<?php
if (!defined("WHMCS"))
    die("Esse arquivo não pode ser acessado diretamente.");

function iugu_config() {
    $configarray = array(
		"name" => "Iugu",
		"description" => "Addon necessário para o funcionamento do módulo de pagamento da Iugu.",
		"version" => "1.0",
		"author" => "Dom Host",
	);
    return $configarray;
}

function iugu_activate() {

    # Create Custom DB Table
    $query = "CREATE TABLE `mod_iugu` (`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY, `fatura_id` int(11) NOT NULL, `iugu_id` varchar(255) NOT NULL, `secure_id` varchar(255) NOT NULL, `valor` varchar(255) NOT NULL, `vencimento` varchar(255) NOT NULL )";
    $result = full_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Addon instalado com sucesso! Você já pode configurar/utilizar o módulo de pagamento da Iugu.');
    return array('status'=>'error','description'=>'Erro ao instalar addon.');

}

function iugu_deactivate() {

    # Remove Custom DB Table
    $query = "DROP TABLE `mod_iugu`";
    $result = full_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Addon desinstalado com sucesso. Não deixei de desativar o módulo de pagamento da Iugu para evitar problemas futuros.');
    return array('status'=>'error','description'=>'Erro ao desinstalar addon.');

}
