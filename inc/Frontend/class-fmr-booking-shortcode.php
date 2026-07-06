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
		$atts = shortcode_atts( array(
			'client_slug' => '',
			'service_id'  => '',
		), $atts, 'fmr_booking_form' );

		$client = null;
		if ( ! empty( $atts['client_slug'] ) ) {
			$client = $this->client_repo->get_by_slug( $atts['client_slug'] );
		}

		// Enqueue necessary scripts and styles
		wp_enqueue_style( 'fmr-booking-frontend' );
		wp_enqueue_script( 'fmr-booking-frontend' );

		ob_start();
		include plugin_dir_path( dirname( __FILE__ ) ) . '../templates/booking-form.php';
		return ob_get_clean();
	}
}
