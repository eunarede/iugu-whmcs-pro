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
function iugu_boleto_MetaData()
{
    return array(
        'DisplayName' => 'Iugu WHMCS v1.5 - Boleto',
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
function iugu_boleto_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Iugu WHMCS v1.5 - Boleto',
        ),
        // a text field type allows for single line text input
        'api_token' => array(
            'FriendlyName' => 'Token',
            'Type' => 'text',
            'Size' => '40',
            'Default' => '',
            'Description' => 'Acesse sua conta Iugu para gerar seu token.',
        ),
        // a password field type allows for masked text input
        'dias' => array(
            'FriendlyName' => 'Dias Adicionais',
						'Type' => 'dropdown',
            'Options' => array(
                '1' => '1 Dia',
                '2' => '2 Dias',
                '3' => '3 Dias',
                '4' => '4 Dias',
                '5' => '5 Dias',
            ),
            'Description' => 'Quantos dias serão acrescidos após o boleto estar vencido?',
        ),
    );
}


function iugu_boleto_link($params){

require_once("iugu/Iugu.php");

// System Parameters
	$apiToken = $params['api_token'];
  $companyName = $params['companyname'];
  $systemUrl = $params['systemurl'];
  $returnUrl = $params['returnurl'];
  $expired_url = $returnUrl;
	$notification_url = $systemUrl . '/modules/gateways/callback/iugu_boleto.php';
  $langPayNow = "Imprimir Boleto";
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
  //var_dump($cpf_cnpj);


	// Invoice Parameters
	$invoiceId = $params['invoiceid'];
	$description = $params["description"];
	$amount = number_format($params['amount'], 2, '', '');
	$currencyCode = $params['currency'];
	$dueDate = $params['dudate'];
	if ( $dueDate < date('d/m/Y') ) {
		// se o vencimento for menor que a data atual (fatura ainda não vencida) acrescenta d+
		$vencimento = date('d/m/Y', strtotime('+ '.$params['dias'].' days'));
	} else {
		// senão, vencimento recebe a date de vencimento
		$vencimento = date('d/m/Y', strtotime($dueDate));
	}
  $paymentMethod = 'bank_slip';

	// Print all client first names using a simple select.

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


  // Busca na tabela mod_iugu se já existe uma fatura criada na Iugu referente a invoice do WHMCS
  //$iuguInvoiceId = Array();
  try{
  // $iuguInvoiceId = Capsule::table('mod_iugu')->where('invoice_id', $invoiceId)->value('iugu_id');
  $iuguInvoiceId = Capsule::table('mod_iugu')->where('invoice_id', $invoiceId)->pluck('iugu_id');
}catch (\Exception $e){
  echo "Problemas em localizar a sua fatura. Contate nosso suporte e informe o erro 001. {$e->getMessage()}";
}
// Loop through each Capsule query made during the page request.
// foreach (Capsule::connection()->getQueryLog() as $query) {
//     echo "Query: {$query['query']}" . PHP_EOL;
//     echo "Execution Time: {$query['time']}ms" . PHP_EOL;
//     echo "Parameters: " . PHP_EOL;
//
//     foreach ($query['bindings'] as $key => $value) {
//         echo "{$key} => {$value}" . PHP_EOL;
//     }
// }
  // var_dump($iuguInvoiceId);

  if (!empty($iuguInvoiceId)) {

    Iugu::setApiKey($apiToken);
    $fetchInvoice = Iugu_Invoice::fetch($iuguInvoiceId);
    //print_r($fetchInvoice);

    $htmlOutput = '<a class="btn btn-success btn-lg" role="button" href="'.$fetchInvoice->secure_url.'">'.$langPayNow.'</a>
                  <p>Linha Digitável: <br><small>'.$fetchInvoice->bank_slip->digitable_line.'</small></p>
                  ';


   if(!empty($fetchInvoice->secure_url)){
  	return $htmlOutput;
   }else{
  	 echo "Erro ao carregar a fatura. Contate o suporte e informe o erro 002.";
  	 //print_r($createInvoice);
   }
  }
  else{

  	Iugu::setApiKey($apiToken);
  	$createInvoice = Iugu_Invoice::create(Array(
  		"email" => $email,
  		"due_date" => $vencimento,
  		"return_url" => $returnUrl,
  		"expired_url" => $expired_url,
  		"notification_url" => $notification_url,
  		"items" => $itens,
  		"ignore_due_email" => true,
  		"custom_variables" => Array(
  			Array(
  				"name" => "invoice_id",
  				"value" => $invoiceId
  			)
  		),
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

    // insere na tabela mod_iugu os dados de retorno referente a criação da fatura Iugu
    $insertIuguData = Capsule::table('mod_iugu')->insert(
                                                          [
                                                            'invoice_id' => $invoiceId,
                                                            'iugu_id' => $createInvoice->id,
                                                            'secure_id' => $createInvoice->secure_id
                                                          ]
                                                        );

    $htmlOutput = '<a class="btn btn-success btn-lg" role="button" href="'.$createInvoice->secure_url.'">'.$langPayNow.'</a>
                  <p>Linha Digitável: <small>'.$createInvoice->bank_slip->digitable_line.'</small></p>
                  ';


   if(!empty($createInvoice->secure_url)){
  	return $htmlOutput;
   }else{
  	 echo "Erro ao gerar cobrança. Contate o suporte e informe o erro 003.";
    }
 } //else
} //function


?>
