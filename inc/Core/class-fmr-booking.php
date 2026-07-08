<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Booking {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->version = defined( 'FMR_BOOKING_VERSION' ) ? FMR_BOOKING_VERSION : '1.0.0';
		$this->plugin_name = 'fmr-booking';
		$this->loader = new FMR_Booking_Loader();
	}

	public function run() {
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->register_cron_hooks();
		$this->loader->run();
	}

	private function set_locale() {
		$plugin_i18n = new FMR_Booking_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new FMR_Booking_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Controllers
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Client_Controller( new FMR_Client_Repository(), new FMR_Branding_Repository() ), 'register_menus' );
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Service_Controller( new FMR_Service_Repository(), new FMR_Resource_Repository() ), 'register_menus' );
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Reminder_Controller( new FMR_Notification_Repository() ), 'register_menus' );
		
		$availability_service = new FMR_Availability_Service( new FMR_Availability_Repository(), new FMR_Service_Repository(), new FMR_Resource_Repository(), new FMR_Rule_Repository() );
		$booking_service      = new FMR_Booking_Service( $availability_service, new FMR_Service_Repository(), new FMR_Rule_Repository() );
		$approval_service     = new FMR_Approval_Service( new FMR_Approval_Repository(), $booking_service, new FMR_Log_Repository() );
		
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Approval_Controller( new FMR_Approval_Repository(), $approval_service ), 'register_menus' );
	}

	private function define_public_hooks() {
		$plugin_public = new FMR_Booking_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', new FMR_Booking_Shortcode( new FMR_Service_Repository(), new FMR_Client_Repository() ), 'register' );

		// 🚨 FIX: Defer REST and WooCommerce instantiation
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_controllers' );
		$this->loader->add_action( 'plugins_loaded', $this, 'register_woocommerce_integration' );
	}

	public function register_rest_controllers() {
		$availability_service = new FMR_Availability_Service( new FMR_Availability_Repository(), new FMR_Service_Repository(), new FMR_Resource_Repository(), new FMR_Rule_Repository() );
		$rest_controller = new FMR_Booking_REST_Controller( 
			$availability_service, 
			new FMR_Booking_Service( $availability_service, new FMR_Service_Repository(), new FMR_Rule_Repository() ) 
		);
		$rest_controller->register_routes();
	}

	public function register_woocommerce_integration() {
		if ( class_exists( 'WooCommerce' ) ) {
			$availability_service = new FMR_Availability_Service( new FMR_Availability_Repository(), new FMR_Service_Repository(), new FMR_Resource_Repository(), new FMR_Rule_Repository() );
			$wc_adapter = new FMR_WooCommerce_Adapter( new FMR_Booking_Service( $availability_service, new FMR_Service_Repository(), new FMR_Rule_Repository() ) );
			$wc_adapter->init();
		}
	}

	private function register_cron_hooks() {
		add_action( 'fmr_process_reminders', function() {
			$notification_repo = new FMR_Notification_Repository();
			$cron_service = new FMR_Cron_Service( $notification_repo, new FMR_Notification_Service( $notification_repo, new FMR_Branding_Service( new FMR_Branding_Repository() ) ) );
			$cron_service->process_reminders();
		} );
	}

	public function get_plugin_name() { return $this->plugin_name; }
	public function get_loader() { return $this->loader; }
	public function get_version() { return $this->version; }
}
