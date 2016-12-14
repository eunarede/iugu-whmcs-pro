<?php
/**
 *
 * @ IUGU HOOKS FOR WHMCS
 *
 * @ Version  : 6.X
 * @ Author   : EUNAREDE
 * @ Release on : 2016-10-21
 * @ Website  : http://www.eunarede.com
 *
 * */
if (!defined( "WHMCS" )) {
	exit( "This file cannot be accessed directly" );
}
use Illuminate\Database\Capsule\Manager as Capsule;

// Gatilho para criação do cliente na Iugu para cobranças futuras

function hook_create_client($vars){
	if (!$currentPage == '/cart.php?a=checkout&amp;'){
		require_once("iugu-php/lib/Iugu.php");
	}

	// problemas com os includes deste gatilho com outro include do iugu. <-----

  // $apiToken = $vars['iugu_api_token'];
  $table = "tbladdonmodules";
	$fields = "value";
	$where = array("module" => "iugu","setting" => "iugu_api_token");
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);

  $apiToken = $data["value"];
  $userId = $vars['userid'];
  $email = $vars['email'];
  $name = $vars['firstname'];


  try{
    Iugu::setApiKey($apiToken);
    $createUser = Iugu_Customer::create(Array(
      "email" => $email,
      "name" => $name,
      "notes" => "Cliente adicionado pelo WHMCS",
			"custom_variables" => Array(
  			Array(
  				"name" => "whmcs_user_id",
  				"value" => $userId
  			))
    ));

  }catch (\Exception $e){
    echo "erro.";
    echo $e->getMessage();
  }



  // Insere na tabela mod_iugu_customers o Código do cliente Iugu
  try{
    Capsule::table('mod_iugu_customers')->insert(
                                      [
                                        'user_id' => $userId,
                                        'iugu_id' => $createUser->id
                                      ]
                                    );
  }catch (\Exception $e){
    echo "erro.";
    echo $e->getMessage();
  }


}
 //add_hook('ClientAdd', 1, 'hook_create_client');

 ?>
