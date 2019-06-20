<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name: LFS Stripe Reports
 * Description: View and download custom reports for payouts your organization has collected via Stripe.
 * Version: 1.1.0
 * Author: Lechoso Forestry Service
 * Author URI: https://lechoso.xyz/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lfs-stripe-reports
 * GitHub Plugin URI: bishless/lfs-stripe-reports
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	 die;
}

// Defines the path to the main plugin file.
define( 'LFSSR_FILE', __FILE__ );

// Defines the path to be used for includes.
define( 'LFSSR_DIR', plugin_dir_path( LFSSR_FILE ) );
define( 'LFSSR_PATH', plugin_dir_path( LFSSR_FILE ) );

// Defines the URL to the plugin.
define( 'LFSSR_URL', plugin_dir_url( LFSSR_FILE ) );

// Defines the current version of the plugin.
define( 'LFSSR_VER', '1.1.0' );

define( 'LFSSR_CAP', 'stripe_reports' );
define( 'LFSSR_STRIPETESTKEY', 'sk_test_4eC39HqLyjWDarjtT1zdp7dc' );
define( 'LFSSR_STRIPETESTVER', '2017-02-14' );

require_once( LFSSR_DIR . '/vendor/autoload.php' );
require_once( LFSSR_DIR . '/inc/general.php' );


function lfssr_add_styles() {
	wp_enqueue_style( 'lfs', LFSSR_URL . '/assets/lechoso.css' );
}





\Stripe\Stripe::setAppInfo( "LFSStripeReports", LFSSR_VER );

$stripe_options = get_option( 'lfssr_stripe' );

if ( !get_option( 'lfssr_stripe' ) ) {
	$plugin_needs_settings = 1;
	$payout_table_classes = 'table--payouts plugin-needs-settings';
} else {
	$plugin_needs_settings = 0;
	$payout_table_classes = 'table--payouts';
}

if ( $stripe_options['lfssr_stripe_api_key'] ) {
	\Stripe\Stripe::setApiKey( $stripe_options['lfssr_stripe_api_key'] );
} else {
	\Stripe\Stripe::setApiKey( LFSSR_STRIPETESTKEY ); // Test key from API Docs
}

if ( $stripe_options['lfssr_stripe_api_ver'] ) {
	\Stripe\Stripe::setApiVersion( $stripe_options['lfssr_stripe_api_ver'] );
} else {
	\Stripe\Stripe::setApiVersion( LFSSR_STRIPETESTVER );
}

// date_default_timezone_set( 'America/Phoenix' );
if ( get_option( 'timezone_string' ) ) {
	date_default_timezone_set( get_option( 'timezone_string' ) );
}

setlocale( LC_MONETARY, 'en_US' );



if ( is_admin() ) {
	add_action( 'admin_init', 'lfssr_initialize_plugin_options' );
	add_action( 'admin_menu', 'lfssr_create_menu_page' );
	add_action( 'admin_enqueue_scripts', 'lfssr_add_styles' );
} else {
	// non-admin
}



function lfssr_initialize_plugin_options() {

	// First, we register a section. This is necessary since all future options must belong to one.
	add_settings_section(
		'lfssr_main_section',         		// ID used to identify this section and with which to register options
		'Stripe Reports Settings',      	// Title to be displayed on the administration page
		'lfssr_main_section_callback',		// Callback used to render the description of the section
		'lfs-stripe-reports-settings'		// Page on which to add this section of options
	);

	add_settings_field(
		'lfssr_stripe_api_key', 			// ID used to identify the field throughout the theme
		'Stripe API Key', 					// The label to the left of the option interface element
		'lfssr_setting_apikey', 			// The name of the function responsible for rendering the option interface
		'lfs-stripe-reports-settings', 		// The page on which this option will be displayed
		'lfssr_main_section',				// The name of the section to which this field belongs
		array(
			'label_for' => 'lfssr_stripe_api_key'
		)
	);

	add_settings_field(
		'lfssr_stripe_api_ver', 			// ID used to identify the field throughout the theme
		'Stripe API Key', 					// The label to the left of the option interface element
		'lfssr_setting_apiver', 			// The name of the function responsible for rendering the option interface
		'lfs-stripe-reports-settings', 		// The page on which this option will be displayed
		'lfssr_main_section',				// The name of the section to which this field belongs
		array(
			'label_for' => 'lfssr_stripe_api_ver'
		)
	);

	register_setting( 'lfssr_options', 'lfssr_stripe', 'lfssr_options_validate' );

} // end lfssr_initialize_plugin_options


function lfssr_create_menu_page() {

	// menu icon
	$svg = '<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M8.383 9.467c0-.643.554-.89 1.472-.89 1.317 0 2.98.379 4.296 1.055V5.758C12.713 5.214 11.293 5 9.855 5 6.34 5 4 6.747 4 9.665c0 4.55 6.583 3.824 6.583 5.786 0 .758-.693 1.005-1.663 1.005-1.438 0-3.274-.56-4.73-1.319v3.923c1.612.66 3.24.94 4.73.94 3.603 0 6.08-1.698 6.08-4.648-.017-4.912-6.617-4.039-6.617-5.885z" fill="#9FA3A8"/><path d="M16.778 3.222A10.966 10.966 0 0 0 9 0h11l-3.222 3.222z" fill="#CE182D"/><path d="M20 11c0-3.038-1.231-5.788-3.222-7.778L20 0v11z" fill="#940418"/></g></svg>';

	add_menu_page(
		'Stripe Reports',
		'Stripe Reports',
		LFSSR_CAP, // should this be a new custom role?
		'lfs-stripe-reports', // menu
		'lfssr_menu_page_display',
		'data:image/svg+xml;base64,' . base64_encode( $svg ),
	);
	add_submenu_page(
		'lfs-stripe-reports', // parent slug
		'Stripe Reports', // page title
		'Stripe Reports', // menu title
		LFSSR_CAP, // role
		'lfs-stripe-reports' // menu slug
	);
	// need another page for payout details that's not shown in the menu...
	add_submenu_page(
		'lfs-stripe-reports',
		'Stripe Reports Settings',
		'Settings',
		LFSSR_CAP, // role
		'lfs-stripe-reports-settings', // menu slug
		'lfssr_options_page_display', // function
	);

	// add_submenu_page(
	// 	'lfs-stripe-reports',
	// 	'Test File Creation',
	// 	'Write Test',
	// 	$capability,
	// 	'lfs-stripe-write-test',
	// 	'lfssr_write_page_display',
	// );
}


if ( !function_exists( 'lfssr_render_payout_rows' ) ) {

	function lfssr_render_payout_rows() {
		global $stripe_options;

		/**
		 * Actual Stripe Payout Table rendering...
		 */

		// Query the API for the Payout's charges
		try {
			$get_recent_payouts = \Stripe\Payout::all(array(
				"limit" => 5
			));
		} catch (\Stripe\Error\ApiConnection $e) {
			echo "Network problem, perhaps try again. ($e)";
		} catch (\Stripe\Error\InvalidRequest $e) {
			echo "You screwed up in your programming. Shouldn't happen! ($e)";
		} catch (\Stripe\Error\Api $e) {
			echo "Stripe's servers are down! Holy. Crap. ($e)";
		}
		// $get_recent_payouts = \Stripe\Payout::all( array( "limit" => 5 ) );

		$tlds = array( ".com", ".org", ".net", ".dev", ".test" );
		$url = get_option( 'siteurl' );
		$info = parse_url( $url );
		$hostname = $info['host'];
		$host = str_replace( $tlds, "", $hostname );
		$stripe_fields = '<input type="hidden" name="api" value="' . $stripe_options['lfssr_stripe_api_key'] . '"><input type="hidden" name="ver" value="' . $stripe_options['lfssr_stripe_api_ver'] . '" />';

		foreach ( $get_recent_payouts->data as $porow ) {
			$trow = '<tr class="payout-row status-' . $porow->status . '" id="' . $porow->id . '">';
			$trow .= '<td data-title="Status"><span class="status-' . $porow->status . '">' . $porow->status . '</td>';
			$trow .= '<td data-title="Date">' . gmdate( 'm/d/Y', $porow->arrival_date ) . '</td>';
			$trow .= '<td data-title="Amount">' . money_format( '%n', $porow->amount/100 ) . '</td>';
			$trow .= '<td data-title="Details"><span class="js-payout-id-short">&hellip;'.lfssr_display_last_five( $porow->id ).'</span></td>';
			$trow .= '<td data-title="Download"><span class="link--csv"><a class="button" href="' . LFSSR_URL . 'dl-csv.php?pid=' . $porow->id . '&co=' . $host . '&a=' . $stripe_options['lfssr_stripe_api_key'] .'&v=' . $stripe_options['lfssr_stripe_api_ver'] .'" title="Download CSV">Download CSV</a></span></td>';
			// $trow .= '<td data-title="Download"><form id="dl_csv--' . $porow->id . '" method="post" action=""><input type="hidden" name="pid" value="' . $porow->id . '"><input type="hidden" name="co" value="' . $host . '" />' . $stripe_fields . '<span class="link--csv"><input type="submit" name="dl" value="Download CSV" /></span></form></td>';
			$trow .= '</tr>';
			echo $trow;
		}
	}
}



// Page: Stripe Reports
//
function lfssr_menu_page_display() {
	global $stripe_options, $plugin_needs_settings, $payout_table_classes;

	if ( !current_user_can( LFSSR_CAP ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>

	<div class="wrap">
		<h1 id="lfssr-title"><?php esc_attr_e( 'Stripe Reports', 'lfs_stripe_reports' ); ?></h1>
		<div id="lfssr-error-wrap"></div>
		<div id="lfssr-main">
			<?php if ( $plugin_needs_settings == 1 ) {
				echo '<p>The table below shows simulated payouts. Please <a href="admin.php?page=lfs-stripe-reports-settings">set your API Key and Version</a> to view your live data.</p>';
			} else {
				echo '<p><strong>Your 5 most recent Payouts</strong></p>';
			} ?>
			<table class="<?php echo $payout_table_classes; ?>">
				<thead>
					<tr class="head-row">
						<th scope="col" class="manage-column column-status">Status</th>
						<th scope="col" class="manage-column column-date">Date</th>
						<th scope="col" class=" column-amount num">Amount</th>
						<th scope="col" class=" column-id">Payout ID</th>
						<th scope="col" class="manage-column column-download">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<?php lfssr_render_payout_rows(); ?>
					<tr class="micro-row">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
				</tbody>
			</table>

			<img class="powered-by" src="<?php echo LFSSR_URL . '/assets/powered_by_stripe@2x.png'; ?>" alt="Powered by Stripe" width="119" />
		</div>


		<br class="clear" />
		<h3>Helpful Links</h3>
		<p><a href="admin.php?page=lfs-stripe-reports-settings">Settings</a> | <a href="https://dashboard.stripe.com/">Stripe Dashboard</a></p>

		<br class="clear" />
		<?php if( LFS_ENV === 'dev' ) { ?>
			<div class="debug">
				<h4>Debug</h4>
				<pre>$plugin_needs_settings: <?php echo $plugin_needs_settings; ?></pre>
				<pre>key: <?php echo $stripe_options['lfssr_stripe_api_key']; ?></pre>
				<pre>ver: <?php echo $stripe_options['lfssr_stripe_api_ver']; ?></pre>
				<pre>tz: <?php echo get_option( 'timezone_string' ); ?></pre>
				<pre>curr_tz: <?php echo date_default_timezone_get(); ?></pre>
				<pre><?php // lfssr_render_payout_rows(); ?></pre>
				<pre><?php // echo $get_recent_payouts; ?></pre>
			</div>
		<?php } ?>
	</div>

	<?php
}


// Page: Stripe Reports Settings
//
function lfssr_options_page_display() {
	global $stripe_options;

	if ( !current_user_can( LFSSR_CAP ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	} ?>
	<div class="wrap">
		<h1>Stripe Reports Settings</h1>
		<p>Provide your 'Live' API key and version from your Stripe Dashboard.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'lfssr_options' ); ?>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php echo __( 'Stripe API Key', 'lfs-stripe-reports' ); ?></th>
					<td><input class="regular-text" type="text" name="lfssr_stripe[lfssr_stripe_api_key]" id="lfssr_stripe[lfssr_stripe_api_key]" value="<?php echo $stripe_options['lfssr_stripe_api_key']; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php echo __( 'Stripe API Version', 'lfs-stripe-reports' ); ?></th>
					<td><input class="regular-text" type="text" name="lfssr_stripe[lfssr_stripe_api_ver]" id="lfssr_stripe[lfssr_stripe_api_ver]" value="<?php echo $stripe_options['lfssr_stripe_api_ver']; ?>" /></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div> <?php
}



function lfssr_write_page_display() {}



// Sanitize and validate input. Accepts an array, returns a sanitized array
function lfssr_options_validate( $input ) {

	$input['lfssr_stripe_api_key'] = wp_filter_nohtml_kses( $input['lfssr_stripe_api_key'] );
	$input['lfssr_stripe_api_ver'] = wp_filter_nohtml_kses( $input['lfssr_stripe_api_ver'] );

	return $input;

}
