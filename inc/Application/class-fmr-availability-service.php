<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

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

	public function get_available_slots( $service_id, $date ) {
		$service = $this->service_repo->get( $service_id );
		if ( ! $service ) return array();

		$required_resource_ids = $this->rule_repo->get_required_resources( $service_id );
		if ( empty( $required_resource_ids ) ) return array();

		// Fetch configurations
		$start_hour = (int) get_option( 'fmr_booking_start_hour', 9 );
		$end_hour   = (int) get_option( 'fmr_booking_end_hour', 17 );
		$interval   = (int) get_option( 'fmr_booking_slot_interval', 30 );
		
		// 🚨 FIX: Fetch ALL reservations and locks for the entire day upfront (2 Queries max)
		$daily_reservations = $this->availability_repo->get_daily_reservations( $required_resource_ids, $date );
		$daily_locks        = $this->availability_repo->get_daily_locks( $service_id, $date );
		
		// Pre-fetch resource capacities to avoid querying inside the loop
		$resource_capacities = array();
		foreach ( $required_resource_ids as $res_id ) {
			$resource = $this->resource_repo->get( $res_id );
			if ( ! $resource || ! $resource->is_active ) return array(); // Fast fail if a resource is inactive
			$resource_capacities[$res_id] = (int) $resource->capacity;
		}

		$slots = array();
		$current_time = strtotime( "$date $start_hour:00:00" );
		$end_time     = strtotime( "$date $end_hour:00:00" );

		// Now loop through time blocks. No DB queries happen in here.
		while ( $current_time + ( $service->duration * 60 ) <= $end_time ) {
			$slot_start = date( 'Y-m-d H:i:s', $current_time );
			$slot_end   = date( 'Y-m-d H:i:s', $current_time + ( $service->duration * 60 ) );
			
			if ( $this->is_slot_available_in_memory( $slot_start, $slot_end, $daily_reservations, $daily_locks, $resource_capacities ) ) {
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
	 * Checks slot availability entirely in PHP memory. O(N) complexity, zero DB hits.
	 */
	private function is_slot_available_in_memory( $start_time, $end_time, $daily_reservations, $daily_locks, $resource_capacities ) {
		// 1. Check Locks
		foreach ( $daily_locks as $lock ) {
			// If lock overlaps with this slot
			if ( $start_time < $lock->expires_at && $end_time > $lock->start_time ) {
				return false;
			}
		}

		// 2. Check Resource Capacity
		$resource_usage = array();
		
		foreach ( $daily_reservations as $reservation ) {
			// If reservation overlaps with this slot
			if ( $start_time < $reservation->end_time && $end_time > $reservation->start_time ) {
				$res_id = $reservation->resource_id;
				$resource_usage[$res_id] = isset($resource_usage[$res_id]) ? $resource_usage[$res_id] + 1 : 1;
				
				// If this resource is maxed out for this time slot, it's unavailable
				if ( $resource_usage[$res_id] >= $resource_capacities[$res_id] ) {
					return false;
				}
			}
		}

		return true;
	}
}
