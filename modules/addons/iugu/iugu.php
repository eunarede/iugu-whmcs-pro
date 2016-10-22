<?php

// http://docs.whmcs.com/Addon_Module_Developer_Docs

if (!defined("WHMCS"))
    die("Esse arquivo não pode ser acessado diretamente.");

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
    $query = "CREATE TABLE `mod_iugu` (`id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, `invoice_id` int(11) NOT NULL, `iugu_id` varchar(255) NOT NULL, `secure_id` varchar(255) NOT NULL)";
    $result = mysql_query($query);
    $query = "CREATE TABLE `mod_iugu_customers` (`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` int(11) NOT NULL, `iugu_id` varchar(255) NOT NULL)";
    $result = mysql_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Addon instalado com sucesso! Você já pode configurar/utilizar o módulo de pagamento da Iugu.');
    return array('status'=>'error','description'=>'Erro ao instalar addon.');

}

function iugu_deactivate() {

    # Remove Custom DB Table
    $query = "DROP TABLE `mod_iugu`";
    $result = mysql_query($query);
    $query = "DROP TABLE `mod_iugu_customers`";
    $result = mysql_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Addon desinstalado com sucesso. Não deixei de desativar o módulo de pagamento da Iugu para evitar problemas futuros.');
    return array('status'=>'error','description'=>'Erro ao desinstalar addon.');

}
