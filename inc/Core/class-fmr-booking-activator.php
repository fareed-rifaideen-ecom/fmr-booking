<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 * @author     FMR
 */
class FMR_Booking_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Run database migrations
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Database/class-fmr-booking-migrations.php';
		FMR_Booking_Migrations::run();

		// Schedule cron events
		if ( ! wp_next_scheduled( 'fmr_process_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'fmr_process_reminders' );
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

}
