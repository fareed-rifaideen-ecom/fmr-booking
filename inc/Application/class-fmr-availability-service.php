<?php
/**
 * Service for handling availability and slot generation.
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
 * Service for handling availability and slot generation.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Availability_Service {

	private $availability_repo;
	private $service_repo;
	private $resource_repo;
	private $rule_repo;

	public function __construct(
		FMR_Availability_Repository $availability_repo,
		FMR_Service_Repository $service_repo,
		FMR_Resource_Repository $resource_repo,
		FMR_Rule_Repository $rule_repo
	) {
		$this->availability_repo = $availability_repo;
		$this->service_repo      = $service_repo;
		$this->resource_repo     = $resource_repo;
		$this->rule_repo         = $rule_repo;
	}

	/**
	 * Generate available slots for a service on a specific date.
	 *
	 * @param int    $service_id Service ID.
	 * @param string $date       Date (Y-m-d).
	 * @return array List of available slots.
	 */
	public function get_available_slots( $service_id, $date ) {
		$service = $this->service_repo->get( $service_id );
		if ( ! $service ) return array();

		$required_resource_ids = $this->rule_repo->get_required_resources( $service_id );
		if ( empty( $required_resource_ids ) ) return array();

		// Get schedule policy from options (or default if not set)
		$start_hour = (int) get_option( 'fmr_booking_start_hour', 9 );
		$end_hour   = (int) get_option( 'fmr_booking_end_hour', 17 );
		$interval   = (int) get_option( 'fmr_booking_slot_interval', 30 );
		
		$slots = array();
		$current_time = strtotime( "$date $start_hour:00:00" );
		$end_time     = strtotime( "$date $end_hour:00:00" );

		while ( $current_time + ( $service->duration * 60 ) <= $end_time ) {
			$slot_start = date( 'Y-m-d H:i:s', $current_time );
			$slot_end   = date( 'Y-m-d H:i:s', $current_time + ( $service->duration * 60 ) );
			
			if ( $this->is_slot_available( $service_id, $required_resource_ids, $slot_start, $slot_end ) ) {
				$slots[] = array(
					'start' => $slot_start,
					'end'   => $slot_end
				);
			}
			
			$current_time += $interval * 60; 
		}

		return $slots;
	}

	/**
	 * Check if all required resources are available for a specific time slot.
	 *
	 * @param int    $service_id   Service ID.
	 * @param array  $resource_ids List of resource IDs.
	 * @param string $start_time   Start time (Y-m-d H:i:s).
	 * @param string $end_time     End time (Y-m-d H:i:s).
	 * @return bool True if all resources are available.
	 */
	public function is_slot_available( $service_id, $resource_ids, $start_time, $end_time ) {
		// 1. Check Resource Capacity
		foreach ( $resource_ids as $resource_id ) {
			$resource = $this->resource_repo->get( $resource_id );
			if ( ! $resource || ! $resource->is_active ) return false;

			$reservations = $this->availability_repo->get_reservations( $resource_id, $start_time, $end_time );
			if ( count( $reservations ) >= $resource->capacity ) {
				return false;
			}
		}

		// 2. Check Slot Locks
		$active_locks = $this->availability_repo->get_active_locks( $service_id, $start_time );
		if ( ! empty( $active_locks ) ) {
			// For simplicity, if there's any active lock, consider it unavailable
			// In advanced scenarios, we'd compare against remaining capacity
			return false;
		}

		return true;
	}
}
