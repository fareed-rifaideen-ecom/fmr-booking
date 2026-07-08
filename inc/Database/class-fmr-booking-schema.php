<?php
/**
 * The database schema definition.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Database
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The database schema definition.
 *
 * Defines the custom tables and their structure for the plugin.
 *
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Database
 * @author     FMR
 */
class FMR_Booking_Schema {

	/**
	 * Get the SQL for creating/updating custom tables.
	 *
	 * @since    1.0.0
	 * @return   array    List of SQL statements.
	 */
	public static function get_schema() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$tables = array();

		// 1. Client Profiles
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_client_profiles (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_name varchar(255) NOT NULL,
			slug varchar(100) NOT NULL,
			settings longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		// 2. Branding Presets
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_branding_presets (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id bigint(20) UNSIGNED NOT NULL,
			preset_name varchar(255) NOT NULL,
			logo_url text DEFAULT NULL,
			primary_color varchar(20) DEFAULT '#000000',
			secondary_color varchar(20) DEFAULT '#ffffff',
			accent_color varchar(20) DEFAULT '#cccccc',
			typography_settings longtext DEFAULT NULL,
			spacing_settings longtext DEFAULT NULL,
			button_styles longtext DEFAULT NULL,
			email_theme longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY client_id (client_id),
			CONSTRAINT fk_branding_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE CASCADE
		) $charset_collate;";

		// 3. Services
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_services (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id bigint(20) UNSIGNED NOT NULL,
			title varchar(255) NOT NULL,
			description text DEFAULT NULL,
			duration int(11) NOT NULL DEFAULT 30,
			buffer_before int(11) DEFAULT 0,
			buffer_after int(11) DEFAULT 0,
			price decimal(10,2) DEFAULT 0.00,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY client_id (client_id),
			CONSTRAINT fk_service_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE CASCADE
		) $charset_collate;";

		// 4. Resources (Staff, Rooms, Equipment)
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_resources (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id bigint(20) UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			type enum('staff', 'room', 'equipment', 'virtual') NOT NULL,
			capacity int(11) DEFAULT 1,
			description text DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY client_id (client_id),
			CONSTRAINT fk_resource_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE CASCADE
		) $charset_collate;";

		// 5. Service-Resource Rules
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_service_resource_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			service_id bigint(20) UNSIGNED NOT NULL,
			resource_id bigint(20) UNSIGNED NOT NULL,
			rule_type varchar(50) DEFAULT 'required',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY service_id (service_id),
			KEY resource_id (resource_id),
			CONSTRAINT fk_rule_service FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}fmr_services(id) ON DELETE CASCADE,
			CONSTRAINT fk_rule_resource FOREIGN KEY (resource_id) REFERENCES {$wpdb->prefix}fmr_resources(id) ON DELETE CASCADE
		) $charset_collate;";

		// 6. Appointments
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_appointments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			uuid varchar(36) NOT NULL,
			secure_token varchar(64) NOT NULL,
			client_id bigint(20) UNSIGNED NOT NULL,
			service_id bigint(20) UNSIGNED NOT NULL,
			customer_name varchar(255) NOT NULL,
			customer_email varchar(255) NOT NULL,
			customer_phone varchar(50) DEFAULT NULL,
			booking_mode enum('in_person', 'virtual') DEFAULT 'in_person',
			start_time datetime NOT NULL,
			end_time datetime NOT NULL,
			status enum('pending', 'approved', 'cancelled', 'rescheduled', 'completed') DEFAULT 'pending',
			notes text DEFAULT NULL,
			intake_answers_json longtext DEFAULT NULL,
			wc_order_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			UNIQUE KEY secure_token (secure_token),
			KEY client_id (client_id),
			KEY service_time (service_id, start_time, end_time),
			CONSTRAINT fk_appt_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE CASCADE,
			CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}fmr_services(id) ON DELETE CASCADE
		) $charset_collate;";

		// 7. Resource Reservations
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_resource_reservations (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id bigint(20) UNSIGNED NOT NULL,
			resource_id bigint(20) UNSIGNED NOT NULL,
			start_time datetime NOT NULL,
			end_time datetime NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			KEY resource_id (resource_id),
			KEY start_time (start_time),
			CONSTRAINT fk_resv_appt FOREIGN KEY (appointment_id) REFERENCES {$wpdb->prefix}fmr_appointments(id) ON DELETE CASCADE,
			CONSTRAINT fk_resv_resource FOREIGN KEY (resource_id) REFERENCES {$wpdb->prefix}fmr_resources(id) ON DELETE CASCADE
		) $charset_collate;";

		// 8. Slot Locks (Temporary locks during checkout)
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_slot_locks (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id varchar(255) NOT NULL,
			service_id bigint(20) UNSIGNED NOT NULL,
			start_time datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY session_id (session_id),
			KEY expires_at (expires_at),
			CONSTRAINT fk_lock_service FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}fmr_services(id) ON DELETE CASCADE
		) $charset_collate;";

		// 9. Reminder Queue
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_reminder_queue (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id bigint(20) UNSIGNED NOT NULL,
			reminder_type varchar(50) NOT NULL,
			scheduled_at datetime NOT NULL,
			status enum('pending', 'sent', 'failed') DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			KEY scheduled_at (scheduled_at),
			CONSTRAINT fk_reminder_appt FOREIGN KEY (appointment_id) REFERENCES {$wpdb->prefix}fmr_appointments(id) ON DELETE CASCADE
		) $charset_collate;";

		// 10. Notification Logs
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_notification_logs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id bigint(20) UNSIGNED DEFAULT NULL,
			recipient varchar(255) NOT NULL,
			subject varchar(255) DEFAULT NULL,
			content longtext DEFAULT NULL,
			sent_at datetime DEFAULT CURRENT_TIMESTAMP,
			status varchar(50) DEFAULT 'success',
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			CONSTRAINT fk_log_appt FOREIGN KEY (appointment_id) REFERENCES {$wpdb->prefix}fmr_appointments(id) ON DELETE SET NULL
		) $charset_collate;";

		// 11. Approval Requests
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_approval_requests (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			appointment_id bigint(20) UNSIGNED NOT NULL,
			request_type enum('booking', 'reschedule', 'cancellation') NOT NULL,
			status enum('pending', 'approved', 'rejected') DEFAULT 'pending',
			requested_at datetime DEFAULT CURRENT_TIMESTAMP,
			actioned_at datetime DEFAULT NULL,
			actioned_by bigint(20) UNSIGNED DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			CONSTRAINT fk_approval_appt FOREIGN KEY (appointment_id) REFERENCES {$wpdb->prefix}fmr_appointments(id) ON DELETE CASCADE
		) $charset_collate;";

		// 12. Activity Logs
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_activity_logs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id bigint(20) UNSIGNED DEFAULT NULL,
			object_id bigint(20) UNSIGNED DEFAULT NULL,
			object_type varchar(100) DEFAULT NULL,
			action varchar(100) NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			details longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY client_id (client_id),
			KEY object_id (object_id),
			CONSTRAINT fk_activity_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE SET NULL
		) $charset_collate;";

		// 13. Attachments
		// Cannot use explicit FK here because object_id is polymorphic (relies on object_type).
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_attachments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			object_id bigint(20) UNSIGNED NOT NULL,
			object_type varchar(100) NOT NULL,
			file_url text NOT NULL,
			file_name varchar(255) NOT NULL,
			file_type varchar(100) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY object_id (object_id)
		) $charset_collate;";

		// 14. Availability Rules
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_availability_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id bigint(20) UNSIGNED NOT NULL,
			target_type enum('service', 'resource', 'global') NOT NULL,
			target_id bigint(20) UNSIGNED DEFAULT NULL,
			day_of_week tinyint(1) NOT NULL COMMENT '0=Sun, 1=Mon, etc.',
			start_time time NOT NULL,
			end_time time NOT NULL,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY target_rule (target_type, target_id),
			CONSTRAINT fk_avail_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE CASCADE
		) $charset_collate;";

		// 15. Blockouts
		$tables[] = "CREATE TABLE {$wpdb->prefix}fmr_blockouts (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id bigint(20) UNSIGNED NOT NULL,
			target_type enum('service', 'resource', 'global') NOT NULL,
			target_id bigint(20) UNSIGNED DEFAULT NULL,
			start_date datetime NOT NULL,
			end_date datetime NOT NULL,
			reason varchar(255) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY target_blockout (target_type, target_id),
			KEY dates (start_date, end_date),
			CONSTRAINT fk_blockout_client FOREIGN KEY (client_id) REFERENCES {$wpdb->prefix}fmr_client_profiles(id) ON DELETE CASCADE
		) $charset_collate;";

		return $tables;
	}
}
