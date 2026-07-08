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
<div id="fmr-booking-container" class="fmr-booking-form-wrapper">
	
	<div class="fmr-booking-steps">
		<div class="fmr-step active" data-step="1"><?php esc_html_e( 'Date & Time', 'fmr-booking' ); ?></div>
		<div class="fmr-step" data-step="2"><?php esc_html_e( 'Your Details', 'fmr-booking' ); ?></div>
		<div class="fmr-step" data-step="3"><?php esc_html_e( 'Payment', 'fmr-booking' ); ?></div>
		<div class="fmr-step" data-step="4"><?php esc_html_e( 'Done', 'fmr-booking' ); ?></div>
	</div>

	<form id="fmr-booking-form">
		
		<div class="fmr-form-step active" data-step="1">
			<h3><?php esc_html_e( 'Select Date & Time', 'fmr-booking' ); ?></h3>
			
			<div class="fmr-field fmr-datepicker-container">
				<label for="fmr-booking-date"><strong><?php esc_html_e( 'Choose Date', 'fmr-booking' ); ?></strong></label>
				<input type="date" id="fmr-booking-date" name="date" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" required>
			</div>
			
			<div id="fmr-slots-container" class="fmr-slots-container">
				<p class="fmr-muted"><?php esc_html_e( 'Please select a date to view available slots.', 'fmr-booking' ); ?></p>
			</div>
			
			<div class="fmr-form-actions">
				<button type="button" class="fmr-next-step fmr-btn"><?php esc_html_e( 'Next', 'fmr-booking' ); ?></button>
			</div>
		</div>

		<div class="fmr-form-step" data-step="2" style="display: none;">
			<h3><?php esc_html_e( 'Enter Your Details', 'fmr-booking' ); ?></h3>
			
			<div class="fmr-field fmr-booking-mode-select">
				<label><strong><?php esc_html_e( 'Appointment Type', 'fmr-booking' ); ?></strong></label>
				<div class="fmr-radio-group">
					<label>
						<input type="radio" name="booking_mode" value="in_person" checked> 
						<?php esc_html_e( 'In-Person', 'fmr-booking' ); ?>
					</label>
					<label>
						<input type="radio" name="booking_mode" value="virtual"> 
						<?php esc_html_e( 'Virtual (Video Call)', 'fmr-booking' ); ?>
					</label>
				</div>
			</div>

			<div class="fmr-field">
				<label for="fmr-name"><?php esc_html_e( 'Full Name', 'fmr-booking' ); ?> <span class="required">*</span></label>
				<input type="text" id="fmr-name" name="customer_name" required>
			</div>
			
			<div class="fmr-field">
				<label for="fmr-email"><?php esc_html_e( 'Email Address', 'fmr-booking' ); ?> <span class="required">*</span></label>
				<input type="email" id="fmr-email" name="customer_email" required>
			</div>
			
			<div class="fmr-field">
				<label for="fmr-phone"><?php esc_html_e( 'Phone Number', 'fmr-booking' ); ?></label>
				<input type="tel" id="fmr-phone" name="customer_phone">
			</div>

			<div class="fmr-field">
				<label for="fmr-notes"><?php esc_html_e( 'Additional Notes', 'fmr-booking' ); ?></label>
				<textarea id="fmr-notes" name="notes" rows="3" placeholder="<?php esc_attr_e( 'Anything specific we should know?', 'fmr-booking' ); ?>"></textarea>
			</div>

			<div class="fmr-form-actions">
				<button type="button" class="fmr-prev-step fmr-btn fmr-btn-secondary"><?php esc_html_e( 'Back', 'fmr-booking' ); ?></button>
				<button type="submit" class="fmr-submit-booking fmr-btn fmr-btn-primary"><?php esc_html_e( 'Confirm Booking', 'fmr-booking' ); ?></button>
			</div>
		</div>

		<div class="fmr-form-step" data-step="3" style="display: none;">
			<div class="fmr-checkout-placeholder text-center">
				<h3><?php esc_html_e( 'Secure Checkout', 'fmr-booking' ); ?></h3>
				<p><?php esc_html_e( 'Redirecting to secure payment gateway...', 'fmr-booking' ); ?></p>
				<div class="fmr-loader"></div>
			</div>
		</div>

		<div class="fmr-form-step" data-step="4" style="display: none;">
			<div class="fmr-success-message text-center">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2" style="margin-bottom: 15px;">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
				<h3><?php esc_html_e( 'Booking Confirmed!', 'fmr-booking' ); ?></h3>
				<p><?php esc_html_e( 'Your request has been securely processed. A confirmation email is on its way.', 'fmr-booking' ); ?></p>
			</div>
		</div>
		
	</form>
</div>
