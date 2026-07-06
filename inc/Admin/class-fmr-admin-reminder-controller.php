<?php
/**
 * Admin controller for handling the reminder queue.
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
 * Admin controller for handling the reminder queue.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 * @author     FMR
 */
class FMR_Admin_Reminder_Controller {

	private $notification_repo;

	public function __construct( FMR_Notification_Repository $notification_repo ) {
		$this->notification_repo = $notification_repo;
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menus() {
		add_submenu_page(
			'fmr-booking',
			__( 'Reminder Queue', 'fmr-booking' ),
			__( 'Reminder Queue', 'fmr-booking' ),
			'manage_options',
			'fmr-booking-reminders',
			array( $this, 'render_reminders_page' )
		);
	}

	/**
	 * Render the reminder queue management page.
	 */
	public function render_reminders_page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'Reminder Queue', 'fmr-booking' ) . '</h1><p>' . esc_html__( 'View and manage scheduled notifications.', 'fmr-booking' ) . '</p></div>';
	}
}
