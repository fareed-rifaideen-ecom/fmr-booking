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

		// For simplicity, assume business hours 09:00 - 17:00
		$start_hour = 9;
		$end_hour   = 17;
		$duration   = $service->duration + $service->buffer_before + $service->buffer_after;
		
		$slots = array();
		$current_time = strtotime( "$date $start_hour:00:00" );
		$end_time     = strtotime( "$date $end_hour:00:00" );

		while ( $current_time + ( $service->duration * 60 ) <= $end_time ) {
			$slot_start = date( 'Y-m-d H:i:s', $current_time );
			$slot_end   = date( 'Y-m-d H:i:s', $current_time + ( $service->duration * 60 ) );
			
			if ( $this->is_slot_available( $required_resource_ids, $slot_start, $slot_end ) ) {
				$slots[] = array(
					'start' => $slot_start,
					'end'   => $slot_end
				);
			}
			
			// Move to next slot (interval could be duration or fixed like 30 mins)
			$current_time += 30 * 60; 
		}

		return $slots;
	}

	/**
	 * Check if all required resources are available for a specific time slot.
	 *
	 * @param array  $resource_ids List of resource IDs.
	 * @param string $start_time   Start time (Y-m-d H:i:s).
	 * @param string $end_time     End time (Y-m-d H:i:s).
	 * @return bool True if all resources are available.
	 */
	public function is_slot_available( $resource_ids, $start_time, $end_time ) {
		foreach ( $resource_ids as $resource_id ) {
			$resource = $this->resource_repo->get( $resource_id );
			if ( ! $resource || ! $resource->is_active ) return false;

			$reservations = $this->availability_repo->get_reservations( $resource_id, $start_time, $end_time );
			if ( count( $reservations ) >= $resource->capacity ) {
				return false;
			}
		}
		return true;
	}
}
