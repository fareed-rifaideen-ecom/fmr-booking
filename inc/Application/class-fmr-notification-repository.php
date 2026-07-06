<?php
/**
 * Repository for handling notification logs and reminder queue.
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
 * Repository for handling notification logs and reminder queue.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Notification_Repository {

	private $queue_table;
	private $logs_table;

	public function __construct() {
		global $wpdb;
		$this->queue_table = $wpdb->prefix . 'fmr_reminder_queue';
		$this->logs_table  = $wpdb->prefix . 'fmr_notification_logs';
	}

	/**
	 * Add a reminder to the queue.
	 */
	public function add_to_queue( $appointment_id, $type, $scheduled_at ) {
		global $wpdb;
		return $wpdb->insert(
			$this->queue_table,
			array(
				'appointment_id' => $appointment_id,
				'reminder_type'  => $type,
				'scheduled_at'   => $scheduled_at,
				'status'         => 'pending',
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get pending reminders that are due.
	 */
	public function get_pending_reminders() {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->queue_table} WHERE status = 'pending' AND scheduled_at <= %s",
			current_time( 'mysql' )
		) );
	}

	/**
	 * Update reminder status.
	 */
	public function update_reminder_status( $id, $status ) {
		global $wpdb;
		return $wpdb->update( $this->queue_table, array( 'status' => $status ), array( 'id' => $id ) );
	}

	/**
	 * Log a notification.
	 */
	public function log_notification( $data ) {
		global $wpdb;
		return $wpdb->insert(
			$this->logs_table,
			array(
				'appointment_id' => $data['appointment_id'],
				'recipient'      => $data['recipient'],
				'subject'        => $data['subject'],
				'content'        => $data['content'],
				'status'         => $data['status'],
				'sent_at'        => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}
