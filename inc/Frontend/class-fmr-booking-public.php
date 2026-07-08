<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Frontend
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Booking_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_styles() {
		// 🚨 FIX: Corrected path traversal to point to the root plugin directory, not the /inc/ directory.
		$plugin_root_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/fmr-booking.php';
		
		// 🚨 FIX: Changed handle to 'fmr-booking-frontend' to match the Shortcode request
		wp_register_style( 
			'fmr-booking-frontend', 
			plugins_url( 'assets/css/fmr-booking-public.css', $plugin_root_file ), 
			array(), 
			$this->version, 
			'all' 
		);
		
		add_action( 'wp_head', array( $this, 'output_branding_css' ) );
	}

	public function output_branding_css() {
		$client_repo      = new FMR_Client_Repository();
		$branding_repo    = new FMR_Branding_Repository();
		$branding_service = new FMR_Branding_Service( $branding_repo );

		global $wpdb;
		$client_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}fmr_client_profiles LIMIT 1" );

		if ( $client_id ) {
			$css = $branding_service->generate_css_variables( $client_id );
			if ( $css ) {
				echo "<style id='fmr-booking-branding'>\n" . esc_html( $css ) . "\n</style>\n";
			}
		}
	}

	public function enqueue_scripts() {
		$plugin_root_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/fmr-booking.php';

		// 🚨 FIX: Changed handle to 'fmr-booking-frontend' to match the Shortcode request
		wp_register_script( 
			'fmr-booking-frontend', 
			plugins_url( 'assets/js/fmr-booking-public.js', $plugin_root_file ), 
			array( 'jquery' ), 
			$this->version, 
			true // Load in footer for better performance
		);
		
		// Note: We removed the old wpApiSettings localization here because 
		// we correctly localize the highly-secure `fmrBookingConfig` directly inside the Shortcode class now!
	}
}
