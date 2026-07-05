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

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'fmr_service_resource_rules';
	}

	/**
	 * Add a rule mapping a service to a resource.
	 *
	 * @param int    $service_id  Service ID.
	 * @param int    $resource_id Resource ID.
	 * @param string $rule_type   Type of rule (e.g., 'required').
	 * @return int|bool Insert ID or false.
	 */
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

	/**
	 * Get all required resources for a specific service.
	 *
	 * @param int $service_id Service ID.
	 * @return array List of resource IDs.
	 */
	public function get_required_resources( $service_id ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT resource_id FROM {$this->table_name} WHERE service_id = %d AND rule_type = 'required'",
			$service_id
		) );
	}

	/**
	 * Delete all rules for a service.
	 *
	 * @param int $service_id Service ID.
	 * @return bool Success or failure.
	 */
	public function delete_by_service( $service_id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'service_id' => $id ) );
	}
}
