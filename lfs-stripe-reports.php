<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name: LFS Stripe Reports
 * Description: View and download custom reports for payouts your organization has collected via Stripe.
 * Version: 1.4.4
 * Author: Lechoso Forestry Service
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lfs-stripe-reports
 * GitHub Plugin URI: bishless/lfs-stripe-reps
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

if ( ! defined('LFS_ENV') ) {
	define( 'LFS_ENV', 'prod' );
}

$lfs_plugin = get_plugin_data( __FILE__ );
$tlds = array( ".com", ".org", ".net", ".dev", ".test" );
$url = get_option( 'siteurl' );
$info = parse_url( $url );
$hostname = $info['host'];
$host = str_replace( $tlds, "", $hostname );

// Defines the path to the main plugin file.
define( 'LFSSR_FILE', __FILE__ );

// Defines the path to be used for includes.
define( 'LFSSR_DIR', plugin_dir_path( LFSSR_FILE ) );
define( 'LFSSR_PATH', plugin_dir_path( LFSSR_FILE ) );

// Defines the URL to the plugin.
define( 'LFSSR_URL', plugin_dir_url( LFSSR_FILE ) );

// Defines the current version of the plugin.
define( 'LFSSR_VER', $lfs_plugin['Version'] );

define( 'LFSSR_CAP', 'stripe_reports' );
define( 'LFSSR_STRIPETESTKEY', 'sk_test_4eC39HqLyjWDarjtT1zdp7dc' );
define( 'LFSSR_STRIPETESTVER', '2017-02-14' );

require_once( LFSSR_DIR . '/vendor/autoload.php' );
require_once( LFSSR_DIR . '/inc/general.php' );



function lfssr_add_styles() {
	global $lfs_plugin;
	$screen = get_current_screen();
	if ( 'dashboard' === $screen->id ) {
		wp_enqueue_style( 'lfssr', LFSSR_URL . 'assets/lechoso.min.css' );
	}
	if ( 'toplevel_page_lfs-stripe-reports' === $screen->id ) {
		wp_enqueue_script( 'lfssr-script', LFSSR_URL . 'assets/lfs-stripe-reports.min.js', array(), $lfs_plugin['Version'], true );
		wp_enqueue_style( 'lfssr', LFSSR_URL . 'assets/lechoso.min.css' );
	}
}





\Stripe\Stripe::setAppInfo( "LFSStripeReports", LFSSR_VER );

$stripe_options = get_option( 'lfssr_stripe' );

if ( !get_option( 'lfssr_stripe' ) ) {
	$plugin_needs_settings = 1;
	$payout_table_classes = 'lechoso table--payouts plugin-needs-settings';
} else {
	$plugin_needs_settings = 0;
	$payout_table_classes = 'lechoso table--payouts on-light-background-color';
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
// if ( get_option( 'timezone_string' ) ) {
// 	date_default_timezone_set( get_option( 'timezone_string' ) );
// }

setlocale( LC_MONETARY, 'en_US' );



if ( is_admin() ) {
	add_action( 'admin_init', 'lfssr_initialize_plugin_options' );
	add_action( 'admin_menu', 'lfssr_create_menu_page' );
	add_action( 'admin_enqueue_scripts', 'lfssr_add_styles' );
	add_action( 'wp_dashboard_setup', 'lfssr_dashboard_widget');
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

	add_settings_field(
		'lfssr_stripe_report_count',
		'Stripe Reports to Retrieve',
		'lfssr_setting_reportcount',
		'lfs-stripe-reports-settings',
		'lfssr_main_section',
		array(
			'label_for' => 'lfssr_stripe_report_count'
		)
	);

	register_setting( 'lfssr_options', 'lfssr_stripe', 'lfssr_options_validate' );

} // end lfssr_initialize_plugin_options


function lfssr_create_menu_page() {

	add_menu_page(
		'Stripe Reports',
		'Stripe Reports',
		LFSSR_CAP, // should this be a new custom role?
		'lfs-stripe-reports', // menu
		'lfssr_menu_page_display',
		LFSSR_URL . 'assets/logomark__stripe-reps.min.svg'
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
		'lfssr_options_page_display' // function
	);

	// add_submenu_page(
	// 	'lfs-stripe-reports',
	// 	'Test File Creation',
	// 	'Write Test',
	// 	$capability,
	// 	'lfs-stripe-write-test',
	// 	'lfssr_write_page_display'
	// );
}


if ( !function_exists( 'lfssr_render_payout_rows' ) ) {

	function lfssr_render_payout_rows( $limit ) {
		global $stripe_options, $host;

		/**
		 * Actual Stripe Payout Table rendering...
		 */

		// Query the API for the Payout's charges
		try {
			$get_recent_payouts = \Stripe\Payout::all(array(
				"limit" => $limit
			));
		} catch (\Stripe\Error\ApiConnection $e) {
			echo "Network problem, perhaps try again. ($e)";
		} catch (\Stripe\Error\InvalidRequest $e) {
			echo "You screwed up in your programming. Shouldn't happen! ($e)";
		} catch (\Stripe\Error\Api $e) {
			echo "Stripe's servers are down! Holy. Crap. ($e)";
		}
		// $get_recent_payouts = \Stripe\Payout::all( array( "limit" => 5 ) );

		// $stripe_fields = '<input type="hidden" name="api" value="' . $stripe_options['lfssr_stripe_api_key'] . '"><input type="hidden" name="ver" value="' . $stripe_options['lfssr_stripe_api_ver'] . '" />';

		foreach ( $get_recent_payouts->data as $porow ) {
			$prerow = '<tr><td class="fake-border" colspan="5"></td></tr>';
			$trow = '<tr class="payout-row status-' . $porow->status . '" id="' . $porow->id . '">';
			$trow .= '<td data-title="Status"><span class="pill__status--' . $porow->status . '">' . $porow->status . '</td>';
			$trow .= '<td data-title="Date">' . gmdate( 'm/d/Y', $porow->arrival_date ) . '</td>';
			$trow .= '<td data-title="Amount">' . money_format( '%n', $porow->amount/100 ) . '</td>';
			$trow .= '<td data-title="Details"><span class="js-payout-id-short">&hellip;'.lfssr_display_last_five( $porow->id ).'</span></td>';
			$trow .= '<td data-title="Download"><span class="link--csv"><a class="button" href="' . LFSSR_URL . 'dl-csv.php?pid=' . $porow->id . '&co=' . $host . '&a=' . $stripe_options['lfssr_stripe_api_key'] .'&v=' . $stripe_options['lfssr_stripe_api_ver'] .'" title="Download CSV">Download</a></span></td>';
			// $trow .= '<td data-title="Download"><form id="dl_csv--' . $porow->id . '" method="post" action=""><input type="hidden" name="pid" value="' . $porow->id . '"><input type="hidden" name="co" value="' . $host . '" />' . $stripe_fields . '<span class="link--csv"><input type="submit" name="dl" value="Download CSV" /></span></form></td>';
			$trow .= '</tr>';

			echo $trow;
			echo $prerow;
		}
	}
}

if ( !function_exists( 'lfssr_render_mini_payout_rows' ) ) {

	function lfssr_render_mini_payout_rows( $limit ) {
		global $stripe_options, $host;

		/**
		 * Actual Stripe Payout Table rendering...
		 */

		// Query the API for the Payout's charges
		try {
			$get_recent_payouts = \Stripe\Payout::all(array(
				"limit" => $limit
			));
		} catch (\Stripe\Error\ApiConnection $e) {
			echo "Network problem, perhaps try again. ($e)";
		} catch (\Stripe\Error\InvalidRequest $e) {
			echo "You screwed up in your programming. Shouldn't happen! ($e)";
		} catch (\Stripe\Error\Api $e) {
			echo "Stripe's servers are down! Holy. Crap. ($e)";
		}
		// $get_recent_payouts = \Stripe\Payout::all( array( "limit" => 5 ) );

		// $stripe_fields = '<input type="hidden" name="api" value="' . $stripe_options['lfssr_stripe_api_key'] . '"><input type="hidden" name="ver" value="' . $stripe_options['lfssr_stripe_api_ver'] . '" />';

		foreach ( $get_recent_payouts->data as &$porow ) {
			$prerow = '<tr><td class="fake-border" colspan="4"></td></tr>';
			$trow = '<tr class="payout-row status-' . $porow->status . '" id="' . $porow->id . '">';
			$trow .= '<td><span class="pill__status--' . $porow->status . '">' . $porow->status . '</td>';
			$trow .= '<td><span title="'.gmdate( 'm/d/Y', $porow->arrival_date ).'">' . gmdate( 'm/d', $porow->arrival_date ) . '</span></td>';
			$trow .= '<td>' . money_format( '%n', $porow->amount/100 ) . '</td>';
			$trow .= '<td><span class="link--csv"><a class="button" href="' . LFSSR_URL . 'dl-csv.php?pid=' . $porow->id . '&co=' . $host . '&a=' . $stripe_options['lfssr_stripe_api_key'] .'&v=' . $stripe_options['lfssr_stripe_api_ver'] .'" title="Download CSV">&hellip;'.lfssr_display_last_five( $porow->id ).'</a></span></td>';
			$trow .= '</tr>';
			if( $porow->status != 'in_transit' ) {
				echo $trow;
				echo $prerow;
			}
		}
	}
}



// Page: Stripe Reports
//
function lfssr_menu_page_display() {
	global $lfs_plugin, $stripe_options, $plugin_needs_settings, $payout_table_classes;

	if ( !current_user_can( LFSSR_CAP ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>

	<div class="wrap">
		<div class="lfs-page-wrapper">
			<div class="lfs-page-content">
				<section>
				<h1 id="lfssr-title"><?php esc_attr_e( 'Stripe Reports', 'lfs_stripe_reports' ); ?></h1>
				<div id="lfssr-error-wrap"></div>
				<div id="lfssr-main">
					<?php if ( $plugin_needs_settings == 1 ) {
						echo '<p>The table below shows simulated payouts. Please <a href="admin.php?page=lfs-stripe-reports-settings">set your API Key and Version</a> to view your live data.</p>';
					} else {
						echo '<p><strong>Your ' . $stripe_options['lfssr_stripe_report_count'] . ' most recent Payouts</strong></p>';
					} ?>
					<table id="js_target_table" class="<?php echo $payout_table_classes; ?>">
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
							<?php if ( $stripe_options['lfssr_stripe_report_count'] ) {
								lfssr_render_payout_rows( $stripe_options['lfssr_stripe_report_count'] );
							} else {
								lfssr_render_payout_rows( 5 );
							} ?>
							<tr class="micro-row">
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<td class="text-center" colspan="5">
									<button id="js_theme_toggle">dark/light</button>
								</td>
							</tr>
						</tfoot>
					</table>
					<p class="text-center"><img class="" src="<?php echo LFSSR_URL .'assets/powered_by_stripe@2x.png'; ?>" alt="Powered by Stripe" width="119" /></p>
				</div>
				</section>

				<section>
				<h2>Handy Dandy Links</h2>
				<p><a href="admin.php?page=lfs-stripe-reports-settings">Settings</a> | <a href="https://dashboard.stripe.com/">Stripe Dashboard</a></p>
				</section>

				<?php if( LFS_ENV === 'dev' ) { ?>
					<section class="debug">
						<?php $format_test = 1234.56; ?>
						<h3>Debug</h3>
						<pre>LFS_ENV: <span class="val"><?php echo LFS_ENV; ?></span></pre>
						<!-- <pre>LFSSR_REPORTCOUNT: <span class="val"><?php // echo LFSSR_REPORTCOUNT; ?></span></pre>
						<pre>LFSSR_REPORTCOUNT defined? ... <span class="val"><?php // echo defined( 'LFSSR_REPORTCOUNT' ); ?></span></pre> -->
						<pre>report count: <span class="val"><?php echo $stripe_options['lfssr_stripe_report_count']; ?></span>
						<pre>money format number: <span class="val"><?php echo $format_test; ?></span></pre>
						<pre>money format: <span class="val"><?php echo money_format( '%n', $format_test ); ?></span></pre>
						<pre>tz: <span class="val"><?php echo get_option( 'timezone_string' ); ?></span></pre>
						<pre>curr_tz: <span class="val"><?php echo date_default_timezone_get(); ?></span></pre>
						<pre><?php // lfssr_render_payout_rows(); ?></pre>
						<pre><?php // echo $get_recent_payouts; ?></pre>
						<pre>get_defined_vars(): <span class="val"><?php print_r( get_defined_vars() ); ?></span></pre>
					</section>
				<?php } ?>

				<p class="footer"><span><?php echo 'Plugin Version: '.$lfs_plugin['Version'].'. &copy;'.date( 'Y' ).' '.$lfs_plugin['Author'].'.'; ?></span></p>
			</div><!-- /.lfs-page-content -->
		</div><!-- /.lfs-page-wrapper -->
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
				<tr valign="top">
					<th scope="row"><?php echo __( 'How many reports to retrieve?', 'lfs-stripe-reports' ); ?></th>
					<td>
						<input type="number" name="lfssr_stripe[lfssr_stripe_report_count]" id="lfssr_stripe[lfssr_stripe_report_count]" min="5" max="30" step="5" value="<?php echo $stripe_options['lfssr_stripe_report_count']; ?>">
						<p class="description"><?php echo __( 'Lower numbers will provide faster performance.', 'lfs-stripe-reports' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div> <?php
}



add_action( 'wp_dashboard_setup', 'lfssr_dashboard_widget');
function lfssr_dashboard_widget() {
	// global $wp_meta_boxes;

	// wp_add_dashboard_widget(
	add_meta_box(
		'lfssr_dashboard_widget_latest_payout',
		__( 'Stripe Reports: Latest Payout', 'lfs-stripe-reports' ),
		'lfssr_dashboard_widget_latest_payout_handler',
		'dashboard',
		'side',
		'high'
	);
}

function lfssr_dashboard_widget_latest_payout_handler() {
	global $payout_table_classes, $lfs_plugin, $stripe_options;
	// TODO: add warning if no settings are set...
	?>


	<table class="<?php echo $payout_table_classes; ?> lfs-dashboard-table">
		<tbody>
			<?php lfssr_render_mini_payout_rows(2); ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4" class="text-center"><a class="button" href="admin.php?page=lfs-stripe-reports">View Latest <?php echo $stripe_options['lfssr_stripe_report_count']; ?></a></td>
			</tr>
		</tfoot>
	</table>
	<p class="text-center"><img src="<?php echo LFSSR_URL .'/assets/powered_by_stripe@2x.png'; ?>" alt="Powered by Stripe" width="119" /></p>
	<!-- <p><img style="float:left;margin:0 8px 8px 0" src="<?php // echo LFSSR_URL .'/assets/icon-128x128.png'; ?>" alt="LFS Stripe Reports logo" width="50" height="50">Note: .</p> -->
	<p class="dashboard-footer"><em><?php echo 'Plugin Version: '.$lfs_plugin['Version']; ?></em></p>
	<?php
}


// Sanitize and validate input. Accepts an array, returns a sanitized array
function lfssr_options_validate( $input ) {

	$input['lfssr_stripe_api_key'] = wp_filter_nohtml_kses( $input['lfssr_stripe_api_key'] );
	$input['lfssr_stripe_api_ver'] = wp_filter_nohtml_kses( $input['lfssr_stripe_api_ver'] );

	return $input;

}
