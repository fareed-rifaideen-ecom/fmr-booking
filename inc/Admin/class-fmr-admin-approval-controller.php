<?php
/**
 * Admin controller for handling the approval workflow UI.
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
 * Admin controller for handling the approval workflow UI.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 * @author     FMR
 */
class FMR_Admin_Approval_Controller {

	private $approval_repo;
	private $approval_service;

	public function __construct( FMR_Approval_Repository $approval_repo, FMR_Approval_Service $approval_service ) {
		$this->approval_repo    = $approval_repo;
		$this->approval_service = $approval_service;
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menus() {
		add_submenu_page(
			'fmr-booking',
			__( 'Approval Queue', 'fmr-booking' ),
			__( 'Approval Queue', 'fmr-booking' ),
			'manage_options',
			'fmr-booking-approvals',
			array( $this, 'render_approvals_page' )
		);
	}

	/**
	 * Render the approval queue page.
	 */
	public function render_approvals_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Approval Queue', 'fmr-booking' ) . '</h1><p>' . esc_html__( 'Manage booking and reschedule requests.', 'fmr-booking' ) . '</p></div>';
	}
}
