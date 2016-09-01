<?php

if($_POST['event'] == "invoice.status_changed"){

	include("../../../init.php");
	include("../../../dbconnect.php");
	include("../../../includes/functions.php");
	include("../../../includes/gatewayfunctions.php");
	include("../../../includes/invoicefunctions.php");
	require_once("../iugu/Iugu.php");

	$gatewaymodule = "iugu";

	$GATEWAY = getGatewayVariables($gatewaymodule);
	if (!$GATEWAY["type"]) die("Module Not Activated");
	
	$post_iugu = array(
		"event" => $_POST['event'],
		"id" => $_POST["data"]["id"],
		"status" => $_POST["data"]["status"]
	);
	
	Iugu::setApiKey($GATEWAY["api_token"]);
	$consultar = Iugu_Invoice::fetch($_POST["data"]["id"]);

	if($consultar->status == "paid" || $consultar->status == "partially_paid"){
		$valor = explode("R$ ", $consultar->total_paid);
		$taxa = explode("R$ ", $consultar->taxes_paid);

		$status = $consultar->status;
		$amount = str_replace(",", ".", $valor[1]);
		$fee = str_replace(",", ".", $taxa[1]);

		foreach($consultar->variables AS $variavel){
			if($variavel->variable == "payment_data.transaction_number"){
				$transid = $variavel->value;
			}
		}

		foreach($consultar->custom_variables AS $variavel){
			if($variavel->name == "invoice_id"){
				$invoiceid = $variavel->value;
			}
		}

		$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]);

		checkCbTransID($transid);

		addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
		logTransaction($GATEWAY["name"],$post_iugu,"Successful");
		
		$sql = mysql_query("SELECT * FROM mod_iugu WHERE fatura_id='".$invoiceid."' AND iugu_id != '".$consultar->id."'");
		while($row = mysql_fetch_array($sql)){
			Iugu::setApiKey($GATEWAY["api_token"]);
			$cancelar = Iugu_Invoice::fetch($row['iugu_id']);
			$cancelar->cancel();
			$remover = Iugu_Invoice::fetch($row['iugu_id']);
			$remover->delete();
			mysql_query("DELETE FROM mod_iugu WHERE id='".$row['id']."'");
		}

	}
}

?>
