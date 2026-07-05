<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Perform cleanup:
 * - Delete options
 * - Drop custom tables (optional, usually better to keep data unless requested)
 * - Clear scheduled tasks
 */

// Example: delete_option( 'fmr_booking_settings' );

// For custom tables, we will handle them in later phases when the schema is defined.
