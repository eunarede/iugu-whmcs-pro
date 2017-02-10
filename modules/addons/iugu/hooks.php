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

# Remove dados do cliente no sistema Iugu quando o mesmo for removido do WHMCS

function hook_delete_iugu_client($vars) {

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
//add_hook('PreDeleteClient', 1, 'hook_delete_iugu_client');
// add_hook('ClientAdd', 1, 'hook_create_client');

 ?>
