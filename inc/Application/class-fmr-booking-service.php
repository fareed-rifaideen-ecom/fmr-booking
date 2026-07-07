<?php
/**
 * Service for handling booking creation and lifecycle.
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
 * Service for handling booking creation and lifecycle.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Booking_Service {

	private $availability_service;
	private $service_repo;
	private $rule_repo;

	public function __construct(
		FMR_Availability_Service $availability_service,
		FMR_Service_Repository $service_repo,
		FMR_Rule_Repository $rule_repo
	) {
		$this->availability_service = $availability_service;
		$this->service_repo         = $service_repo;
		$this->rule_repo            = $rule_repo;
	}

	/**
	 * Create a new booking.
	 *
	 * @param array $data Booking data.
	 * @return int|WP_Error Appointment ID or error.
	 */
	public function create_booking( $data ) {
		global $wpdb;

		// Validation
		$service_id = isset( $data['service_id'] ) ? (int) $data['service_id'] : 0;
		$start_time = isset( $data['start_time'] ) ? sanitize_text_field( $data['start_time'] ) : '';

		if ( ! $service_id ) {
			return new WP_Error( 'invalid_service', __( 'Invalid service ID.', 'fmr-booking' ) );
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start_time ) ) {
			return new WP_Error( 'invalid_date', __( 'Invalid start time format. Use YYYY-MM-DD HH:MM:SS.', 'fmr-booking' ) );
		}

		$service = $this->service_repo->get( $service_id );
		if ( ! $service ) {
			return new WP_Error( 'invalid_service', __( 'Service not found.', 'fmr-booking' ) );
		}

		// Standardize time handling using WordPress-aware timestamping
		$start_timestamp = strtotime( $start_time );
		$end_time = date( 'Y-m-d H:i:s', $start_timestamp + ( $service->duration * 60 ) );

		$required_resource_ids = $this->rule_repo->get_required_resources( $service->id );
		if ( ! $this->availability_service->is_slot_available( $service->id, $required_resource_ids, $start_time, $end_time ) ) {
			return new WP_Error( 'slot_unavailable', __( 'The selected slot is no longer available.', 'fmr-booking' ) );
		}

		$wpdb->query( 'START TRANSACTION' );

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'fmr_appointments',
			array(
				'client_id'      => $service->client_id,
				'service_id'     => $service->id,
				'customer_name'  => sanitize_text_field( $data['customer_name'] ?? '' ),
				'customer_email' => sanitize_email( $data['customer_email'] ?? '' ),
				'customer_phone' => sanitize_text_field( $data['customer_phone'] ?? '' ),
				'start_time'     => $start_time,
				'end_time'       => $end_time,
				'status'         => 'pending',
				'notes'          => sanitize_textarea_field( $data['notes'] ?? '' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'db_error', __( 'Failed to create appointment.', 'fmr-booking' ) );
		}

		$appointment_id = (int) $wpdb->insert_id;

		// Create resource reservations with strict error handling
		foreach ( $required_resource_ids as $resource_id ) {
			$res_inserted = $wpdb->insert(
				$wpdb->prefix . 'fmr_resource_reservations',
				array(
					'appointment_id' => $appointment_id,
					'resource_id'    => (int) $resource_id,
					'start_time'     => $start_time,
					'end_time'       => $end_time,
				),
				array( '%d', '%d', '%s', '%s' )
			);

			if ( false === $res_inserted ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'db_error', __( 'Failed to create resource reservation.', 'fmr-booking' ) );
			}
		}

		$wpdb->query( 'COMMIT' );

		return $appointment_id;
	}

	/**
	 * Transition booking status.
	 *
	 * @param int    $appointment_id Appointment ID.
	 * @param string $new_status      New status.
	 * @return bool Success or failure.
	 */
	public function update_status( $appointment_id, $new_status ) {
		global $wpdb;

		$allowed_statuses = array( 'pending', 'approved', 'cancelled', 'completed', 'rescheduled' );
		if ( ! in_array( $new_status, $allowed_statuses, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'fmr_appointments',
			array( 'status' => $new_status ),
			array( 'id' => (int) $appointment_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		return $result !== false;
	}
}
