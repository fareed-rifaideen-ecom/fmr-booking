<?php
/**
 * Repository for handling service data operations.
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
 * Repository for handling service data operations.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Service_Repository {

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
		$this->table_name = $wpdb->prefix . 'fmr_services';
	}

	/**
	 * Create a new service.
	 *
	 * @param array $data Service data.
	 * @return int|bool Insert ID or false.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'client_id'     => 0,
			'title'         => '',
			'description'   => '',
			'duration'      => 30,
			'buffer_before' => 0,
			'buffer_after'  => 0,
			'price'         => 0.00,
			'is_active'     => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'client_id'     => (int) $data['client_id'],
				'title'         => sanitize_text_field( $data['title'] ),
				'description'   => wp_kses_post( $data['description'] ),
				'duration'      => (int) $data['duration'],
				'buffer_before' => (int) $data['buffer_before'],
				'buffer_after'  => (int) $data['buffer_after'],
				'price'         => (float) $data['price'],
				'is_active'     => (int) $data['is_active'],
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%f', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a service by ID.
	 *
	 * @param int $id Service ID.
	 * @return object|null Service data.
	 */
	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
	}

	/**
	 * Get all services for a client.
	 *
	 * @param int $client_id Client ID.
	 * @return array List of services.
	 */
	public function get_by_client( $client_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE client_id = %d ORDER BY title ASC", $client_id ) );
	}

	/**
	 * Update a service.
	 *
	 * @param int   $id   Service ID.
	 * @param array $data Service data.
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
	 * Delete a service.
	 *
	 * @param int $id Service ID.
	 * @return bool Success or failure.
	 */
	public function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
	}
}
