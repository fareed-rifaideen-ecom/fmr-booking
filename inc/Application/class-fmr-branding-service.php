<?php
/**
 * Service for handling branding tokens and CSS generation.
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
 * Service for handling branding tokens and CSS generation.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Application
 * @author     FMR
 */
class FMR_Branding_Service {

	/**
	 * Branding repository.
	 *
	 * @var FMR_Branding_Repository
	 */
	private $repository;

	/**
	 * Initialize the class.
	 *
	 * @param FMR_Branding_Repository $repository Branding repository.
	 */
	public function __construct( FMR_Branding_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Get branding tokens for a client.
	 *
	 * @param int $client_id Client ID.
	 * @return array Branding tokens.
	 */
	public function get_tokens( $client_id ) {
		$preset = $this->repository->get_by_client( $client_id );

		if ( ! $preset ) {
			return $this->get_default_tokens();
		}

		return array(
			'colors'     => array(
				'primary'   => $preset->primary_color,
				'secondary' => $preset->secondary_color,
				'accent'    => $preset->accent_color,
			),
			'typography' => $preset->typography_settings,
			'spacing'    => $preset->spacing_settings,
			'buttons'    => $preset->button_styles,
			'email'      => $preset->email_theme,
			'logo'       => $preset->logo_url,
		);
	}

	/**
	 * Generate CSS variables from branding tokens.
	 *
	 * @param int $client_id Client ID.
	 * @return string CSS variables.
	 */
	public function generate_css_variables( $client_id ) {
		$tokens = $this->get_tokens( $client_id );
		$css = ":root {\n";

		// Colors
		foreach ( $tokens['colors'] as $key => $value ) {
			$css .= "  --fmr-color-{$key}: {$value};\n";
		}

		// Typography
		if ( ! empty( $tokens['typography'] ) ) {
			foreach ( $tokens['typography'] as $key => $value ) {
				$css .= "  --fmr-font-{$key}: {$value};\n";
			}
		}

		// Spacing
		if ( ! empty( $tokens['spacing'] ) ) {
			foreach ( $tokens['spacing'] as $key => $value ) {
				$css .= "  --fmr-spacing-{$key}: {$value};\n";
			}
		}

		// Button Styles
		if ( ! empty( $tokens['buttons'] ) ) {
			foreach ( $tokens['buttons'] as $key => $value ) {
				$css .= "  --fmr-button-{$key}: {$value};\n";
			}
		}

		$css .= "}\n";
		return $css;
	}

	/**
	 * Get default branding tokens.
	 *
	 * @return array Default tokens.
	 */
	private function get_default_tokens() {
		return array(
			'colors'     => array(
				'primary'   => '#0073aa',
				'secondary' => '#23282d',
				'accent'    => '#00a0d2',
			),
			'typography' => array(
				'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
				'size'   => '16px',
				'weight' => '400',
			),
			'spacing'    => array(
				'unit' => '8px',
			),
			'buttons'    => array(
				'radius' => '4px',
				'padding' => '10px 20px',
			),
			'email'      => array(
				'header_bg' => '#0073aa',
				'footer_text' => '#666666',
			),
			'logo'       => '',
		);
	}
}
