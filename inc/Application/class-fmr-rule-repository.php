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

/**
 * Repository for handling service-resource dependency rules.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
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
		// 🚨 Performance: Explicitly selecting only what is needed.
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT resource_id FROM {$this->table_name} WHERE service_id = %d AND rule_type = 'required'",
			$service_id
		) );
	}

	public function delete_by_service( $service_id ) {
		global $wpdb;
		// 🚨 FIX: Changed undefined variable $id to the correct parameter $service_id
		return $wpdb->delete( $this->table_name, array( 'service_id' => $service_id ), array( '%d' ) );
	}
}
