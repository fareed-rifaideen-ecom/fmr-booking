<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Availability_Service {

	private $availability_repo;
	private $service_repo;
	private $resource_repo;
	private $rule_repo;
	private $avail_rule_repo;

	public function __construct(
		FMR_Availability_Repository $availability_repo,
		FMR_Service_Repository $service_repo,
		FMR_Resource_Repository $resource_repo,
		FMR_Rule_Repository $rule_repo,
		FMR_Availability_Rule_Repository $avail_rule_repo
	) {
		$this->availability_repo = $availability_repo;
		$this->service_repo      = $service_repo;
		$this->resource_repo     = $resource_repo;
		$this->rule_repo         = $rule_repo;
		$this->avail_rule_repo   = $avail_rule_repo;
	}

	public function get_available_slots( $service_id, $date ) {
		$service = $this->service_repo->get( $service_id );
		
		// 🚨 DIAGNOSTIC: Throw exact errors so the frontend prints them.
		if ( ! $service ) throw new Exception("Service not found in the database.");
		if ( ! $service->is_active ) throw new Exception("This service is currently marked as Inactive.");

		$client_id   = (int) $service->client_id;
		$day_of_week = (int) date( 'w', strtotime( $date ) );

		// 1. Fetch Schedule Rules
		$schedules = $this->avail_rule_repo->get_schedule_for_day( $client_id, $day_of_week );
		
		if ( empty( $schedules ) ) {
			throw new Exception("Store is closed on this day of the week. Please check Admin -> Schedules.");
		}

		$open_time  = '23:59:59';
		$close_time = '00:00:00';
		foreach ( $schedules as $schedule ) {
			if ( $schedule->start_time < $open_time ) $open_time = $schedule->start_time;
			if ( $schedule->end_time > $close_time ) $close_time = $schedule->end_time;
		}

		// 2. Fetch Blockouts
		$blockouts = $this->avail_rule_repo->get_blockouts_for_date( $client_id, $date );

		// 3. Check Resources
		$required_resource_ids = $this->rule_repo->get_required_resources( $service_id );
		
		$daily_reservations  = array();
		$resource_capacities = array();
		
		if ( ! empty( $required_resource_ids ) ) {
			$daily_reservations = $this->availability_repo->get_daily_reservations( $required_resource_ids, $date );
			foreach ( $required_resource_ids as $res_id ) {
				$resource = $this->resource_repo->get( $res_id );
				
				if ( ! $resource ) throw new Exception("A required resource (ID: $res_id) is missing from the database.");
				if ( ! $resource->is_active ) throw new Exception("Required resource '{$resource->name}' is marked as Inactive.");
				
				$resource_capacities[$res_id] = (int) $resource->capacity;
			}
		}

		$daily_locks  = $this->availability_repo->get_daily_locks( $service_id, $date );
		$slots        = array();
		$current_time = strtotime( "$date $open_time" );
		$end_time     = strtotime( "$date $close_time" );
		$interval     = (int) $service->duration; 

		if ( $interval <= 0 ) throw new Exception("Service duration must be greater than 0.");

		// 4. Loop Time Blocks
		while ( $current_time + ( $interval * 60 ) <= $end_time ) {
			$slot_start = date( 'Y-m-d H:i:s', $current_time );
			$slot_end   = date( 'Y-m-d H:i:s', $current_time + ( $interval * 60 ) );
			
			if ( $this->is_slot_available_in_memory( $slot_start, $slot_end, $daily_reservations, $daily_locks, $blockouts, $resource_capacities ) ) {
				$slots[] = array( 'start' => $slot_start, 'end' => $slot_end );
			}
			$current_time += $interval * 60; 
		}

		return $slots;
	}

	private function is_slot_available_in_memory( $start_time, $end_time, $daily_reservations, $daily_locks, $blockouts, $resource_capacities ) {
		foreach ( $blockouts as $blockout ) {
			if ( $start_time < $blockout->end_date && $end_time > $blockout->start_date ) {
				return false;
			}
		}

		foreach ( $daily_locks as $lock ) {
			if ( $start_time < $lock->expires_at && $end_time > $lock->start_time ) {
				return false;
			}
		}

		if ( ! empty( $resource_capacities ) ) {
			$resource_usage = array();
			foreach ( $daily_reservations as $reservation ) {
				if ( $start_time < $reservation->end_time && $end_time > $reservation->start_time ) {
					$res_id = $reservation->resource_id;
					$resource_usage[$res_id] = isset($resource_usage[$res_id]) ? $resource_usage[$res_id] + 1 : 1;
					
					if ( $resource_usage[$res_id] >= $resource_capacities[$res_id] ) {
						return false;
					}
				}
			}
		}
		return true;
	}
}
