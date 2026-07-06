<?php
/**
 * Service for handling scheduled cron jobs.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Cron
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Service for handling scheduled cron jobs.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Cron
 * @author     FMR
 */
class FMR_Cron_Service {

	private $notification_repo;
	private $notification_service;

	public function __construct( FMR_Notification_Repository $notification_repo, FMR_Notification_Service $notification_service ) {
		$this->notification_repo    = $notification_repo;
		$this->notification_service = $notification_service;
	}

	/**
	 * Process the reminder queue.
	 */
	public function process_reminders() {
		$reminders = $this->notification_repo->get_pending_reminders();
		
		global $wpdb;
		$appointments_table = $wpdb->prefix . 'fmr_appointments';

		foreach ( $reminders as $reminder ) {
			$appointment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$appointments_table} WHERE id = %d", $reminder->appointment_id ) );
			
			if ( $appointment ) {
				$sent = $this->notification_service->send( $appointment, $reminder->reminder_type );
				$this->notification_repo->update_reminder_status( $reminder->id, $sent ? 'sent' : 'failed' );
			} else {
				$this->notification_repo->update_reminder_status( $reminder->id, 'failed' );
			}
		}
	}
}
