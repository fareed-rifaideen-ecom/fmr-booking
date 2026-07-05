<?php
/**
 * Repository for handling resource data operations.
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
 * Repository for handling resource data operations.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Resource_Repository {

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
		$this->table_name = $wpdb->prefix . 'fmr_resources';
	}

	/**
	 * Create a new resource.
	 *
	 * @param array $data Resource data.
	 * @return int|bool Insert ID or false.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'client_id'   => 0,
			'name'        => '',
			'type'        => 'staff',
			'capacity'    => 1,
			'description' => '',
			'is_active'   => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'client_id'   => (int) $data['client_id'],
				'name'        => sanitize_text_field( $data['name'] ),
				'type'        => sanitize_text_field( $data['type'] ),
				'capacity'    => (int) $data['capacity'],
				'description' => wp_kses_post( $data['description'] ),
				'is_active'   => (int) $data['is_active'],
			),
			array( '%d', '%s', '%s', '%d', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a resource by ID.
	 *
	 * @param int $id Resource ID.
	 * @return object|null Resource data.
	 */
	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
	}

	/**
	 * Get all resources for a client.
	 *
	 * @param int $client_id Client ID.
	 * @return array List of resources.
	 */
	public function get_by_client( $client_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE client_id = %d ORDER BY name ASC", $client_id ) );
	}

	/**
	 * Update a resource.
	 *
	 * @param int   $id   Resource ID.
	 * @param array $data Resource data.
	 * @return bool Success or failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;
		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id )
		);
		return $result !== false;
	}

	/**
	 * Delete a resource.
	 *
	 * @param int $id Resource ID.
	 * @return bool Success or failure.
	 */
	public function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
	}
}
