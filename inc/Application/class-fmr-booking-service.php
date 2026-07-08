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
	 * @return string|WP_Error Appointment UUID on success, or error on failure.
	 */
	public function create_booking( $data ) {
		global $wpdb;

		// 1. Basic Validation
		$service_id = isset( $data['service_id'] ) ? (int) $data['service_id'] : 0;
		$start_time = isset( $data['start_time'] ) ? sanitize_text_field( $data['start_time'] ) : '';

		if ( ! $service_id ) {
			return new WP_Error( 'invalid_service', __( 'Invalid service ID.', 'fmr-booking' ) );
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start_time ) ) {
			return new WP_Error( 'invalid_date', __( 'Invalid start time format. Use YYYY-MM-DD HH:MM:SS.', 'fmr-booking' ) );
		}

		$service = $this->service_repo->get( $service_id );
		if ( ! $service || ! $service->is_active ) {
			return new WP_Error( 'invalid_service', __( 'Service is not active or not found.', 'fmr-booking' ) );
		}

		$start_timestamp = strtotime( $start_time );
		$end_time        = date( 'Y-m-d H:i:s', $start_timestamp + ( $service->duration * 60 ) );
		$booking_date    = date( 'Y-m-d', $start_timestamp );

		// 2. Strict Slot Validation via the Rules Engine
		// We fetch the day's slots to ensure this exact time obeys all blockouts, locks, and capacities.
		$available_slots = $this->availability_service->get_available_slots( $service->id, $booking_date );
		$is_valid_slot   = false;
		
		foreach ( $available_slots as $slot ) {
			if ( $slot['start'] === $start_time ) {
				$is_valid_slot = true;
				break;
			}
		}

		if ( ! $is_valid_slot ) {
			return new WP_Error( 'slot_unavailable', __( 'The selected slot is outside operating hours, blocked, or no longer available.', 'fmr-booking' ) );
		}

		// 3. Generate Secure Identifiers (Crucial for Schema & Security)
		$uuid         = wp_generate_uuid4();
		$secure_token = wp_generate_password( 40, false ); // Used for guest auth (cancel/reschedule links)
		$booking_mode = isset( $data['booking_mode'] ) && in_array( $data['booking_mode'], array( 'in_person', 'virtual' ) ) ? $data['booking_mode'] : 'in_person';
		
		// 4. Begin Database Transaction
		$wpdb->query( 'START TRANSACTION' );

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'fmr_appointments',
			array(
				'uuid'                => $uuid,
				'secure_token'        => $secure_token,
				'client_id'           => $service->client_id,
				'service_id'          => $service->id,
				'customer_name'       => sanitize_text_field( $data['customer_name'] ?? '' ),
				'customer_email'      => sanitize_email( $data['customer_email'] ?? '' ),
				'customer_phone'      => sanitize_text_field( $data['customer_phone'] ?? '' ),
				'booking_mode'        => $booking_mode,
				'start_time'          => $start_time,
				'end_time'            => $end_time,
				'status'              => 'pending',
				'notes'               => sanitize_textarea_field( $data['notes'] ?? '' ),
				'intake_answers_json' => isset( $data['intake_answers'] ) ? wp_json_encode( $data['intake_answers'] ) : null,
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'db_error', __( 'Failed to create appointment.', 'fmr-booking' ) );
		}

		$appointment_id = (int) $wpdb->insert_id;

		// 5. Create Resource Reservations (Locking in the staff/rooms)
		$required_resource_ids = $this->rule_repo->get_required_resources( $service->id );
		
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

		// Return the UUID (not the DB ID) to the API for security
		return $uuid;
	}

	public function update_status( $appointment_id, $new_status ) {
		global $wpdb;

		if ( ! FMR_Status::is_valid( $new_status ) ) {
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
