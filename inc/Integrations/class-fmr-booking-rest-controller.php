<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Booking_REST_Controller extends WP_REST_Controller {

	private $namespace = 'fmr-booking/v1';
	private $availability_service;
	private $booking_service;

	public function __construct( FMR_Availability_Service $availability_service, FMR_Booking_Service $booking_service ) {
		$this->availability_service = $availability_service;
		$this->booking_service      = $booking_service;
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/services/(?P<service_id>\d+)/slots', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_slots' ),
				'permission_callback' => '__return_true', // NOTE: Add IP rate-limiting here in production
				'args'                => array(
					'date' => array(
						'required'          => true,
						'validate_callback' => function( $param ) { return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param ); },
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		) );

		register_rest_route( $this->namespace, '/appointments', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => array( $this, 'check_nonce' ),
				'args'                => array(
					'service_id'     => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'start_time'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
					'customer_name'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
					'customer_email' => array( 'required' => true, 'sanitize_callback' => 'sanitize_email' ),
					'customer_phone' => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'notes'          => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
				),
			),
		) );
	}

	public function get_slots( $request ) {
		try {
			$slots = $this->availability_service->get_available_slots( $request['service_id'], $request['date'] );
			return rest_ensure_response( array( 'data' => $slots ) );
		} catch ( Exception $e ) {
			return $this->format_error( 'INTERNAL_ERROR', $e->getMessage(), 500 );
		}
	}

	public function create_booking( $request ) {
		$result = $this->booking_service->create_booking( $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return $this->format_error( $result->get_error_code(), $result->get_error_message(), 400 );
		}

		return rest_ensure_response( array( 'data' => array( 'id' => $result, 'status' => 'pending' ) ) );
	}

	public function check_nonce( $request ) {
		return wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
	}

	private function format_error( $code, $message, $status ) {
		return new WP_REST_Response( array(
			'error' => array(
				'code'      => $code,
				'message'   => $message,
				'requestId' => wp_generate_uuid4(),
				'details'   => array()
			)
		), $status );
	}
}
