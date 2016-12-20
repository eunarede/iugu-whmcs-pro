<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use Illuminate\Database\Capsule\Manager as Capsule;
require_once("iugu/Iugu.php");
/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Meta_Data_Parameters
 *
 * @return array
 */
function iugu_cartao_MetaData()
{
    return array(
        'DisplayName' => 'Iugu WHMCS Pro - Cartão',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => true,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function iugu_cartao_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Iugu WHMCS Pro - Cartão',
        ),
        // a text field type allows for single line text input
        'iugu_account_id' => array(
            'FriendlyName' => 'Número da Conta',
            'Type' => 'text',
            'Size' => '40',
            'Default' => '',
            'Description' => 'Número da sua conta Iugu. Não confunda com API.',
        ),
        'api_token' => array(
            'FriendlyName' => 'Token',
            'Type' => 'text',
            'Size' => '40',
            'Default' => '',
            'Description' => 'Acesse sua conta Iugu para gerar seu token.',
        ),
    );
}


function iugu_cartao_storeremote($params){

  $apiToken = $params['api_token'];
  $accountId = $params['iugu_account_id'];
  // parametro com o ID da conta WHMCS
  $userid = $params['clientdetails']['userid'];
  // Dados do cliente recebidos como sugerido pela equipe do WHMCS
  $clientFirstName = $params['clientdetails']['firstname']; # cliente first name
  $clientLastName = $params['clientdetails']['lastname']; # client last name
  $clientEmail = $params['clientdetails']['email']; # client email
  // tratamento do vencimento do cartão de crédito para a Iugu
  $cardMonth = substr( $params['cardexp'], 0, -2 ); # trata a expiração do cc para mês
  // $cardYear = substr_replace('20', $params['cardexp'], 2, 0); # trata a expiração do cc para ano
  $cardYear = substr( $params['cardexp'], 2, 2 ); # trata a expiração do cc para ano
  // Dados do cartão
  $cardNumber = $params['cardnum']; # the Card Number
  $cardCvv = $params['cardcvv']; # the verification card code
  // Busca na tabela mod_iugu se já existe um cliente criado na Iugu
  try{
  $iuguUserId = Capsule::table('mod_iugu_customers')->where('user_id', $userid)->value('iugu_id');
  logModuleCall("Iugu Cartao","Buscar Cliente",$userid,$iuguUserId);
}catch (\Exception $e){
  //logModuleCall("Iugu Cartao","Buscar Cliente",$userid,$iuguUserId);
  echo "Problemas em localizar o cliente no banco de dados local. Erro 001. {$e->getMessage()}";
}
if (!$iuguUserId) {
  Iugu::setApiKey($apiToken);
  $iuguUser = Iugu_Customer::create(Array(
    "email" => $clientEmail,
    "name" => $clientFirstName,
    "notes" => "Cliente criado através do WHMCS",
    "custom_variables" => Array(
      Array(
        "name" => "whmcs_user_id",
        "value" => $userid
      ))
  ));
  // Insere na tabela mod_iugu_customers o Código do cliente Iugu
  Capsule::table('mod_iugu_customers')->insert(
                                    [
                                      'user_id' => $userid,
                                      'iugu_id' => $iuguUser->id
                                    ]
                                  );
  logModuleCall("Iugu Cartao","Criar Cliente",$userid,$iuguUser);
  $iuguUserId = $iuguUser->id;
}
  // Cria o token de pagamento através dos dados do cartão do cliente
  $urlToken = 'https://api.iugu.com/v1/payment_token';
  $postfieldsToken = array(
    "account_id" => $accountId,
    "method" => 'credit_card',
    "test" => "true",
    "data[number]" => $cardNumber,
    "data[verification_value]" => $cardCvv,
    "data[first_name]" => $clientFirstName,
    "data[last_name]" => $clientLastName,
    "data[month]" => $cardMonth,
    "data[year]" => $cardYear,
  );

  $responseToken = curlCall($urlToken, $postfieldsToken);
  $iuguCreditCardToken = json_decode($responseToken, true);

  logModuleCall("Iugu Cartao","Criar Token",$postfieldsToken,$iuguCreditCardToken);

    //cria o método de pagamento padrão para armazenar o token do cartão de credito na iugu
    $urlMethod = "https://api.iugu.com/v1/customers/{$iuguUserId}/payment_methods";
    $postfieldsMethod = array(
      "api_token" => $apiToken,
      "description" => 'Cartão Inserido via WHMCS',
      "token" => $iuguCreditCardToken['id'],
      "set_as_default" => "true",
    );

    $payment_method = curlCall($urlMethod, $postfieldsMethod);
    $paymentMethodToken = json_decode($payment_method, true);

    logModuleCall("Iugu Cartao","Criar Metodo de Pgto",$payment_method,$paymentMethodToken);

    //se o metodo de pagamento foi criado com sucesso, retorna sucesso ao _storeremote
    if($paymentMethodToken['id']){
      return array(
        "status" => "success",
        "gatewayid" => $paymentMethodToken['id'],
        "rawdata" => $paymentMethodToken,
      );
    }
    //senão retorna falha ao _storeremote
    else{
      return array(
        "status" => "failed",
        "rawdata" => $paymentMethodToken,
      );
    }


} //iugu_cartao_storeremote


function iugu_cartao_capture($params){

// System Parameters
	$apiToken = $params['api_token'];
  $moduleDisplayName = $params['name'];


// Client Parameters
  $fullname = $params['clientdetails']['fullname'];
  $firstname = $params['clientdetails']['firstname'];
  $lastname = $params['clientdetails']['lastname'];
  $email = $params['clientdetails']['email'];
  $phone = $params['clientdetails']['phonenumber'];
  $cpf_cnpj = $params['clientdetails']['customfields1'];
  $userid = $params['userid'];
  //var_dump($cpf_cnpj);

	// Invoice Parameters
	$invoiceId = $params['invoiceid'];
	$description = $params["description"];
	$amount = number_format($params['amount'], 2, '', '');
	$currencyCode = $params['currency'];
	$dueDate = $params['duedate'];
  $tokenisedPaymentId = $params['gatewayid'];

	/** @var stdClass $client */
	$itens = Array();
	try {
    $selectInvoiceItens = Capsule::table('tblinvoiceitems')->select('amount', 'description')->where('invoiceid', $invoiceId)->get();
    logModuleCall("Iugu Cartao","Buscar Itens da Fatura","Itens",$selectInvoiceItens);
			}catch (\Exception $e) {
    		echo "Não foi possível gerar os itens da fatura. Erro 002 {$e->getMessage()}";
				}

  foreach ($selectInvoiceItens as $key => $value) {
    $valor = number_format($value->amount, 2, '', '');
    $item = Array();
    $item['description'] = $value->description;
    $item['quantity'] = "1";
    $item['price_cents'] = $valor;
    $itens[] = $item;
  }

  // Busca na tabela mod_iugu_customers o ID do cliente da Iugu
  try{
    $iuguCustomerId = Capsule::table('mod_iugu_customers')->where('user_id', $userid)->value('iugu_id');
    logModuleCall("Iugu Cartao","Buscar Cliente para Captura","Cliente",$iuguCustomerId);
  }catch (\Exception $e){
    echo "Problemas em localizar o cliente no banco de dados local. {$e->getMessage()}";
  }

	Iugu::setApiKey($apiToken);
	$chargeInvoice = Iugu_Charge::create(Array(
    "customer_payment_method_id" => $tokenisedPaymentId,
    "customer_id" => $iuguCustomerId,
     "email" => $email,
		"items" => $itens
	));

 if($chargeInvoice->success){
   logModuleCall("Iugu Cartao","Captura da Fatura","Sucesso",$chargeInvoice);
   return array(
     "status" => "success",
     "transid" => $chargeInvoice->invoice_id,
     "rawdata" => $chargeInvoice,
 );
 }
 else {
   logModuleCall("Iugu Cartao","Captura da Fatura","Falhou",$chargeInvoice);
   return array(
    "status" => "declined",
    "rawdata" => $chargeInvoice,
);
 }

} # iugu_cartao_capture


?>
