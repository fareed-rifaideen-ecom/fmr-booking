<?php
/**
 * Repository for handling service-resource dependency rules.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Rule_Repository {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'fmr_service_resource_rules';
	}

	public function add_rule( $service_id, $resource_id, $rule_type = 'required' ) {
		global $wpdb;
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'service_id'  => (int) $service_id,
				'resource_id' => (int) $resource_id,
				'rule_type'   => sanitize_text_field( $rule_type ),
			),
			array( '%d', '%d', '%s' )
		);
		return $result ? $wpdb->insert_id : false;
	}

	public function get_required_resources( $service_id ) {
		global $wpdb;
		$results = $wpdb->get_col( $wpdb->prepare(
			"SELECT resource_id FROM {$this->table_name} WHERE service_id = %d AND rule_type = 'required'",
			$service_id
		) );
		
		// Ensure it always returns an array, even if empty
		return is_array( $results ) ? $results : array();
	}

	public function delete_by_service( $service_id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'service_id' => $service_id ), array( '%d' ) );
	}
}
