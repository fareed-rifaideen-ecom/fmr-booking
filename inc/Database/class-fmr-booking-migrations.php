<?php
/**
 * The migration runner.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Database
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The migration runner.
 *
 * Handles the execution of database migrations and version tracking.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Database
 * @author     FMR
 */
class FMR_Booking_Migrations {

	/**
	 * Run all migrations.
	 *
	 * @since    1.0.0
	 */
	public static function run() {
		$installed_version = get_option( 'fmr_booking_db_version', '0.0.0' );

		if ( version_compare( $installed_version, FMR_BOOKING_VERSION, '<' ) ) {
			self::execute_schema_update();
			update_option( 'fmr_booking_db_version', FMR_BOOKING_VERSION );
		}
	}

	/**
	 * Execute schema update using dbDelta.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static function execute_schema_update() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-fmr-booking-schema.php';

		$queries = FMR_Booking_Schema::get_schema();

		foreach ( $queries as $sql ) {
			dbDelta( $sql );
		}
	}

}
