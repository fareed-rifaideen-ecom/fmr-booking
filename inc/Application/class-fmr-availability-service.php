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
		FMR_Availability_Rule_Repository $avail_rule_repo // 🚨 NEW: Injected Rule Engine
	) {
		$this->availability_repo = $availability_repo;
		$this->service_repo      = $service_repo;
		$this->resource_repo     = $resource_repo;
		$this->rule_repo         = $rule_repo;
		$this->avail_rule_repo   = $avail_rule_repo;
	}

	public function get_available_slots( $service_id, $date ) {
		$service = $this->service_repo->get( $service_id );
		if ( ! $service ) return array();

		$client_id   = (int) $service->client_id;
		$day_of_week = (int) date( 'w', strtotime( $date ) ); // 0 = Sunday, 6 = Saturday

		// 1. Fetch Schedule Rules (Operating Hours) for this Day
		$schedules = $this->avail_rule_repo->get_schedule_for_day( $client_id, $day_of_week );
		
		// Fast-fail: If there are no rules for this day, they are closed.
		if ( empty( $schedules ) ) return array(); 

		// Calculate bounds based on the schedules (Simplified for MVP: takes earliest start and latest end)
		$open_time  = '23:59:59';
		$close_time = '00:00:00';
		foreach ( $schedules as $schedule ) {
			if ( $schedule->start_time < $open_time ) $open_time = $schedule->start_time;
			if ( $schedule->end_time > $close_time ) $close_time = $schedule->end_time;
		}

		// 2. Fetch full-day or partial Blockouts (Holidays / Sick Leave)
		$blockouts = $this->avail_rule_repo->get_blockouts_for_date( $client_id, $date );

		// 3. Check Resources
		$required_resource_ids = $this->rule_repo->get_required_resources( $service_id );
		if ( empty( $required_resource_ids ) ) return array();

		$daily_reservations = $this->availability_repo->get_daily_reservations( $required_resource_ids, $date );
		$daily_locks        = $this->availability_repo->get_daily_locks( $service_id, $date );
		
		$resource_capacities = array();
		foreach ( $required_resource_ids as $res_id ) {
			$resource = $this->resource_repo->get( $res_id );
			if ( ! $resource || ! $resource->is_active ) return array(); 
			$resource_capacities[$res_id] = (int) $resource->capacity;
		}

		$slots = array();
		$current_time = strtotime( "$date $open_time" );
		$end_time     = strtotime( "$date $close_time" );
		$interval     = isset( $service->slot_interval ) ? (int) $service->slot_interval : 30;

		// 4. Loop Time Blocks (In-Memory Processing)
		while ( $current_time + ( $service->duration * 60 ) <= $end_time ) {
			$slot_start = date( 'Y-m-d H:i:s', $current_time );
			$slot_end   = date( 'Y-m-d H:i:s', $current_time + ( $service->duration * 60 ) );
			
			if ( $this->is_slot_available_in_memory( $slot_start, $slot_end, $daily_reservations, $daily_locks, $blockouts, $resource_capacities ) ) {
				$slots[] = array(
					'start' => $slot_start,
					'end'   => $slot_end
				);
			}
			
			$current_time += $interval * 60; 
		}

		return $slots;
	}

	private function is_slot_available_in_memory( $start_time, $end_time, $daily_reservations, $daily_locks, $blockouts, $resource_capacities ) {
		// A. Check Blockouts (Holidays/Time Off)
		foreach ( $blockouts as $blockout ) {
			if ( $start_time < $blockout->end_date && $end_time > $blockout->start_date ) {
				return false;
			}
		}

		// B. Check Cart Locks
		foreach ( $daily_locks as $lock ) {
			if ( $start_time < $lock->expires_at && $end_time > $lock->start_time ) {
				return false;
			}
		}

		// C. Check Resource Capacity
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

		return true;
	}
}
