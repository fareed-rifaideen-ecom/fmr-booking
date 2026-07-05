<?php
/**
 * REST API controller for booking operations.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Integrations
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * REST API controller for booking operations.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Integrations
 * @author     FMR
 */
class FMR_Booking_REST_Controller extends WP_REST_Controller {

	private $namespace = 'fmr-booking/v1';
	private $availability_service;
	private $booking_service;

	public function __construct( FMR_Availability_Service $availability_service, FMR_Booking_Service $booking_service ) {
		$this->availability_service = $availability_service;
		$this->booking_service      = $booking_service;
	}

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/slots', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_slots' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service_id' => array( 'required' => true, 'validate_callback' => 'is_numeric' ),
					'date'       => array( 'required' => true ),
				),
			),
		) );

		register_rest_route( $this->namespace, '/book', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			),
		) );
	}

	/**
	 * Get available slots.
	 */
	public function get_slots( $request ) {
		$service_id = $request['service_id'];
		$date       = $request['date'];
		$slots      = $this->availability_service->get_available_slots( $service_id, $date );
		return rest_ensure_response( $slots );
	}

	/**
	 * Create a booking.
	 */
	public function create_booking( $request ) {
		$params = $request->get_params();
		$result = $this->booking_service->create_booking( $params );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'appointment_id' => $result, 'message' => __( 'Booking successful.', 'fmr-booking' ) ) );
	}

	/**
	 * Check nonce for secure requests.
	 */
	public function check_nonce( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		return wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
