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
function iugu_boleto_MetaData()
{
    return array(
        'DisplayName' => 'Iugu WHMCS Pro - Boleto',
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
function iugu_boleto_config(){
    return array(
        // nome amigável do módulo
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Iugu WHMCS v1.5 - Boleto',
        ),
        // token da API da Iugu
        'api_token' => array(
            'FriendlyName' => 'Token',
            'Type' => 'text',
            'Size' => '40',
            'Default' => '',
            'Description' => 'Acesse sua conta Iugu para gerar seu token.',
        ),
        // dias adicionais para vencimento do boleto
        'dias' => array(
            'FriendlyName' => 'Dias Adicionais',
						'Type' => 'dropdown',
            'Options' => array(
                '1' => '1 dia',
                '2' => '2 dias',
                '3' => '3 dias',
                '4' => '4 dias',
                '5' => '5 dias',
            ),
            'Description' => 'Quantos dias serão acrescidos após o boleto estar vencido?',
        ),
        'cpf_cnpj_field' => array(
            'FriendlyName' => 'Campo CPF/CNPJ',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Insira o nome referente ao campo CPF/CNPJ',
        ),
    );
}

// Cadastra o cliente na Iugu
function add_client( $params ){
  try{
    Iugu::setApiKey($params['api_token']);
    $iuguCustomer = Iugu_Customer::create(Array(
      "email" => $params['clientdetails']['email'],
      "name" => $params['clientdetails']['fullname'],
      "notes" => "Cliente cadastrado através do WHMCS",
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
                                        'iugu_id' => $iuguCustomer->id
                                      ]
                                    );
    logModuleCall("Iugu Boleto", "Criar Cliente", $params['clientdetails']['userid'], $iuguCustomer);

    return $iuguCustomer->id;
  }catch (\Exception $e){
    //logModuleCall("Iugu Cartao","Buscar Cliente",$userid,$iuguUserId);
    echo "Problemas em cadastrar o cliente na Iugu. {$e->getMessage()}";
  }
}
// Busca na tabela mod_iugu_customers se já existe o cliente cadastrado
function search_client( $user ) {
  try{
    $iuguUserId = Capsule::table('mod_iugu_customers')->where('user_id', $user)->value('iugu_id');
    logModuleCall("Iugu Boleto","Buscar Cliente",$user,$iuguUserId);
    return $iuguUserId;
  }catch (\Exception $e){
    //logModuleCall("Iugu Cartao","Buscar Cliente",$userid,$iuguUserId);
    echo "Problemas em localizar o cliente no banco de dados local. Erro 001. {$e->getMessage()}";
  }
}

// Busca na tabela mod_iugu se já existe uma fatura criada na Iugu referente a invoice do WHMCS
function search_invoice( $invoice ) {
  //$iuguInvoiceId = Array();
  try{
    // $iuguInvoiceId = Capsule::table('mod_iugu')->where('invoice_id', $invoiceid)->value('iugu_id');
    $iuguInvoiceId = Capsule::table('mod_iugu')->where('invoice_id', $invoice)->value('iugu_id');
    logModuleCall("Iugu Boleto","Buscar Fatura",$invoice,$iuguInvoiceId);
    return $iuguInvoiceId;
  }catch (\Exception $e){
    echo "Problemas em localizar a fatura no banco local. {$e->getMessage()}";
  }
}


function iugu_boleto_link( $params ){

// System Parameters
	$apiToken = $params['api_token'];
  $apiUserName = $params['api_username'];
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
  $userid = $params['clientdetails']['userid'];
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
  $cpf_cnpj_field = $params['cpf_cnpj_field'];
  $cpf_cnpj = $params['clientdetails']["$cpf_cnpj_field"];
  //var_dump($cpf_cnpj);


	// Invoice Parameters
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
	$amount = number_format($params['amount'], 2, '', '');
	$currencyCode = $params['currency'];
  // solicitação a API interna do WHMCS para busca de detalhes da fatura, principalmente sua data de vencimento
  $command = "getinvoice";
  $adminuser = $apiUserName;
  $values["invoiceid"] = $invoiceid;
  $results = localAPI($command,$values,$adminuser);
  //  print_r($results);
  $dueDate = date('d/m/Y', strtotime($results['duedate']));
  // echo "<br>";
  // echo "data de vencimento";
  // print_r(date('d/m/Y', strtotime($results['duedate'])));
  // echo "<br>";

	/** @var stdClass $itens */
	$itens = Array();
	try {
    $selectInvoiceItens = Capsule::table('tblinvoiceitems')->select('amount', 'description')->where('invoiceid', $invoiceid)->get();
			}catch (\Exception $e) {
    		echo "Não foi possível gerar os itens da fatura. {$e->getMessage()}";
				}

  foreach ($selectInvoiceItens as $key => $value) {
    $valor = number_format($value->amount, 2, '', '');
    $item = Array();
    $item['description'] = $value->description;
    $item['quantity'] = "1";
    $item['price_cents'] = $valor;
    $itens[] = $item;
  }

  // busca o usuario no banco local
  $iuguClientId = search_client( $userid );
  // busca informações da fatura no banco local para comparação e verificação
  $iuguInvoiceId = search_invoice( $invoiceid );
  // se não retornar o usuário, presume-se que ele não existe. Então vamos cadastra-lo.
  if(!$iuguClientId){
    $iuguClientId = add_client( $params );
  }
  // se não retornar uma fatura com o ID procurado, presume-se que é nova. Então cadastra.
  if(!$iuguInvoiceId){
    Iugu::setApiKey($apiToken);
  	$createInvoice = Iugu_Invoice::create(Array(
  		"email" => $email,
  		"due_date" => $dueDate,
  		"return_url" => $returnUrl,
  		"expired_url" => $expired_url,
  		"notification_url" => $notification_url,
      "customer_id" => $iuguClientId,
      "payable_with" => 'bank_slip',
  		"items" => $itens,
  		"ignore_due_email" => false,
  		"custom_variables" => Array(
  			Array(
  				"name" => "invoice_id",
  				"value" => $invoiceid
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
    // print_r($createInvoice);
    logModuleCall("Iugu Boleto","Gerar Fatura",$invoiceid,json_decode($createInvoice, true));
    // insere na tabela mod_iugu os dados de retorno referente a criação da fatura Iugu
    Capsule::table('mod_iugu')->insert(
                                                          [
                                                            'invoice_id' => $invoiceid,
                                                            'iugu_id' => $createInvoice->id,
                                                            'secure_id' => $createInvoice->secure_id
                                                          ]
                                                        );

  $htmlOutput = '<a class="btn btn-success btn-lg" target="_blank" role="button" href="'.$createInvoice->secure_url.'?bs=true">'.$langPayNow.'</a>
                <p>Linha Digitável: <br><small>'.$createInvoice->bank_slip->digitable_line.'</small></p>
                <p><img class="img-responsive" src="'.$createInvoice->bank_slip->barcode.'" ></p>
                ';
  return $htmlOutput;
}else {

    Iugu::setApiKey($apiToken);
    $fetchInvoice = Iugu_Invoice::fetch($iuguInvoiceId);
    //print_r($fetchInvoice);
    logModuleCall("Iugu Boleto","Buscar Fatura Iugu",$invoiceid,$fetchInvoice);

    $htmlOutput = '<a class="btn btn-success btn-lg" target="_blank" role="button" href="'.$fetchInvoice->secure_url.'?bs=true">'.$langPayNow.'</a>
                  <p>Linha Digitável: <br><small>'.$fetchInvoice->bank_slip->digitable_line.'</small></p>
                  <p><img class="img-responsive" src="'.$fetchInvoice->bank_slip->barcode.'" ></p>
                  ';

    return $htmlOutput;

  }

} //function


?>
