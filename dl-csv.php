<?php

$pid = $_GET['pid'];
$prefix = $_GET['co'];
$api = $_GET['a'];
$ver = $_GET['v'];

$filename = $prefix. '-charges-in-' . $pid . '.csv';
header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename='.$filename );
// Disable caching - HTTP 1.1
header("Cache-Control: no-cache, no-store, must-revalidate");
// Disable caching - HTTP 1.0
header("Pragma: no-cache");
// Disable caching - Proxies
header("Expires: 0");

use Stripe\SKU;
use Stripe\Refund;
use Stripe\Payout;
use Stripe\BalanceTransaction;
require_once( 'vendor/autoload.php' );

\Stripe\Stripe::setApiKey( $api );
\Stripe\Stripe::setApiVersion( $ver );

// setlocale( LC_MONETARY, 'en_US' );


if ($_GET['pid']) {

	$csv = fopen( 'php://output', 'w' );

	$headers = array(
		'Post Date',
		'Amount',
		'Description',
		'Type',
		'Metadata'
	);

	fputcsv( $csv, $headers );

	$trid = $_GET['pid'];

	// Create empty arrays to populate with the Payout's charges and refunds
	$payout_charges = array();
	$payout_refunds = array();

	// Query the API for the Payout's charges
	$get_charges_in_payout = \Stripe\BalanceTransaction::all(array(
		"limit" => 50,
		"payout" => $trid,
		"type" => "charge"
	));
	// Query the API for the Payout's refunds (if any)
	$get_refunds_in_payout = \Stripe\BalanceTransaction::all(array(
		"limit" => 50,
		"payout" => $trid,
		"type" => "refund"
	));

	// For each Charge in the Payout object, add the Charge ID to the array from earlier
	foreach ( $get_charges_in_payout->data as $charge) {
		$payout_charges[] = $charge->source;
	}
	// For each Refund in the Payout object, add the Refund ID to the array from earlier
	foreach ( $get_refunds_in_payout->data as $refund ) {
		$payout_refunds[] = $refund->source;
	}

	// Render the CSV row for each charge
	foreach ( $payout_charges as $xferchrg ) {
		// Query the API for the Charge with Metadata
		$c = \Stripe\Charge::retrieve(array(
			"id" => $xferchrg,
			"expand" => array("balance_transaction")
		));
		$id = $c->id;
		$created = gmdate( 'm/d/Y', $c->created );
		$amount = money_format( '%n', $c->balance_transaction->net/100 );
		$desc = $c->description;
		$type = "Stripe";
		$name = $c->source->name;
		// $meta = json_encode( $c->metadata, JSON_PRETTY_PRINT );
		// via: https://stackoverflow.com/questions/7462394/php-json-string-escape-double-quotes-for-js-output
		$meta = str_replace("\u0022","\\\\\"",json_encode( $c->metadata,JSON_HEX_QUOT));
		$text = $name.' (' . $meta . ') '.$id;
		$chrow = array(
			$created,
			$amount,
			$desc,
			$type,
			$text
		);
		fputcsv( $csv, $chrow );
	}

	$payout_refunds = array_filter( $payout_refunds );
	if ( !empty( $payout_refunds )) {
		foreach ( $payout_refunds as $payoutref ) {
			$r = \Stripe\Refund::retrieve(array(
				"id" => $payoutref,
				"expand" => array("balance_transaction")
			));
			$id = $r->id;
			$created = gmdate( 'm/d/Y', $r->created );
			$amount = money_format( '%n', $r->balance_transaction->net/100 );
			$desc = "Refund";
			$type = "Stripe";
			$text = $r->balance_transaction->description;
			$refrow = array(
				$created,
				$amount,
				$desc,
				$type,
				$text
			);
			fputcsv( $csv, $refrow );
		}
	}

	fclose($csv);

	exit;

} else {
	exit;
}
?>
