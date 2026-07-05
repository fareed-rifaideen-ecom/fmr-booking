<?php
/**
 * Admin controller for handling services and resources.
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
 * Admin controller for handling services and resources.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 * @author     FMR
 */
class FMR_Admin_Service_Controller {

	/**
	 * Service repository.
	 *
	 * @var FMR_Service_Repository
	 */
	private $service_repo;

	/**
	 * Resource repository.
	 *
	 * @var FMR_Resource_Repository
	 */
	private $resource_repo;

	/**
	 * Initialize the class.
	 *
	 * @param FMR_Service_Repository  $service_repo  Service repository.
	 * @param FMR_Resource_Repository $resource_repo Resource repository.
	 */
	public function __construct( FMR_Service_Repository $service_repo, FMR_Resource_Repository $resource_repo ) {
		$this->service_repo  = $service_repo;
		$this->resource_repo = $resource_repo;
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menus() {
		add_submenu_page(
			'fmr-booking',
			__( 'Services', 'fmr-booking' ),
			__( 'Services', 'fmr-booking' ),
			'manage_options',
			'fmr-booking-services',
			array( $this, 'render_services_page' )
		);

		add_submenu_page(
			'fmr-booking',
			__( 'Resources', 'fmr-booking' ),
			__( 'Resources', 'fmr-booking' ),
			'manage_options',
			'fmr-booking-resources',
			array( $this, 'render_resources_page' )
		);
	}

	/**
	 * Render the services management page.
	 */
	public function render_services_page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'Services', 'fmr-booking' ) . '</h1><p>' . esc_html__( 'Manage booking services and their rules.', 'fmr-booking' ) . '</p></div>';
	}

	/**
	 * Render the resources management page.
	 */
	public function render_resources_page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'Resources', 'fmr-booking' ) . '</h1><p>' . esc_html__( 'Manage staff, rooms, and equipment.', 'fmr-booking' ) . '</p></div>';
	}
}
