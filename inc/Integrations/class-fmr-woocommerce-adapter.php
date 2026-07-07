<?php
/**
 * Adapter for optional WooCommerce integration.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Integrations
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adapter for optional WooCommerce integration.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Integrations
 * @author     FMR
 */
class FMR_WooCommerce_Adapter {

	private $booking_service;

	public function __construct( FMR_Booking_Service $booking_service ) {
		$this->booking_service = $booking_service;
	}

	/**
	 * Check if WooCommerce is active.
	 */
	public static function is_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Register WooCommerce specific hooks.
	 */
	public function init() {
		if ( ! self::is_active() ) {
			return;
		}

		add_action( 'woocommerce_payment_complete', array( $this, 'handle_payment_complete' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_payment_complete' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'handle_payment_complete' ) );
	}

	/**
	 * Handle payment completion to confirm booking.
	 */
	public function handle_payment_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Prevent duplicate processing
		if ( $order->get_meta( '_fmr_payment_processed' ) ) {
			return;
		}

		global $wpdb;
		$processed = false;

		foreach ( $order->get_items() as $item ) {
			$appointment_id = (int) $item->get_meta( '_fmr_appointment_id' );
			if ( ! $appointment_id ) {
				continue;
			}

			// Validate appointment exists and check current status
			$appointment = $wpdb->get_row( $wpdb->prepare(
				"SELECT status, wc_order_id FROM {$wpdb->prefix}fmr_appointments WHERE id = %d",
				$appointment_id
			) );

			if ( ! $appointment || $appointment->status === FMR_Status::APPROVED ) {
				continue;
			}

			// Update status through service layer
			$updated = $this->booking_service->update_status( $appointment_id, FMR_Status::APPROVED );
			
			if ( $updated ) {
				$wpdb->update(
					$wpdb->prefix . 'fmr_appointments',
					array( 'wc_order_id' => $order_id ),
					array( 'id' => $appointment_id ),
					array( '%d' ),
					array( '%d' )
				);
				$processed = true;
			}
		}

		if ( $processed ) {
			$order->update_meta_data( '_fmr_payment_processed', '1' );
			$order->save();
		}
	}

	/**
	 * Add booking to WooCommerce cart.
	 */
	public function add_to_cart( $appointment_id, $service ) {
		if ( ! self::is_active() ) {
			return false;
		}

		// In a real scenario, you might have a hidden product for bookings
		// or map each service to a WC product.
		$product_id = $this->get_service_product_id( $service );
		
		if ( ! $product_id ) {
			return false;
		}

		$cart_item_data = array(
			'fmr_appointment_id' => $appointment_id,
		);

		return WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
	}

	/**
	 * Get WC Product ID for a service.
	 */
	private function get_service_product_id( $service ) {
		// Placeholder logic to find or create a product for the service
		return get_option( 'fmr_booking_default_product_id' );
	}
}
