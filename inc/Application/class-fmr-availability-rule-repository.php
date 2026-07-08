<?php
/**
 * Repository for handling recurring schedules and holiday blockouts.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Repository for handling recurring schedules and holiday blockouts.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Availability_Rule_Repository {

	private $rules_table;
	private $blockouts_table;

	public function __construct() {
		global $wpdb;
		$this->rules_table     = $wpdb->prefix . 'fmr_availability_rules';
		$this->blockouts_table = $wpdb->prefix . 'fmr_blockouts';
	}

	/**
	 * Fetch all active schedule rules (global, service-level, and resource-level) for a specific day of the week.
	 * * @param int $client_id    The Client Profile ID.
	 * @param int $day_of_week  0 (Sunday) through 6 (Saturday).
	 * @return array            Array of schedule rules.
	 */
	public function get_schedule_for_day( $client_id, $day_of_week ) {
		global $wpdb;
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT target_type, target_id, start_time, end_time 
			 FROM {$this->rules_table} 
			 WHERE client_id = %d 
			 AND day_of_week = %d 
			 AND is_active = 1",
			$client_id,
			$day_of_week
		) );
	}

	/**
	 * Fetch all blockouts (holidays, sick leave, closures) that overlap with a specific date.
	 * * @param int    $client_id The Client Profile ID.
	 * @param string $date      The date to check (YYYY-MM-DD).
	 * @return array            Array of overlapping blockouts.
	 */
	public function get_blockouts_for_date( $client_id, $date ) {
		global $wpdb;
		
		$start_of_day = $date . ' 00:00:00';
		$end_of_day   = $date . ' 23:59:59';
		
		// Mathematical overlap check: Blockout Start <= Target End AND Blockout End >= Target Start
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT target_type, target_id, start_date, end_date, reason 
			 FROM {$this->blockouts_table} 
			 WHERE client_id = %d 
			 AND start_date <= %s 
			 AND end_date >= %s",
			$client_id,
			$end_of_day,
			$start_of_day
		) );
	}
}
