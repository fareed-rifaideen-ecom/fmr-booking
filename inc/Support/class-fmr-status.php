<?php
/**
 * Support class for booking statuses.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Support
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Support class for booking statuses.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Support
 * @author     FMR
 */
class FMR_Status {

	const PENDING      = 'pending';
	const APPROVED     = 'approved';
	const CANCELLED    = 'cancelled';
	const COMPLETED    = 'completed';
	const RESCHEDULED  = 'rescheduled';

	/**
	 * Get all allowed statuses.
	 *
	 * @return array List of statuses.
	 */
	public static function get_all() {
		return array(
			self::PENDING,
			self::APPROVED,
			self::CANCELLED,
			self::COMPLETED,
			self::RESCHEDULED,
		);
	}

	/**
	 * Validate a status.
	 *
	 * @param string $status Status to validate.
	 * @return bool True if valid.
	 */
	public static function is_valid( $status ) {
		return in_array( $status, self::get_all(), true );
	}
}
