<?php
/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../iugu/Iugu.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// die if post data does not contain event information
if (!isset($_POST['event'])) {
  die("Invoice Does Not Contain Valid Information");
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$iugu_event = $_POST['event'];
$iugu_invoice_id = $_POST['data']['id'];
$iugu_status = $_POST['data']['status'];
//var_dump($iuguEvent);
//var_dump($iuguInvoiceId);
//var_dump($iuguStatus);
//call gateway parameter api_token from gateway parameters
Iugu::setApiKey($gatewayParams["api_token"]);

//fetch data from the invoice id
$consultaFatura = Iugu_Invoice::fetch($iugu_invoice_id);
//var_dump($consultaFatura);
/**
 * @see http://php.net/manual/pt_BR/function.substr-replace.php
 */
$transactionId = $consultaFatura->id;
$paymentAmount = substr_replace($consultaFatura->total_cents, '.', -2, 0);
$paymentFee = substr_replace($consultaFatura->taxes_paid_cents , '.' , -2, 0);
$customVariables = $consultaFatura->custom_variables;
foreach ($customVariables as $key) {
  if ($key->name == 'invoice_id') {
    $invoiceId = $key->value;
  }
}
$currency = $consultaFatura->currency;
$discount = $consultaFatura->discount_cents;
$clientEmail = $consultaFatura->email;
$transactionStatus = $consultaFatura->status;
$updateAt = $consultaFatura->updated_at;
$paidAt = $consultaFatura->paid_at;
$commission = $consultaFatura->commission_cents;
$secureId = $consultaFatura->secure_id;
$costumerId = $consultaFatura->customer_id;

//$transactionStatus = $success ? 'Success' : 'Failure';

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 */
checkCbTransID($transactionId);




//verifica se o status da fatura é paga ou parcialmente paga
if ( $transactionStatus == 'paid' || $transactionStatus == 'partially_paid' ) {


  /**
   * Add Invoice Payment.
   *
   * Applies a payment transaction entry to the given invoice ID.
   *
   * @param int $invoiceId         Invoice ID
   * @param string $transactionId  Transaction ID
   * @param float $paymentAmount   Amount paid (defaults to full balance)
   * @param float $paymentFee      Payment fee (optional)
   * @param string $gatewayModule  Gateway module name
   */
  addInvoicePayment(
      $invoiceId,
      $transactionId,
      $paymentAmount,
      $paymentFee,
      $gatewayModuleName
  );

  /**
   * Log Transaction.
   *
   * Add an entry to the Gateway Log for debugging purposes.
   *
   * The debug data can be a string or an array. In the case of an
   * array it will be
   *
   * @param string $gatewayName        Display label
   * @param string|array $debugData    Data to log
   * @param string $transactionStatus  Status
   */
  logTransaction($gatewayParams['name'], $consultaFatura, $transactionStatus);

  echo json_encode( "Success" );

}else {
  echo json_encode( "Error" );
}
