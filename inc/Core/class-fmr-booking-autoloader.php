<?php
/**
 * Plugin autoloader.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin autoloader.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 * @author     FMR
 */
class FMR_Booking_Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes.
	 */
	public static function autoload( $class ) {
		if ( strpos( $class, 'FMR_' ) !== 0 ) {
			return;
		}

		$parts = explode( '_', $class );
		
		// Remove 'FMR'
		array_shift( $parts );
		
		if ( empty( $parts ) ) {
			return;
		}

		// Handle sub-packages based on naming convention
		// FMR_Booking -> inc/Core/class-fmr-booking.php
		// FMR_Booking_Loader -> inc/Core/class-fmr-booking-loader.php
		// FMR_Client_Repository -> inc/Application/class-fmr-client-repository.php
		
		$filename = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$base_dir = plugin_dir_path( __FILE__ ) . '../';
		
		$subdirs = array(
			'Core',
			'Application',
			'Database',
			'Admin',
			'Frontend',
			'Integrations',
			'Cron',
			'Support'
		);

		foreach ( $subdirs as $subdir ) {
			$path = $base_dir . $subdir . '/' . $filename;
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
