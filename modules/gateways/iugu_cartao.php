<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use Illuminate\Database\Capsule\Manager as Capsule;
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
        'DisplayName' => 'Iugu WHMCS v1.5 - Cartão',
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
            'Value' => 'Iugu WHMCS v1.5 - Cartão',
        ),
        // a text field type allows for single line text input
        'account_id' => array(
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
  require_once("iugu/Iugu.php");

  $accountId = $params['account_id'];

  Iugu::setApiKey($apiToken);
  $createToken = Iugu_PaymentToken::create(Array(
    "account_id" => $accountId,
    "method" => 'credit_card',
    "data" => Array(
        "number" => $params['cardnum'],
        "verification_value" => $params['cccvv'],
        "first_name" => $params['clientdetails']['firstname'],
        "last_name" => $params['clientdetails']['lastname'],
        "month" => $cardMonth,
        "year" => $cardYear
    )
  ));

  //se o token do cartão foi criado, tenta criar o metodo de pagamento do cliente
  if(!empty($createToken->id)){
    // Busca na tabela mod_iugu_customers o ID do cliente da Iugu
    try{
      $iuguCustomerId = Capsule::table('mod_iugu_customers')->where('user_id', $userid)->pluck('iugu_id');
    }catch (\Exception $e){
      echo "Problemas em localizar o cliente Iugu. Contate nosso suporte e informe o erro 001.";
      echo $e->getMessage();
    }

    //cria o método de pagamento padrão para armazenar o token do cartão de credito na iugu
    Iugu::setApiKey($apiToken);

    $customer = Iugu_Customer::fetch($iuguCustomerId);
    $payment_method = $customer->payment_methods()->create(Array(
    "description" => "Primeiro Cartão",
    "item_type" => "credit_card",
    "token" => $createToken->id
    ));
    //se o id do metodo de pagamento foi criado com sucesso, retorna sucesso ao _storeremote
    if(!empty($payment_method->id)){
      return array(
        "status" => "success",
        "gatewayid" => $payment_method->id,
        "rawdata" => $payment_method,
      );
    }
    //senão retorna falha ao _storeremote
    else{
      return array(
        "status" => "failed",
        "rawdata" => $createToken,
      );
    }

  }
  // se o token nao foi criado com sucesso, não tenta criar o metodo de pagamento
  // e retorna falha ao _storeremote
  else{
    return array(
      "status" => "failed",
      "rawdata" => $createToken,
    );
  }


}


function iugu_cartao_capture($params){

require_once("iugu/Iugu.php");

//var_dump($params);

// System Parameters
	$apiToken = $params['api_token'];
  $companyName = $params['companyname'];
  $langPayNow = "Pagar com Cartão de Crédito";
  $moduleDisplayName = $params['name'];
  $moduleName = $params['paymentmethod'];
  $whmcsVersion = $params['whmcsVersion'];

// Client Parameters
  $fullname = $params['clientdetails']['fullname'];
  $firstname = $params['clientdetails']['firstname'];
  $lastname = $params['clientdetails']['lastname'];
  $email = $params['clientdetails']['email'];
  $address1 = $params['clientdetails']['address1'];
  $address2 = $params['clientdetails']['address2'];
  $city = $params['clientdetails']['city'];
  $state = $params['clientdetails']['state'];
  $postcode = $params['clientdetails']['postcode'];
  $country = $params['clientdetails']['country'];
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
			}catch (\Exception $e) {
    		echo "Não foi possível gerar a URL. {$e->getMessage()}";
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
    $iuguCustomerId = Capsule::table('mod_iugu_customers')->where('user_id', $userid)->pluck('iugu_id');
  }catch (\Exception $e){
    echo "Problemas em localizar a sua fatura. Contate nosso suporte e informe o erro 001. {$e->getMessage()}";
  }

	Iugu::setApiKey($apiToken);
	$chargeInvoice = Iugu_Charge::create(Array(
    "customer_payment_method_id" => $tokenisedPaymentId,
    "customer_id" => $iuguCustomerId,
		"items" => $itens,
		"payer" => Array(
			"cpf_cnpj" => $cpf_cnpj,
			"name" => $fullname,
			"email" => $email,
			"address" => Array(
				"street" => $address1,
				"number" => $address2,
				"city" => $city,
				"state" => $state,
				"country" => $country,
				"zip_code" => $postcode
			)
		)
	));

 if($chargeInvoice->success){
   return array(
     "status" => "success",
     "transid" => $chargeInvoice->invoice_id,
     "rawdata" => $chargeInvoice,
 );
 }else{
   return array(
    "status" => "declined",
    "rawdata" => $chargeInvoice,
);
 }

}//function


?>
