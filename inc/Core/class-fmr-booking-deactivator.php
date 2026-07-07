<?php
/**
 * Fired during plugin deactivation.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 * @author     FMR
 */
class FMR_Booking_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear scheduled cron events
		wp_clear_scheduled_hook( 'fmr_process_reminders' );
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

}
