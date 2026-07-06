<?php
/**
 * The core plugin class.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 */

/**
 * The core plugin class.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Core
 * @author     FMR
 */
class FMR_Booking {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @access   protected
	 * @var      FMR_Booking_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'FMR_BOOKING_VERSION' ) ) {
			$this->version = FMR_BOOKING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'fmr-booking';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - FMR_Booking_Loader. Orchestrates the hooks of the plugin.
	 * - FMR_Booking_i18n. Defines internationalization functionality.
	 * - FMR_Booking_Admin. Defines all hooks for the admin area.
	 * - FMR_Booking_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the hooks with the core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Core/class-fmr-booking-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Core/class-fmr-booking-i18n.php';

		/**
		 * Repositories and Services
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-client-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-branding-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-branding-service.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-service-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-resource-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-availability-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-rule-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-availability-service.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-booking-service.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-notification-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-notification-service.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-approval-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-log-repository.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Application/class-fmr-approval-service.php';

		/**
		 * Cron
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Cron/class-fmr-cron-service.php';

		/**
		 * Integrations
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Integrations/class-fmr-booking-rest-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Integrations/class-fmr-woocommerce-adapter.php';

		/**
		 * The class responsible for defining all hooks that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Admin/class-fmr-booking-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Admin/class-fmr-admin-client-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Admin/class-fmr-admin-service-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Admin/class-fmr-admin-reminder-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Admin/class-fmr-admin-approval-controller.php';

		/**
		 * The class responsible for defining all hooks that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Frontend/class-fmr-booking-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'Frontend/class-fmr-booking-shortcode.php';

		$this->loader = new FMR_Booking_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the FMR_Booking_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new FMR_Booking_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new FMR_Booking_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Client and Branding Admin Controller
		$client_repo   = new FMR_Client_Repository();
		$branding_repo = new FMR_Branding_Repository();
		$admin_client  = new FMR_Admin_Client_Controller( $client_repo, $branding_repo );
		$this->loader->add_action( 'admin_menu', $admin_client, 'register_menus' );

		// Service and Resource Admin Controller
		$service_repo  = new FMR_Service_Repository();
		$resource_repo = new FMR_Resource_Repository();
		$admin_service = new FMR_Admin_Service_Controller( $service_repo, $resource_repo );
		$this->loader->add_action( 'admin_menu', $admin_service, 'register_menus' );

		// Reminder Admin Controller
		$notification_repo = new FMR_Notification_Repository();
		$admin_reminder    = new FMR_Admin_Reminder_Controller( $notification_repo );
		$this->loader->add_action( 'admin_menu', $admin_reminder, 'register_menus' );

		// Approval Admin Controller
		$approval_repo    = new FMR_Approval_Repository();
		$log_repo         = new FMR_Log_Repository();
		$availability_repo = new FMR_Availability_Repository();
		$service_repo      = new FMR_Service_Repository();
		$resource_repo     = new FMR_Resource_Repository();
		$rule_repo         = new FMR_Rule_Repository();
		
		$availability_service = new FMR_Availability_Service( $availability_repo, $service_repo, $resource_repo, $rule_repo );
		$booking_service      = new FMR_Booking_Service( $availability_service, $service_repo, $rule_repo );
		$approval_service     = new FMR_Approval_Service( $approval_repo, $booking_service, $log_repo );
		$admin_approval       = new FMR_Admin_Approval_Controller( $approval_repo, $approval_service );
		
		$this->loader->add_action( 'admin_menu', $admin_approval, 'register_menus' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new FMR_Booking_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Shortcode
		$service_repo = new FMR_Service_Repository();
		$client_repo  = new FMR_Client_Repository();
		$shortcode    = new FMR_Booking_Shortcode( $service_repo, $client_repo );
		$this->loader->add_action( 'init', $shortcode, 'register' );

		// REST API Integration
		$availability_repo = new FMR_Availability_Repository();
		$service_repo      = new FMR_Service_Repository();
		$resource_repo     = new FMR_Resource_Repository();
		$rule_repo         = new FMR_Rule_Repository();
		
		$availability_service = new FMR_Availability_Service( $availability_repo, $service_repo, $resource_repo, $rule_repo );
		$booking_service      = new FMR_Booking_Service( $availability_service, $service_repo, $rule_repo );
		$rest_controller      = new FMR_Booking_REST_Controller( $availability_service, $booking_service );
		$this->loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );

		// WooCommerce Integration
		$wc_adapter = new FMR_WooCommerce_Adapter( $booking_service );
		$wc_adapter->init();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
		$this->register_cron_jobs();
	}

	/**
	 * Register scheduled cron jobs.
	 */
	private function register_cron_jobs() {
		$notification_repo    = new FMR_Notification_Repository();
		$branding_repo        = new FMR_Branding_Repository();
		$branding_service     = new FMR_Branding_Service( $branding_repo );
		$notification_service = new FMR_Notification_Service( $notification_repo, $branding_service );
		$cron_service         = new FMR_Cron_Service( $notification_repo, $notification_service );

		if ( ! wp_next_scheduled( 'fmr_process_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'fmr_process_reminders' );
		}

		add_action( 'fmr_process_reminders', array( $cron_service, 'process_reminders' ) );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the core plugin.
	 *
	 * @since     1.0.0
	 * @return    FMR_Booking_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
