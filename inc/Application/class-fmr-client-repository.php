<?php
/**
 * Repository for handling client profile data operations.
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
 * Repository for handling client profile data operations.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Client_Repository {

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
		$this->table_name = $wpdb->prefix . 'fmr_client_profiles';
	}

	/**
	 * Create a new client profile.
	 *
	 * @param array $data Client data.
	 * @return int|bool Insert ID or false.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'client_name' => '',
			'slug'        => '',
			'settings'    => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( is_array( $data['settings'] ) ) {
			$data['settings'] = json_encode( $data['settings'] );
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'client_name' => sanitize_text_field( $data['client_name'] ),
				'slug'        => sanitize_title( $data['slug'] ),
				'settings'    => $data['settings'],
			),
			array( '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a client by ID.
	 *
	 * @param int $id Client ID.
	 * @return object|null Client data.
	 */
	public function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
		if ( $row && $row->settings ) {
			$row->settings = json_decode( $row->settings, true );
		}
		return $row;
	}

	/**
	 * Get a client by slug.
	 *
	 * @param string $slug Client slug.
	 * @return object|null Client data.
	 */
	public function get_by_slug( $slug ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE slug = %s", $slug ) );
		if ( $row && $row->settings ) {
			$row->settings = json_decode( $row->settings, true );
		}
		return $row;
	}

	/**
	 * Update a client profile.
	 *
	 * @param int   $id   Client ID.
	 * @param array $data Client data.
	 * @return bool Success or failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = json_encode( $data['settings'] );
		}

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id )
		);

		return $result !== false;
	}

	/**
	 * Delete a client profile.
	 *
	 * @param int $id Client ID.
	 * @return bool Success or failure.
	 */
	public function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
	}
}
