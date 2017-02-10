<?php

// http://docs.whmcs.com/Addon_Module_Developer_Docs

/**
 *
 * @ IUGU ADDON FOR WHMCS
 *
 * @ Version  : 7.X
 * @ Author   : EUNAREDE
 * @ Release on : 2016-10-21
 * @ Website  : http://www.eunarede.com
 *
 * */

if (!defined("WHMCS"))
    die("Esse arquivo não pode ser acessado diretamente.");

use Illuminate\Database\Capsule\Manager as Capsule;

function iugu_config() {
    $configarray = array(
		"name" => "Iugu",
		"description" => "Addon necessário para o funcionamento do módulo de pagamento da Iugu.",
		"version" => "2.0",
		"author" => "EunaRede",
    "fields" => array(
                      "iugu_account_number" => array(
                                                    "FriendlyName" => "Número da Conta Iugu",
                                                    "Type" => "text",
                                                    "Size" => "25",
                                                    "Description" => "O número da sua conta Iugu (não confundir com api)",
                                                    ),
                      "iugu_api_token" => array(
                                              "FriendlyName" => "Token da API Iugu",
                                              "Type" => "text",
                                              "Size" => "25",
                                              "Description" => "A Chave de API (Token)",
                      ),
    ),
	);
    return $configarray;
}

function iugu_upgrade($vars){
  $version = $vars['version'];

  #Cria a nova tabela no banco na atualização de versão
  if($version < 2.0){
    $query = "CREATE TABLE `mod_iugu_customers` (`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` int(11) NOT NULL, `iugu_id` varchar(255) NOT NULL)";
    $result = mysql_query($query);

  }
}

function iugu_activate() {

    # Create Custom DB Table
    $query = "CREATE TABLE `mod_iugu_invoices` (`id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, `invoice_id` int(11) NOT NULL, `iugu_id` varchar(255) NOT NULL, `secure_id` varchar(255) NOT NULL)";
    $result = mysql_query($query);
    $query = "CREATE TABLE `mod_iugu_customers` (`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` int(11) NOT NULL, `iugu_id` varchar(255) NOT NULL)";
    $result = mysql_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Addon instalado com sucesso! Você já pode configurar/utilizar o módulo de pagamento da Iugu.');
    return array('status'=>'error','description'=>'Erro ao instalar addon.');

}

function iugu_output($vars){

  require_once("iugu-php/lib/Iugu.php");

  $iuguAccNumber = $vars['iugu_account_number'];
  $iuguApiToken = $vars['iugu_api_token'];

  // Busca na tabela mod_iugu se já existe uma fatura criada na Iugu referente a invoice do WHMCS
  //$iuguInvoiceId = Array();
  try{
  // $iuguInvoiceId = Capsule::table('mod_iugu')->where('invoice_id', $invoiceId)->value('iugu_id');
  $tableUsers = Capsule::table('mod_iugu_customers')->select('user_id', 'iugu_id')->get();
}catch (\Exception $e){
  echo "Problemas em localizar a sua fatura. Contate nosso suporte e informe o erro 001. {$e->getMessage()}";
}
echo "<div class='tablebg'>";
echo '<h2>Usuários com Cadastro na Iugu</h2><p>Usuários do WHMCS com cadastro associado na Iugu. Dados do Banco</p>';
echo "<table id='sortabletbl0' class='datatable' width='100%' border='0' cellspacing='1' cellpadding='3'>";
echo '<thead>';
echo '<tr>';
echo '<th>User</th>';
echo '<th>Iugu User</th>';
echo '</tr>';
echo '</thead><tbody>';
echo '<tbody>';
foreach ($tableUsers as $key => $value) {
  echo '<tr><td>';
  print_r($value->user_id);
  echo '</td><td>';
  print_r($value->iugu_id);
  echo '</td></tr>';
}
echo '</tbody></table></div>';

try{

  Iugu::setApiKey($iuguApiToken);
  $customers = Iugu_Customer::search()->results();
  // var_dump($customers);
  echo "<div class='tablebg'>";
  echo '<h2>Usuários Iugu</h2><p>Usuários cadastrados na Iugu. Dados da API</p>';
  echo "<table id='sortabletbl0' class='datatable' width='100%' border='0' cellspacing='1' cellpadding='3'>";
  echo '<thead>';
  echo '<tr>';
  echo '<th>Nome</th>';
  echo '<th>Email</th>';
  echo '<th>ID</th>';
  echo '</tr>';
  echo '</thead><tbody>';
  echo '<tbody>';
  foreach ($customers as $key => $value) {
    echo '<tr><td>';
    print_r($value->name);
    echo '</td><td>';
    print_r($value->email);
    echo '</td><td>';
    print_r($value->id);
    echo '</td></tr>';
  }
  echo '</tbody></table></div>';

}catch (\Exception $e){

  echo "Problemas em localizar a sua fatura. Contate nosso suporte e informe o erro 001. {$e->getMessage()}";
}

}



function iugu_deactivate() {

    # Remove Custom DB Table
    $query = "DROP TABLE `mod_iugu_invoices`";
    $result = mysql_query($query);
    $query = "DROP TABLE `mod_iugu_customers`";
    $result = mysql_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Addon desinstalado com sucesso. Não deixei de desativar o módulo de pagamento da Iugu para evitar problemas futuros.');
    return array('status'=>'error','description'=>'Erro ao desinstalar addon.');

}
