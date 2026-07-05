<?php
/**
 * Admin controller for handling client and branding settings.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin controller for handling client and branding settings.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 * @author     FMR
 */
class FMR_Admin_Client_Controller {

	/**
	 * Client repository.
	 *
	 * @var FMR_Client_Repository
	 */
	private $client_repo;

	/**
	 * Branding repository.
	 *
	 * @var FMR_Branding_Repository
	 */
	private $branding_repo;

	/**
	 * Initialize the class.
	 *
	 * @param FMR_Client_Repository   $client_repo   Client repository.
	 * @param FMR_Branding_Repository $branding_repo Branding repository.
	 */
	public function __construct( FMR_Client_Repository $client_repo, FMR_Branding_Repository $branding_repo ) {
		$this->client_repo   = $client_repo;
		$this->branding_repo = $branding_repo;
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'FMR Booking', 'fmr-booking' ),
			__( 'FMR Booking', 'fmr-booking' ),
			'manage_options',
			'fmr-booking',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			25
		);

		add_submenu_page(
			'fmr-booking',
			__( 'Client Profiles', 'fmr-booking' ),
			__( 'Client Profiles', 'fmr-booking' ),
			'manage_options',
			'fmr-booking-clients',
			array( $this, 'render_clients_page' )
		);

		add_submenu_page(
			'fmr-booking',
			__( 'Branding', 'fmr-booking' ),
			__( 'Branding', 'fmr-booking' ),
			'manage_options',
			'fmr-booking-branding',
			array( $this, 'render_branding_page' )
		);
	}

	/**
	 * Render the main dashboard.
	 */
	public function render_dashboard() {
		echo '<div class="wrap"><h1>' . esc_html__( 'FMR Booking Dashboard', 'fmr-booking' ) . '</h1></div>';
	}

	/**
	 * Render the client profiles page.
	 */
	public function render_clients_page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'Client Profiles', 'fmr-booking' ) . '</h1><p>' . esc_html__( 'Manage client profiles and settings here.', 'fmr-booking' ) . '</p></div>';
	}

	/**
	 * Render the branding settings page.
	 */
	public function render_branding_page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'Branding Presets', 'fmr-booking' ) . '</h1><p>' . esc_html__( 'Configure visual branding tokens and CSS variables.', 'fmr-booking' ) . '</p></div>';
	}
}
