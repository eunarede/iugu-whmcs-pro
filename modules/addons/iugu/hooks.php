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
  require_once("iugu-php/lib/Iugu.php");

  // $apiToken = $vars['iugu_api_token'];
  $table = "tbladdonmodules";
	$fields = "value";
	$where = array("module" => "iugu","setting" => "iugu_api_token");
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);

  $apiToken = $data["value"];
  $userid = $vars['userid'];
  $email = $vars['email'];
  $name = $vars['firstname'];


  try{
    Iugu::setApiKey($apiToken);
    $createUser = Iugu_Customer::create(Array(
      "email" => $email,
      "name" => $name,
      "notes" => "Cliente adicionado pelo WHMCS - $userid"
    ));

  }catch (\Exception $e){
    echo "erro.";
    echo $e->getMessage();
  }



  // Insere na tabela mod_iugu_customers o Código do cliente Iugu
  try{
    Capsule::table('mod_iugu_customers')->insert(
                                      [
                                        'user_id' => $userid,
                                        'iugu_id' => $createUser->id
                                      ]
                                    );
  }catch (\Exception $e){
    echo "erro.";
    echo $e->getMessage();
  }


}
add_hook('ClientAdd', 1, 'hook_create_client');

 ?>
