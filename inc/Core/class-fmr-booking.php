<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Booking {

	protected $loader;
	protected $plugin_name;
	protected $version;

	// The Dependency Container
	private $container = array(); 

	public function __construct() {
		$this->version = defined( 'FMR_BOOKING_VERSION' ) ? FMR_BOOKING_VERSION : '1.0.0';
		$this->plugin_name = 'fmr-booking';
		$this->loader = new FMR_Booking_Loader();
		
		$this->init_dependencies();
	}

	/**
	 * Instantiates all repositories and services ONCE.
	 */
	private function init_dependencies() {
		// Repositories
		$this->container['repo.client']       = new FMR_Client_Repository();
		$this->container['repo.branding']     = new FMR_Branding_Repository();
		$this->container['repo.service']      = new FMR_Service_Repository();
		$this->container['repo.resource']     = new FMR_Resource_Repository();
		$this->container['repo.rule']         = new FMR_Rule_Repository();
		$this->container['repo.avail']        = new FMR_Availability_Repository();
		$this->container['repo.avail_rule']   = new FMR_Availability_Rule_Repository();
		$this->container['repo.approval']     = new FMR_Approval_Repository();
		$this->container['repo.log']          = new FMR_Log_Repository();
		$this->container['repo.notification'] = new FMR_Notification_Repository();

		// Services
		$this->container['service.availability'] = new FMR_Availability_Service(
			$this->container['repo.avail'],
			$this->container['repo.service'],
			$this->container['repo.resource'],
			$this->container['repo.rule'],
			$this->container['repo.avail_rule']
		);

		$this->container['service.booking'] = new FMR_Booking_Service(
			$this->container['service.availability'],
			$this->container['repo.service'],
			$this->container['repo.rule']
		);

		$this->container['service.approval'] = new FMR_Approval_Service(
			$this->container['repo.approval'],
			$this->container['service.booking'],
			$this->container['repo.log']
		);
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

		// Inject from container
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Client_Controller( $this->container['repo.client'], $this->container['repo.branding'] ), 'register_menus' );
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Service_Controller( $this->container['repo.service'], $this->container['repo.resource'], $this->container['repo.rule'] ), 'register_menus' );
		
		// 🚨 NEW: Inject the Availability Controller
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Availability_Controller(), 'register_menus' );

		$this->loader->add_action( 'admin_menu', new FMR_Admin_Reminder_Controller( $this->container['repo.notification'] ), 'register_menus' );
		$this->loader->add_action( 'admin_menu', new FMR_Admin_Approval_Controller( $this->container['repo.approval'], $this->container['service.approval'] ), 'register_menus' );
	}

	private function define_public_hooks() {
		$plugin_public = new FMR_Booking_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		$this->loader->add_action( 'init', new FMR_Booking_Shortcode( $this->container['repo.service'], $this->container['repo.client'] ), 'register' );

		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_controllers' );
		$this->loader->add_action( 'plugins_loaded', $this, 'register_woocommerce_integration' );
	}

	public function register_rest_controllers() {
		$rest_controller = new FMR_Booking_REST_Controller( 
			$this->container['service.availability'], 
			$this->container['service.booking']
		);
		$rest_controller->register_routes();
	}

	public function register_woocommerce_integration() {
		if ( class_exists( 'WooCommerce' ) ) {
			$wc_adapter = new FMR_WooCommerce_Adapter( $this->container['service.booking'] );
			$wc_adapter->init();
		}
	}

	private function register_cron_hooks() {
		add_action( 'fmr_process_reminders', function() {
			// Container isn't available in this closure context easily, so we re-instantiate just for the async Cron
			$notification_repo = new FMR_Notification_Repository();
			$cron_service = new FMR_Cron_Service( 
				$notification_repo, 
				new FMR_Notification_Service( $notification_repo, new FMR_Branding_Service( new FMR_Branding_Repository() ) ) 
			);
			$cron_service->process_reminders();
		} );
	}

	public function get_plugin_name() { return $this->plugin_name; }
	public function get_loader() { return $this->loader; }
	public function get_version() { return $this->version; }
}
