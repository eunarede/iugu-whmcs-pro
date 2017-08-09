<?php
/**
 *
 * @ IUGU HOOKS FOR WHMCS
 *
 * @ Version  : 7.X
 * @ Author   : EUNAREDE
 * @ Release on : 2016-10-21
 * @ Website  : http://www.eunarede.com
 *
 * */
if (!defined( "WHMCS" )) {
	exit( "This file cannot be accessed directly" );
}
use Illuminate\Database\Capsule\Manager as Capsule;
use Unirest\Request as Request;
use Unirest\Body as Body;
// require_once("src/Unirest.php");

# Remove dados do cliente no sistema Iugu quando o mesmo for removido do WHMCS

function hook_delete_iugu_client( $vars ) {

	require_once("iugu-php/lib/Iugu.php");

	// Dados para conexão com API da Iugu
	// $addonData = Capsule::table('tbladdonmodules')->where('module', 'iugu')->get();
	// $apiToken = $addonData['iugu_api_token'];
	// $accountNumber = $addonData['iugu_account_number'];

	$table = "tbladdonmodules";
	$fields = "value";
	$where = array("module" => "iugu","setting" => "iugu_api_token");
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);

	$apiToken = $data["value"];

	// Dados do cliente no WHMCS para exlcusão
	$userid = $vars['userid'];
	$iuguUserId = Capsule::table('mod_iugu_customers')->where('user_id', $userid)->value('iugu_id');
	// se os dados existirem na tabela mod_iugu_customers então busque na Iugu e apague
	if($iuguUserId) {
		Iugu::setApiKey($apiToken);
		$customer = Iugu_Customer::fetch($iuguUserId);
		$customer->delete();
		// remove da tabela local os dados do cliente associado a Iugu
		try{
			Capsule::table('mod_iugu_customers')->where('user_id', $userid)->delete();
		}catch(\Exception $e){
			echo "Problemas em apagar o cliente no banco de dados local. {$e->getMessage()}";
		}
		$response = json_decode($customer, true);
		logModuleCall("Iugu Clients","Apagar Cliente",$iuguUserId,$response);
}

}

function hook_cancel_invoice_iugu( $vars ) {

// use Unirest\Request as Request;
// use Unirest\Body as Body;
require_once("src/Unirest.php");


	$apitoken = Capsule::table('tbladdonmodules')->where([
		['module', '=', 'iugu'],
		['setting', '=', 'iugu_api_token'],
	])->value('value');

	$iuguinvoiceid = Capsule::table('mod_iugu_invoices')
									->where('invoice_id', $vars['invoiceid'])
									->value('iugu_id');

									if (!is_null($iuguinvoiceid)){
										Unirest\Request::auth("$apitoken", '');
										$result = Unirest\Request::put("https://api.iugu.com/v1/invoices/$iuguinvoiceid/cancel");
									}




}
//add_hook('PreDeleteClient', 1, 'hook_delete_iugu_client');
// add_hook('ClientAdd', 1, 'hook_create_client');
add_hook('InvoiceCancelled', 1, 'hook_cancel_invoice_iugu');
 ?>
