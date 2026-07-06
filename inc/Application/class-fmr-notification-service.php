<?php
/**
 * Service for handling email notifications and templates.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Service for handling email notifications and templates.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Notification_Service {

	private $repo;
	private $branding_service;

	public function __construct( FMR_Notification_Repository $repo, FMR_Branding_Service $branding_service ) {
		$this->repo = $repo;
		$this->branding_service = $branding_service;
	}

	/**
	 * Send a notification.
	 */
	public function send( $appointment, $type ) {
		$tokens = $this->branding_service->get_tokens( $appointment->client_id );
		$template = $this->get_template( $type, $appointment, $tokens );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent = wp_mail( $appointment->customer_email, $template['subject'], $template['body'], $headers );

		$this->repo->log_notification( array(
			'appointment_id' => $appointment->id,
			'recipient'      => $appointment->customer_email,
			'subject'        => $template['subject'],
			'content'        => $template['body'],
			'status'         => $sent ? 'success' : 'failed',
		) );

		return $sent;
	}

	/**
	 * Get email template based on type.
	 */
	private function get_template( $type, $appointment, $tokens ) {
		$subject = '';
		$body    = '';

		switch ( $type ) {
			case 'booking_received':
				$subject = __( 'Booking Received', 'fmr-booking' );
				$body = sprintf( __( 'Hi %s, we have received your booking request for %s.', 'fmr-booking' ), $appointment->customer_name, $appointment->start_time );
				break;
			case 'booking_approved':
				$subject = __( 'Booking Confirmed', 'fmr-booking' );
				$body = sprintf( __( 'Hi %s, your booking for %s has been confirmed.', 'fmr-booking' ), $appointment->customer_name, $appointment->start_time );
				break;
			case 'reminder_24h':
				$subject = __( 'Reminder: Your appointment is tomorrow', 'fmr-booking' );
				$body = sprintf( __( 'Hi %s, this is a reminder for your appointment at %s.', 'fmr-booking' ), $appointment->customer_name, $appointment->start_time );
				break;
			// Add other cases as needed
		}

		// Wrap in branding layout
		$body = $this->wrap_in_layout( $body, $tokens );

		return array( 'subject' => $subject, 'body' => $body );
	}

	/**
	 * Wrap content in a branded HTML layout.
	 */
	private function wrap_in_layout( $content, $tokens ) {
		$bg_color = $tokens['email']['header_bg'] ?? '#0073aa';
		return "
			<html>
			<body style='font-family: sans-serif; margin: 0; padding: 0;'>
				<div style='background: {$bg_color}; padding: 20px; color: white; text-align: center;'>
					<h2>FMR Booking</h2>
				</div>
				<div style='padding: 20px;'>
					{$content}
				</div>
				<div style='padding: 20px; font-size: 12px; color: #666; text-align: center;'>
					&copy; " . date('Y') . " FMR Booking
				</div>
			</body>
			</html>
		";
	}
}
