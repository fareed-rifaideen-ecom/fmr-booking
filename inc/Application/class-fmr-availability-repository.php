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
	 *
	 * @var string
	 */
	private $reservations_table;

	/**
	 * Table name for slot locks.
	 *
	 * @var string
	 */
	private $locks_table;

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		global $wpdb;
		$this->reservations_table = $wpdb->prefix . 'fmr_resource_reservations';
		$this->locks_table        = $wpdb->prefix . 'fmr_slot_locks';
	}

	/**
	 * Get all reservations for a resource within a time range.
	 *
	 * @param int    $resource_id Resource ID.
	 * @param string $start_time  Range start (Y-m-d H:i:s).
	 * @param string $end_time    Range end (Y-m-d H:i:s).
	 * @return array List of reservations.
	 */
	public function get_reservations( $resource_id, $start_time, $end_time ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->reservations_table} 
			 WHERE resource_id = %d 
			 AND (
			    (start_time BETWEEN %s AND %s) 
			    OR (end_time BETWEEN %s AND %s) 
			    OR (start_time <= %s AND end_time >= %s)
			 )",
			$resource_id,
			$start_time, $end_time,
			$start_time, $end_time,
			$start_time, $end_time
		) );
	}

	/**
	 * Add a temporary slot lock.
	 *
	 * @param array $data Lock data.
	 * @return int|bool Insert ID or false.
	 */
	public function add_lock( $data ) {
		global $wpdb;
		$result = $wpdb->insert(
			$this->locks_table,
			array(
				'session_id' => sanitize_text_field( $data['session_id'] ),
				'service_id' => (int) $data['service_id'],
				'start_time' => $data['start_time'],
				'expires_at' => $data['expires_at'],
			),
			array( '%s', '%d', '%s', '%s' )
		);
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Clean up expired locks.
	 *
	 * @return int Number of deleted rows.
	 */
	public function cleanup_locks() {
		global $wpdb;
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->locks_table} WHERE expires_at < %s", current_time( 'mysql' ) ) );
	}
}
