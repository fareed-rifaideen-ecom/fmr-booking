<?php
/**
 * Repository for handling activity and audit logs.
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
 * Repository for handling activity and audit logs.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Log_Repository {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'fmr_activity_logs';
	}

	/**
	 * Log an activity.
	 */
	public function log( $data ) {
		global $wpdb;
		return $wpdb->insert(
			$this->table_name,
			array(
				'client_id'   => $data['client_id'] ?? null,
				'object_id'   => $data['object_id'],
				'object_type' => $data['object_type'],
				'action'      => $data['action'],
				'user_id'     => get_current_user_id(),
				'details'     => is_array( $data['details'] ) ? json_encode( $data['details'] ) : $data['details'],
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Get logs for a specific object.
	 */
	public function get_by_object( $object_id, $object_type ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE object_id = %d AND object_type = %s ORDER BY created_at DESC",
			$object_id,
			$object_type
		) );
	}
}
