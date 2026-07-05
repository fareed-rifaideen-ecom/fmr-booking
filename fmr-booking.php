<?php
/**
 * Plugin Name:       FMR Booking
 * Plugin URI:        https://fmr.com/fmr-booking
 * Description:       A secure, reusable, white-label WordPress booking plugin for client profiles, service rules, and branding presets.
 * Version:           1.0.0
 * Author:            FMR
 * Author URI:        https://fmr.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fmr-booking
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'FMR_BOOKING_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'inc/Core/class-fmr-booking.php';

/**
 * Begins execution of the plugin.
 */
function run_fmr_booking() {
	$plugin = new FMR_Booking();
	$plugin->run();
}

/**
 * The code that runs during plugin activation.
 */
register_activation_hook( __FILE__, 'activate_fmr_booking' );
function activate_fmr_booking() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/Core/class-fmr-booking-activator.php';
	FMR_Booking_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
register_deactivation_hook( __FILE__, 'deactivate_fmr_booking' );
function deactivate_fmr_booking() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/Core/class-fmr-booking-deactivator.php';
	FMR_Booking_Deactivator::deactivate();
}

run_fmr_booking();
