<?php
/**
 * Service for handling approval workflows and admin actions.
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
 * Service for handling approval workflows and admin actions.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Approval_Service {

	private $approval_repo;
	private $booking_service;
	private $log_repo;

	public function __construct(
		FMR_Approval_Repository $approval_repo,
		FMR_Booking_Service $booking_service,
		FMR_Log_Repository $log_repo
	) {
		$this->approval_repo   = $approval_repo;
		$this->booking_service = $booking_service;
		$this->log_repo        = $log_repo;
	}

	/**
	 * Approve a booking request.
	 */
	public function approve_request( $request_id, $user_id ) {
		global $wpdb;
		$request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_approval_requests WHERE id = %d", $request_id ) );
		
		if ( ! $request || $request->status !== 'pending' ) {
			return false;
		}

		$wpdb->query( 'START TRANSACTION' );

		// Update request status
		$this->approval_repo->update_status( $request_id, 'approved', $user_id );

		// Update appointment status
		$this->booking_service->update_status( $request->appointment_id, 'approved' );

		// Log activity
		$this->log_repo->log( array(
			'object_id'   => $request->appointment_id,
			'object_type' => 'appointment',
			'action'      => 'approved',
			'details'     => "Approved request #{$request_id}",
		) );

		$wpdb->query( 'COMMIT' );

		return true;
	}

	/**
	 * Reject a booking request.
	 */
	public function reject_request( $request_id, $user_id ) {
		global $wpdb;
		$request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_approval_requests WHERE id = %d", $request_id ) );

		if ( ! $request || $request->status !== 'pending' ) {
			return false;
		}

		$wpdb->query( 'START TRANSACTION' );

		$this->approval_repo->update_status( $request_id, 'rejected', $user_id );
		$this->booking_service->update_status( $request->appointment_id, 'cancelled' );

		$this->log_repo->log( array(
			'object_id'   => $request->appointment_id,
			'object_type' => 'appointment',
			'action'      => 'rejected',
			'details'     => "Rejected request #{$request_id}",
		) );

		$wpdb->query( 'COMMIT' );

		return true;
	}
}
