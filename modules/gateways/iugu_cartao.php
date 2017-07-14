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
function iugu_cartao_MetaData(){
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
function iugu_cartao_config(){
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
        'cpf_cnpj_field' => array(
            'FriendlyName' => 'Campo CPF/CNPJ',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Insira o nome referente ao campo CPF/CNPJ',
        ),
        'test_mode' => array(
            'FriendlyName' => 'Ativar modo teste',
            'Type' => 'yesno',
            'Description' => 'ativar a utilização de cartões de crédito de teste.'
        ),
    );
}

function iugu_cartao_add_client( $params ) {

  $campoDoc = $params['cpf_cnpj_field'];

  Iugu::setApiKey($params['api_token']);
  
  $iuguUser = Iugu_Customer::create(Array(
    "email" => $params['clientdetails']['email'],
    "name" => $params['clientdetails']['fullname'],
    "cpf_cnpj" => $params['clientdetails'][$campoDoc],
    "zip_code" => $params['clientdetails']['postcode'],
    "number" => $params['clientdetails']['address2'],
    "notes" => "Cliente criado através do WHMCS",
    "custom_variables" => Array(
      Array(
        "name" => "whmcs_user_id",
        "value" => $params['clientdetails']['userid']
      ))
  ));
  // Insere na tabela mod_iugu_customers o Código do cliente Iugu
  Capsule::table('mod_iugu_customers')->insert(
                                    [
                                      'user_id' => $params['clientdetails']['userid'],
                                      'iugu_id' => $iuguUser->id
                                    ]
                                  );
  logModuleCall("Iugu","iugu_create_client",$params['clientdetails']['userid'],$iuguUser);
  return $iuguUser->id;
}

// Busca na tabela mod_iugu_customers se já existe o cliente cadastrado
function iugu_cartao_search_client( $user ) {

    // procura no banco
    $iuguUserId = Capsule::table('mod_iugu_customers')->where('user_id', $user)->value('iugu_id');

    // loga a ação para debug
    logModuleCall("Iugu","iugu_search_client",$user,$iuguUserId);

    // retorna o ID do cliente
    return $iuguUserId;

}


function iugu_cartao_storeremote($params){

  $apiToken = $params['api_token'];
  $testMode = "true";
  if (!$params['test_mode']) {
    $testMode = "false";
  }
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
  $iuguClientId = iugu_cartao_search_client( $userid );
  if ( is_null($iuguClientId) ) {
    $iuguClientId = iugu_cartao_add_client($params);
  }
  logModuleCall("iugu_cartao_storeremote","Buscar Cliente",$userid,$iuguClientId);
  }catch (\Exception $e){
  //logModuleCall("Iugu Cartao","Buscar Cliente",$userid,$iuguClientId);
  echo "Problemas em localizar o cliente no banco de dados local. Erro 001. {$e->getMessage()}";
  }

  // Cria o token de pagamento através dos dados do cartão do cliente
  $urlToken = 'https://api.iugu.com/v1/payment_token';
  $postfieldsToken = array(
    "account_id" => $accountId,
    "method" => 'credit_card',
    "test" => $testMode,
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
    $urlMethod = "https://api.iugu.com/v1/customers/{$iuguClientId}/payment_methods";
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


function iugu_cartao_capture( $params ){

  // System Parameters
	$apiToken = $params['api_token'];

  $userid = $params['clientdetails']['userid'];
  //var_dump($cpf_cnpj);

	// Invoice Parameters
	$invoiceId = $params['invoiceid'];
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
    $iuguCustomerId = iugu_cartao_search_client( $userid );
    logModuleCall("Iugu","iugu_cartao_capture","Cliente",$iuguCustomerId);
  }catch (\Exception $e){
    echo "Problemas em localizar o cliente no banco de dados local. {$e->getMessage()}";
  }

  if (!$iuguCustomerId) {
    $iuguCustomerId = iugu_cartao_add_client( $params );
  }

	Iugu::setApiKey($apiToken);
	$chargeInvoice = Iugu_Charge::create(Array(
    "customer_payment_method_id" => $tokenisedPaymentId,
    "customer_id" => $iuguCustomerId,
     "email" => $email,
		"items" => $itens
	));

 if($chargeInvoice->success){
   logModuleCall("Iugu Cartao","Captura da Fatura","Sucesso",$chargeInvoice->invoice_id);
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
