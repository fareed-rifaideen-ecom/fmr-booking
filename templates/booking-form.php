<?php
/**
 * Frontend booking form template.
 *
 * @package FMR_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="fmr-booking-container" class="fmr-booking-form-wrapper" data-client-id="<?php echo esc_attr( $client ? $client->id : '' ); ?>">
	<div class="fmr-booking-steps">
		<div class="fmr-step active" data-step="1"><?php esc_html_e( 'Select Service', 'fmr-booking' ); ?></div>
		<div class="fmr-step" data-step="2"><?php esc_html_e( 'Pick a Date', 'fmr-booking' ); ?></div>
		<div class="fmr-step" data-step="3"><?php esc_html_e( 'Your Details', 'fmr-booking' ); ?></div>
		<div class="fmr-step" data-step="4"><?php esc_html_e( 'Confirmation', 'fmr-booking' ); ?></div>
	</div>

	<form id="fmr-booking-form">
		<!-- Step 1: Service Selection -->
		<div class="fmr-form-step active" data-step="1">
			<h3><?php esc_html_e( 'Choose a Service', 'fmr-booking' ); ?></h3>
			<div class="fmr-services-list">
				<!-- Services will be loaded here via JS -->
			</div>
			<button type="button" class="fmr-next-step" disabled><?php esc_html_e( 'Next', 'fmr-booking' ); ?></button>
		</div>

		<!-- Step 2: Date & Time -->
		<div class="fmr-form-step" data-step="2">
			<h3><?php esc_html_e( 'Select Date & Time', 'fmr-booking' ); ?></h3>
			<div class="fmr-datepicker-container">
				<input type="date" id="fmr-booking-date" name="date">
			</div>
			<div class="fmr-slots-container">
				<!-- Slots will be loaded here via AJAX -->
			</div>
			<button type="button" class="fmr-prev-step"><?php esc_html_e( 'Back', 'fmr-booking' ); ?></button>
			<button type="button" class="fmr-next-step" disabled><?php esc_html_e( 'Next', 'fmr-booking' ); ?></button>
		</div>

		<!-- Step 3: Customer Details -->
		<div class="fmr-form-step" data-step="3">
			<h3><?php esc_html_e( 'Enter Your Details', 'fmr-booking' ); ?></h3>
			<div class="fmr-field">
				<label for="fmr-name"><?php esc_html_e( 'Name', 'fmr-booking' ); ?></label>
				<input type="text" id="fmr-name" name="customer_name" required>
			</div>
			<div class="fmr-field">
				<label for="fmr-email"><?php esc_html_e( 'Email', 'fmr-booking' ); ?></label>
				<input type="email" id="fmr-email" name="customer_email" required>
			</div>
			<div class="fmr-field">
				<label for="fmr-phone"><?php esc_html_e( 'Phone', 'fmr-booking' ); ?></label>
				<input type="tel" id="fmr-phone" name="customer_phone">
			</div>
			<button type="button" class="fmr-prev-step"><?php esc_html_e( 'Back', 'fmr-booking' ); ?></button>
			<button type="submit" class="fmr-submit-booking"><?php esc_html_e( 'Book Now', 'fmr-booking' ); ?></button>
		</div>

		<!-- Step 4: Success Message -->
		<div class="fmr-form-step" data-step="4">
			<div class="fmr-success-message">
				<h3><?php esc_html_e( 'Booking Successful!', 'fmr-booking' ); ?></h3>
				<p><?php esc_html_e( 'Your request has been received and is pending approval.', 'fmr-booking' ); ?></p>
			</div>
		</div>
	</form>
</div>
