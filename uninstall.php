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
 * - Drop custom tables
 * - Clear scheduled tasks
 */

// Delete options
delete_option( 'fmr_booking_db_version' );
delete_option( 'fmr_booking_default_product_id' );

// Clear scheduled tasks
wp_clear_scheduled_hook( 'fmr_process_reminders' );

// Drop custom tables with hardening
global $wpdb;
$allowed_tables = array(
	'fmr_client_profiles',
	'fmr_branding_presets',
	'fmr_services',
	'fmr_resources',
	'fmr_service_resource_rules',
	'fmr_appointments',
	'fmr_resource_reservations',
	'fmr_slot_locks',
	'fmr_reminder_queue',
	'fmr_notification_logs',
	'fmr_approval_requests',
	'fmr_activity_logs',
	'fmr_attachments',
);

foreach ( $allowed_tables as $table ) {
	// Securely construct the table name
	$table_name = $wpdb->prefix . $table;
	
	// Final validation before execution
	if ( preg_match( '/^[a-zA-Z0-9_]+$/', $table_name ) ) {
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}
}
