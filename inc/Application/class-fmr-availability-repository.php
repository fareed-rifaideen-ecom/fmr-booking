<?php
/**
 * Repository for handling resource availability and blockouts.
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
 * Repository for handling resource availability and blockouts.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Availability_Repository {

	/**
	 * Table name for reservations.
	 * @var string
	 */
	private $reservations_table;

	/**
	 * Table name for slot locks.
	 * @var string
	 */
	private $locks_table;

	public function __construct() {
		global $wpdb;
		$this->reservations_table = $wpdb->prefix . 'fmr_resource_reservations';
		$this->locks_table        = $wpdb->prefix . 'fmr_slot_locks';
	}

	/**
	 * [NEW] Fetch ALL reservations for given resources over a full day.
	 * Eliminates N+1 queries by grabbing the whole day in a single trip.
	 */
	public function get_daily_reservations( array $resource_ids, $date ) {
		if ( empty( $resource_ids ) ) {
			return array();
		}

		global $wpdb;
		
		// Safely handle dynamic IN() clauses in WordPress
		$placeholders = implode( ',', array_fill( 0, count( $resource_ids ), '%d' ) );
		
		$start_of_day = $date . ' 00:00:00';
		$end_of_day   = $date . ' 23:59:59';
		
		// Overlap math: Target Start < Range End AND Target End > Range Start
		$values = array_merge( $resource_ids, array( $end_of_day, $start_of_day ) );
		
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT id, resource_id, start_time, end_time 
			FROM {$this->reservations_table} 
			WHERE resource_id IN ($placeholders)
			AND start_time < %s 
			AND end_time > %s
		", $values ) );
	}

	/**
	 * [NEW] Fetch ALL active locks for a service over a full day.
	 */
	public function get_daily_locks( $service_id, $date ) {
		global $wpdb;
		
		$start_of_day = $date . ' 00:00:00';
		$end_of_day   = $date . ' 23:59:59';
		
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT id, session_id, start_time, expires_at 
			FROM {$this->locks_table} 
			WHERE service_id = %d
			AND start_time < %s 
			AND expires_at > %s
			AND expires_at > %s -- Ensure it hasn't expired relative to now
		", $service_id, $end_of_day, $start_of_day, current_time( 'mysql' ) ) );
	}

	/**
	 * Get all reservations for a specific resource within a time range.
	 * 🚨 FIX: Replaced SELECT * and mathematically simplified the overlap check.
	 */
	public function get_reservations( $resource_id, $start_time, $end_time ) {
		global $wpdb;
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT id, resource_id, start_time, end_time 
			 FROM {$this->reservations_table} 
			 WHERE resource_id = %d 
			 AND start_time < %s 
			 AND end_time > %s",
			$resource_id,
			$end_time,
			$start_time
		) );
	}

	/**
	 * Add a temporary slot lock.
	 */
	public function add_lock( $data ) {
		global $wpdb;
		
		$result = $wpdb->insert(
			$this->locks_table,
			array(
				'session_id' => sanitize_text_field( $data['session_id'] ),
				'service_id' => (int) $data['service_id'],
				'start_time' => sanitize_text_field( $data['start_time'] ),
				'expires_at' => sanitize_text_field( $data['expires_at'] ),
			),
			array( '%s', '%d', '%s', '%s' )
		);
		
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Clean up expired locks.
	 */
	public function cleanup_locks() {
		global $wpdb;
		
		return $wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$this->locks_table} WHERE expires_at < %s", 
			current_time( 'mysql' ) 
		) );
	}

	/**
	 * Get active locks for a service and time.
	 * 🚨 FIX: Replaced SELECT * with explicit column definitions.
	 */
	public function get_active_locks( $service_id, $start_time ) {
		global $wpdb;
		
		$this->cleanup_locks();
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT id, session_id, start_time, expires_at 
			 FROM {$this->locks_table} 
			 WHERE service_id = %d 
			 AND start_time = %s 
			 AND expires_at >= %s",
			$service_id,
			$start_time,
			current_time( 'mysql' )
		) );
	}
}
