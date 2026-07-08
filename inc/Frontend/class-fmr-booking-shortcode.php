<?php
/**
 * Shortcode for the frontend booking form.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Frontend
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortcode for the frontend booking form.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Frontend
 * @author     FMR
 */
class FMR_Booking_Shortcode {

	private $service_repo;
	private $client_repo;

	public function __construct( FMR_Service_Repository $service_repo, FMR_Client_Repository $client_repo ) {
		$this->service_repo = $service_repo;
		$this->client_repo  = $client_repo;
	}

	/**
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( 'fmr_booking_form', array( $this, 'render' ) );
	}

	/**
	 * Render the booking form.
	 */
	public function render( $atts ) {
		// 1. Parse and sanitize attributes
		$atts = shortcode_atts( array(
			'client_slug' => '',
			'service_id'  => 0,
		), $atts, 'fmr_booking_form' );

		$client_id = 0;
		if ( ! empty( $atts['client_slug'] ) ) {
			$client = $this->client_repo->get_by_slug( sanitize_text_field( $atts['client_slug'] ) );
			if ( $client ) {
				$client_id = (int) $client->id;
			}
		}

		// 2. Enqueue necessary scripts and styles
		wp_enqueue_style( 'fmr-booking-frontend' );
		wp_enqueue_script( 'fmr-booking-frontend' );

		// 3. Securely bridge the backend to the frontend JS
		wp_localize_script( 'fmr-booking-frontend', 'fmrBookingConfig', array(
			'restUrl'   => esc_url_raw( rest_url( 'fmr-booking/v1' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'clientId'  => $client_id,
			'serviceId' => (int) $atts['service_id'],
			'i18n'      => array(
				'loading' => __( 'Loading available slots...', 'fmr-booking' ),
				'error'   => __( 'Something went wrong. Please try again.', 'fmr-booking' ),
			)
		) );

		// 4. Render the UI
		ob_start();
		$service_id = (int) $atts['service_id'];
		
		// 🚨 FIX: Corrected the directory path so it reliably points to /templates/booking-form.php
		$template_path = dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/booking-form.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<p>Error: Booking template missing.</p>';
		}
		
		return ob_get_clean();
	}
}
