<?php
/**
 * Repository for handling branding preset data operations.
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
 * Repository for handling branding preset data operations.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Branding_Repository {

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
		$this->table_name = $wpdb->prefix . 'fmr_branding_presets';
	}

	/**
	 * Create a new branding preset.
	 *
	 * @param array $data Preset data.
	 * @return int|bool Insert ID or false.
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'client_id'           => 0,
			'preset_name'         => '',
			'logo_url'            => '',
			'primary_color'       => '#000000',
			'secondary_color'     => '#ffffff',
			'accent_color'        => '#cccccc',
			'typography_settings' => array(),
			'spacing_settings'    => array(),
			'button_styles'       => array(),
			'email_theme'         => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$insert_data = array(
			'client_id'           => (int) $data['client_id'],
			'preset_name'         => sanitize_text_field( $data['preset_name'] ),
			'logo_url'            => esc_url_raw( $data['logo_url'] ),
			'primary_color'       => sanitize_hex_color( $data['primary_color'] ),
			'secondary_color'     => sanitize_hex_color( $data['secondary_color'] ),
			'accent_color'        => sanitize_hex_color( $data['accent_color'] ),
			'typography_settings' => json_encode( $data['typography_settings'] ),
			'spacing_settings'    => json_encode( $data['spacing_settings'] ),
			'button_styles'       => json_encode( $data['button_styles'] ),
			'email_theme'         => json_encode( $data['email_theme'] ),
		);

		$result = $wpdb->insert( $this->table_name, $insert_data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a branding preset by ID.
	 *
	 * @param int $id Preset ID.
	 * @return object|null Preset data.
	 */
	public function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
		return $this->format_row( $row );
	}

	/**
	 * Get the branding preset for a specific client.
	 *
	 * @param int $client_id Client ID.
	 * @return object|null Preset data.
	 */
	public function get_by_client( $client_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE client_id = %d LIMIT 1", $client_id ) );
		return $this->format_row( $row );
	}

	/**
	 * Format the row data from JSON strings to arrays.
	 *
	 * @param object|null $row Database row.
	 * @return object|null Formatted row.
	 */
	private function format_row( $row ) {
		if ( ! $row ) {
			return null;
		}

		$json_fields = array( 'typography_settings', 'spacing_settings', 'button_styles', 'email_theme' );
		foreach ( $json_fields as $field ) {
			if ( isset( $row->$field ) ) {
				$row->$field = json_decode( $row->$field, true );
			}
		}

		return $row;
	}

	/**
	 * Update a branding preset.
	 *
	 * @param int   $id   Preset ID.
	 * @param array $data Preset data.
	 * @return bool Success or failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$json_fields = array( 'typography_settings', 'spacing_settings', 'button_styles', 'email_theme' );
		foreach ( $json_fields as $field ) {
			if ( isset( $data[$field] ) && is_array( $data[$field] ) ) {
				$data[$field] = json_encode( $data[$field] );
			}
		}

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id )
		);

		return $result !== false;
	}
}
