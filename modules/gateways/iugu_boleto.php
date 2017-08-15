<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use Illuminate\Database\Capsule\Manager as Capsule;
use Unirest\Request as Request;
use Unirest\Body as Body;
require_once("iugu/src/Unirest.php");
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
function iugu_boleto_MetaData(){
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
        'ignore_due_email' => array(
            'FriendlyName' => 'Ignorar e-mails de cobrança da Iugu',
            'Type' => 'yesno',
            'Description' => 'Desabilitar o envio de e-mails da Iugu diretamente ao cliente.'
        ),
    );
}
/**
 * Cria a fatura na API Iugu
 * @param  array $params parametros do WHMCS
 * @return array         Dados do Boleto
 */
function iugu_boleto_create_invoice ( $params ){
  try {

    // System Parameters
    	$apiToken = $params['api_token'];
      $systemUrl = $params['systemurl'];
      $returnUrl = $params['returnurl'];
      $expired_url = $returnUrl;
    	$notification_url = $systemUrl . '/modules/gateways/callback/iugu_boleto.php';
      $langPayNow = "Imprimir Boleto";

    // Client Parameters
      $userid = $params['clientdetails']['userid'];
      $fullname = $params['clientdetails']['fullname'];
      $companyname = $params['clientdetails']['companyname'];
      $email = $params['clientdetails']['email'];
      $address1 = $params['clientdetails']['address1'];
      $address2 = $params['clientdetails']['address2'];
      $city = $params['clientdetails']['city'];
      $state = $params['clientdetails']['state'];
      $postcode = $params['clientdetails']['postcode'];
      $country = $params['clientdetails']['country'];
      $document = $params['cpf_cnpj_field'];
      $cpf_cnpj = $params['clientdetails'][$document] ? preg_replace('/[^0-9]/', '', $params['clientdetails'][$document]) : '00000000000';

      /**
       * caso o cliente possua um nome de empresa cadastrada, utiliza esse valor para ser o nome do pagador
       * @var string
       */
      if($companyname) {
        $payername = $companyname;
      } else {
        $payername = $fullname;
      }

    	// Invoice Parameters
    	$invoiceid = $params['invoiceid'];
    	$description = $params["description"];

      $dueDate = Capsule::table('tblinvoices')->select('duedate')->where('id', $invoiceid)->value('duedate');

    	/** @var stdClass $itens */
    	$itens = array();
    	try {
        $selectInvoiceItens = Capsule::table('tblinvoiceitems')->select('amount', 'description')->where('invoiceid', $invoiceid)->get();
    			}catch (\Exception $e) {
        		echo "Não foi possível gerar os itens da fatura. {$e->getMessage()}";
    				}

      foreach ($selectInvoiceItens as $key => $value) {
        $valor = number_format($value->amount, 2, '', '');
        $item = array();
        $item['description'] = $value->description;
        $item['quantity'] = "1";
        $item['price_cents'] = $valor;
        $itens[] = $item;
      }

      $data = array(
            'email' => $email,
        		'due_date' =>  $dueDate,
        		'return_url' => $returnUrl,
        		'expired_url' => $expired_url,
        		"notification_url" => $notification_url,
            "fines" => true,
            "per_day_interest" => true,
            "ignore_due_email" => false,
            "payable_with" => 'bank_slip',
        		"items" => $itens,
        		'custom_variables' => array(
              [
              'name' => 'invoice_id',
              'value' => $invoiceid,
              ]
            ),
        		"payer" => array(
        			'cpf_cnpj' => $cpf_cnpj,
        			'name' => $payername,
        			'email' => $email,
        			'address' => array(
        				'street' => $address1,
        				'number' => '000',
        				'country' => $country,
        				'zip_code' => $postcode
        			)
        		));

      // basic auth
      Unirest\Request::auth("$apiToken", '');
      $body = Unirest\Request\Body::json($data);
      $headers = array('Content-Type' => 'application/json');
      $createInvoice = Unirest\Request::post('https://api.iugu.com/v1/invoices', $headers, $body);

      // insere na tabela mod_iugu_invoices os dados de retorno referente a criação da fatura Iugu
      Capsule::table('mod_iugu_invoices')->insert(
                                                            [
                                                              'invoice_id' => $invoiceid,
                                                              'iugu_id' => $createInvoice->body->id,
                                                              'secure_id' => $createInvoice->body->secure_id
                                                            ]
                                                          );

      return $createInvoice;

      logModuleCall("Iugu Boleto","Boleto Gerado", json_decode($body, true), json_decode($createInvoice->raw_body, true));

  }catch (\Exception $e){
    echo "Problemas ao criar o boleto. {$e->getMessage()} <br>";
    echo $createInvoice;
    logModuleCall("Iugu Boleto","Problemas ao Gerar Boleto", json_decode($body, true), json_decode($createInvoice->raw_body, true));
  }
}

/**
 * Busca na tabela modmod_iugu_invoices_iugu se já existe uma fatura criada na Iugu referente a invoice do WHMCS
 * @param  array Parametros do WHMCS
 * @return array          Dados do Boleto
 */
function iugu_boleto_search_invoice( $apiToken,  $iuguInvoiceId ) {

  try{

    // basic auth
    Unirest\Request::auth("$apiToken", '');
    $headers = array('Accept' => 'application/json');
    $fetchInvoice = Unirest\Request::get("https://api.iugu.com/v1/invoices/$iuguInvoiceId", $headers, $params = null);
    // retorna o ID da fatura

    logModuleCall("Iugu Boleto", "Busca Fatura", $iuguInvoiceId, json_decode($fetchInvoice->raw_body, true));

    return $fetchInvoice;

  }catch (\Exception $e){
    echo "Problemas em localizar a fatura no banco local. {$e->getMessage()}";
    // loga a ação para debug
    logModuleCall("Iugu Boleto","Erro ao Buscar Fatura",$iuguInvoiceId,json_decode($fetchInvoice->raw_body, true));
  }
}

function iugu_boleto_duplicate_invoice ( $params ) {
  try{

    $apiToken  = $params['api_token'];
    $invoiceid = $params['invoiceid'];
    $today     = date("Ymd");

    $iuguInvoiceId = Capsule::table('mod_iugu_invoices')
                              ->where('invoice_id', $invoiceid)
                              ->value('iugu_id');


    // Autenticacao
    Unirest\Request::auth("$apiToken", '');
    $headers = array('Content-Type' => 'application/json');
    $data    = array(
      'due_date'             => $today,
      'current_fines_option' => true,
    );
    $body          = Unirest\Request\Body::json($data);
    $response      = Unirest\Request::post("https://api.iugu.com/v1/invoices/$iuguInvoiceId/duplicate", $headers, $body);
    $iuguInvoiceId = $response->body->id;
    Capsule::table('mod_iugu_invoices')
            ->where('invoice_id', $invoiceid)
            ->update(array('iugu_id' => $iuguInvoiceId));

    return $response;

  }catch (\Exception $e){
    echo "Problemas em localizar a fatura no banco local. {$e->getMessage()}";
    logModuleCall("Iugu Boleto", "Erro ao gerar segunda via", $iuguInvoiceId, json_decode($response->raw_body, true));
  }
}
/**
 * Gera o link do boleto e exibe o botão na fatura
 * @param  array $params parametros do WHMCS
 * @return string         retorna o botão da fatura
 */
function iugu_boleto_link( $params ){

  $apiToken  = $params['api_token'];
  $langPayNow = "Baixar Boleto";
  $invoiceid = $params['invoiceid'];
  $duedate = Capsule::table('tblinvoices')->select('duedate')->where('id', $invoiceid)->first();

  // procura no banco
  $iuguInvoiceId = Capsule::table('mod_iugu_invoices')->where('invoice_id', $invoiceid)->value('iugu_id');

  if ($iuguInvoiceId){
    // busca informações da fatura no banco local para comparação e verificação
    $invoiceparams = iugu_boleto_search_invoice( $apiToken,  $iuguInvoiceId );
  }

  // se não retornar uma fatura com o ID procurado, presume-se que é nova. Então cadastra.
  if( is_null($iuguInvoiceId) ){
    $invoiceparams = iugu_boleto_create_invoice( $params );
    $htmlOutput = '<a class="btn btn-success btn-lg" target="_blank" role="button" download href="'.$invoiceparams->body->secure_url.'.pdf">'.$langPayNow.'</a>
                  <p>Linha Digitável: <br><small>'.$invoiceparams->body->bank_slip->digitable_line.'</small></p>
                  <p><img class="img-responsive" src="'.$invoiceparams->body->bank_slip->barcode.'" ></p>
                  ';
    return $htmlOutput;
  }

  // se a fatura estiver cancelada, não exibe o boleto e informa o cliente
  if ($invoiceparams->body->status == 'canceled') {
    $htmlOutput = "
                  <p class='bg-danger text-danger'>Este Boleto está cancelado. Contacte o setor financeiro.</p>
    ";
    return $htmlOutput;
  }

  // se retornar a data de vencimento menor que o dia de hoje, gera segunda via do boleto
  if ($invoiceparams->body->status != 'expired' && date("Y-m-d", strtotime($invoiceparams->body->due_date)) < date("Y-m-d")){

    $invoiceparams = iugu_boleto_duplicate_invoice( $params );
    $htmlOutput = '<a class="btn btn-success btn-lg" target="_blank" role="button" download href="'.$invoiceparams->body->secure_url.'.pdf">'.$langPayNow.'</a>
                  <p>Linha Digitável: <br><small>'.$invoiceparams->body->bank_slip->digitable_line.'</small></p>
                  <p><img class="img-responsive" src="'.$invoiceparams->body->bank_slip->barcode.'" ></p>
                  ';
    return $htmlOutput;
  }

  $htmlOutput = '<a class="btn btn-success btn-lg" target="_blank" role="button" download href="'.$invoiceparams->body->secure_url.'.pdf">'.$langPayNow.'</a>
                <p>Linha Digitável: <br><small>'.$invoiceparams->body->bank_slip->digitable_line.'</small></p>
                <p><img class="img-responsive" src="'.$invoiceparams->body->bank_slip->barcode.'" ></p>
                ';
  return $htmlOutput;

} //function

?>
