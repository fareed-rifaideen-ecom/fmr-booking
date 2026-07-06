<?php
/**
 * Repository for handling approval requests.
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
 * Repository for handling approval requests.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Approval_Repository {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'fmr_approval_requests';
	}

	/**
	 * Create an approval request.
	 */
	public function create( $appointment_id, $type ) {
		global $wpdb;
		return $wpdb->insert(
			$this->table_name,
			array(
				'appointment_id' => $appointment_id,
				'request_type'   => $type,
				'status'         => 'pending',
				'requested_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get pending approval requests.
	 */
	public function get_pending() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE status = 'pending' ORDER BY requested_at ASC" );
	}

	/**
	 * Update approval status.
	 */
	public function update_status( $id, $status, $user_id ) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name,
			array(
				'status'      => $status,
				'actioned_at' => current_time( 'mysql' ),
				'actioned_by' => $user_id,
			),
			array( 'id' => $id )
		);
	}
}
