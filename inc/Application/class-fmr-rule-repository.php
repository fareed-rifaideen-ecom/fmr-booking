<?php
/**
 * Repository for handling service-resource dependency rules.
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

	/**
	 * 🚨 FIX: Forcefully create the table if the host dropped it during activation.
	 */
	private function ensure_table_exists() {
		global $wpdb;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) != $this->table_name ) {
			$charset_collate = $wpdb->get_charset_collate();
			// Create table without strict Foreign Keys to prevent host rejections
			$wpdb->query( "CREATE TABLE {$this->table_name} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				service_id bigint(20) UNSIGNED NOT NULL,
				resource_id bigint(20) UNSIGNED NOT NULL,
				rule_type varchar(50) DEFAULT 'required',
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY service_id (service_id),
				KEY resource_id (resource_id)
			) {$charset_collate};" );
		}
	}

	public function add_rule( $service_id, $resource_id, $rule_type = 'required' ) {
		global $wpdb;
		$this->ensure_table_exists(); // Run the healing check

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
		$this->ensure_table_exists(); // Run the healing check

		$results = $wpdb->get_col( $wpdb->prepare(
			"SELECT resource_id FROM {$this->table_name} WHERE service_id = %d AND rule_type = 'required'",
			$service_id
		) );
		
		return is_array( $results ) ? $results : array();
	}

	public function delete_by_service( $service_id ) {
		global $wpdb;
		$this->ensure_table_exists(); // Run the healing check
		return $wpdb->delete( $this->table_name, array( 'service_id' => $service_id ), array( '%d' ) );
	}
}
