<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Frontend
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Frontend
 * @author     FMR
 */
class FMR_Booking_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
		public function enqueue_styles() {
			wp_register_style( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/fmr-booking-public.css', array(), $this->version, 'all' );
			
			// Output dynamic CSS variables
			add_action( 'wp_head', array( $this, 'output_branding_css' ) );
		}

	/**
	 * Output branding CSS variables in the head.
	 */
	public function output_branding_css() {
		// For now, we use a default client ID or the first one found
		// In a real scenario, this would be determined by the current page or shortcode attribute
		$client_repo    = new FMR_Client_Repository();
		$branding_repo  = new FMR_Branding_Repository();
		$branding_service = new FMR_Branding_Service( $branding_repo );

		// Get the first client for demo purposes
		global $wpdb;
		$client_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}fmr_client_profiles LIMIT 1" );

		if ( $client_id ) {
			$css = $branding_service->generate_css_variables( $client_id );
			echo "<style id='fmr-booking-branding'>\n" . $css . "</style>\n";
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
		public function enqueue_scripts() {
			wp_register_script( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/fmr-booking-public.js', array( 'jquery' ), $this->version, false );
			
			wp_localize_script( $this->plugin_name, 'wpApiSettings', array(
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			) );
		}

}
