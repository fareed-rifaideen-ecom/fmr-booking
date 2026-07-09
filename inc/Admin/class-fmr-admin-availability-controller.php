<?php
/**
 * Admin controller for handling schedules and blockouts.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Admin_Availability_Controller {

	private $client_id;

	public function __construct() {
		$this->ensure_client_exists();
		add_action( 'admin_init', array( $this, 'process_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	private function ensure_client_exists() {
		global $wpdb;
		$client_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}fmr_client_profiles LIMIT 1" );
		$this->client_id = $client_id ? (int) $client_id : 1;
	}

	public function register_menus() {
		add_submenu_page( 'fmr-booking', __( 'Schedules', 'fmr-booking' ), __( 'Schedules', 'fmr-booking' ), 'manage_options', 'fmr-booking-schedules', array( $this, 'render_schedules_page' ) );
		add_submenu_page( 'fmr-booking', __( 'Time Off', 'fmr-booking' ), __( 'Time Off', 'fmr-booking' ), 'manage_options', 'fmr-booking-blockouts', array( $this, 'render_blockouts_page' ) );
	}

	public function process_actions() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		global $wpdb;

		// --- PROCESS SCHEDULES ---
		if ( isset( $_POST['fmr_action'] ) && $_POST['fmr_action'] === 'save_schedules' ) {
			check_admin_referer( 'fmr_save_schedules', 'fmr_schedules_nonce' );
			$table = $wpdb->prefix . 'fmr_availability_rules';

			// Wipe existing global rules for a clean save
			$wpdb->delete( $table, array( 'client_id' => $this->client_id, 'target_type' => 'global' ), array( '%d', '%s' ) );

			// Insert updated rules
			if ( ! empty( $_POST['days'] ) && is_array( $_POST['days'] ) ) {
				foreach ( $_POST['days'] as $day_index => $day_data ) {
					if ( isset( $day_data['is_active'] ) && $day_data['is_active'] == '1' ) {
						$wpdb->insert(
							$table,
							array(
								'client_id'   => $this->client_id,
								'target_type' => 'global',
								'day_of_week' => absint( $day_index ),
								'start_time'  => sanitize_text_field( $day_data['start_time'] ),
								'end_time'    => sanitize_text_field( $day_data['end_time'] ),
								'is_active'   => 1,
							),
							array( '%d', '%s', '%d', '%s', '%s', '%d' )
						);
					}
				}
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-schedules', 'msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// --- PROCESS BLOCKOUTS ---
		if ( isset( $_POST['fmr_action'] ) && $_POST['fmr_action'] === 'save_blockout' ) {
			check_admin_referer( 'fmr_save_blockout', 'fmr_blockout_nonce' );
			$table = $wpdb->prefix . 'fmr_blockouts';

			$wpdb->insert(
				$table,
				array(
					'client_id'   => $this->client_id,
					'target_type' => 'global',
					'start_date'  => sanitize_text_field( $_POST['start_date'] ) . ' 00:00:00',
					'end_date'    => sanitize_text_field( $_POST['end_date'] ) . ' 23:59:59',
					'reason'      => sanitize_text_field( $_POST['reason'] ),
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-blockouts', 'msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_blockout' && isset( $_GET['id'] ) ) {
			check_admin_referer( 'delete_blockout_' . $_GET['id'] );
			$wpdb->delete( $wpdb->prefix . 'fmr_blockouts', array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-blockouts', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public function display_notices() {
		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], array( 'fmr-booking-schedules', 'fmr-booking-blockouts' ) ) ) return;
		if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'saved' ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings successfully saved.', 'fmr-booking' ) . '</p></div>';
		}
		if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Item successfully deleted.', 'fmr-booking' ) . '</p></div>';
		}
	}

	// ==========================================
	// SCHEDULES UI
	// ==========================================

	public function render_schedules_page() {
		global $wpdb;
		$rules = $wpdb->get_results( $wpdb->prepare( "SELECT day_of_week, start_time, end_time FROM {$wpdb->prefix}fmr_availability_rules WHERE client_id = %d AND target_type = 'global'", $this->client_id ) );
		
		$schedule_data = array();
		foreach ( $rules as $rule ) {
			$schedule_data[ $rule->day_of_week ] = $rule;
		}

		$days = array( 0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday' );

		echo '<div class="wrap"><h1>' . esc_html__( 'Weekly Schedule', 'fmr-booking' ) . '</h1>';
		echo '<p>' . esc_html__( 'Define your standard operating hours. If a day is unchecked, no bookings can be made on that day.', 'fmr-booking' ) . '</p>';
		?>
		<form method="post" action="">
			<input type="hidden" name="fmr_action" value="save_schedules">
			<?php wp_nonce_field( 'fmr_save_schedules', 'fmr_schedules_nonce' ); ?>
			
			<table class="wp-list-table widefat fixed striped" style="max-width: 600px; margin-top: 20px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Day', 'fmr-booking' ); ?></th>
						<th><?php esc_html_e( 'Start Time', 'fmr-booking' ); ?></th>
						<th><?php esc_html_e( 'End Time', 'fmr-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $days as $index => $label ) : 
						$is_active = isset( $schedule_data[$index] );
						$start     = $is_active ? substr( $schedule_data[$index]->start_time, 0, 5 ) : '09:00';
						$end       = $is_active ? substr( $schedule_data[$index]->end_time, 0, 5 ) : '17:00';
					?>
					<tr>
						<td>
							<label>
								<input type="checkbox" name="days[<?php echo $index; ?>][is_active]" value="1" <?php checked( $is_active ); ?>>
								<strong><?php echo esc_html( $label ); ?></strong>
							</label>
						</td>
						<td><input type="time" name="days[<?php echo $index; ?>][start_time]" value="<?php echo esc_attr( $start ); ?>"></td>
						<td><input type="time" name="days[<?php echo $index; ?>][end_time]" value="<?php echo esc_attr( $end ); ?>"></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><?php submit_button( __( 'Save Schedule', 'fmr-booking' ), 'primary', 'submit', false ); ?></p>
		</form>
		</div>
		<?php
	}

	// ==========================================
	// BLOCKOUTS UI
	// ==========================================

	public function render_blockouts_page() {
		global $wpdb;
		$blockouts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_blockouts WHERE client_id = %d ORDER BY start_date ASC", $this->client_id ) );

		echo '<div class="wrap"><h1>' . esc_html__( 'Time Off & Holidays', 'fmr-booking' ) . '</h1>';
		echo '<p>' . esc_html__( 'Add dates when your business is closed. This overrides the Weekly Schedule.', 'fmr-booking' ) . '</p>';
		?>
		
		<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; max-width: 600px; margin-bottom: 20px;">
			<form method="post" action="">
				<input type="hidden" name="fmr_action" value="save_blockout">
				<?php wp_nonce_field( 'fmr_save_blockout', 'fmr_blockout_nonce' ); ?>
				
				<p>
					<label><strong><?php esc_html_e( 'Start Date:', 'fmr-booking' ); ?></strong><br>
					<input type="date" name="start_date" required></label>
				</p>
				<p>
					<label><strong><?php esc_html_e( 'End Date:', 'fmr-booking' ); ?></strong><br>
					<input type="date" name="end_date" required></label>
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Reason / Holiday Name:', 'fmr-booking' ); ?></strong><br>
					<input type="text" name="reason" class="regular-text" required></label>
				</p>
				<?php submit_button( __( 'Add Blockout', 'fmr-booking' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
			<thead><tr><th>Reason</th><th>Start Date</th><th>End Date</th><th>Action</th></tr></thead>
			<tbody>
				<?php if ( $blockouts ) : ?>
					<?php foreach ( $blockouts as $b ) : 
						$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete_blockout', 'id' => $b->id ) ), 'delete_blockout_' . $b->id );
					?>
						<tr>
							<td><strong><?php echo esc_html( $b->reason ); ?></strong></td>
							<td><?php echo esc_html( date( 'Y-m-d', strtotime( $b->start_date ) ) ); ?></td>
							<td><?php echo esc_html( date( 'Y-m-d', strtotime( $b->end_date ) ) ); ?></td>
							<td><a href="<?php echo esc_url( $delete_url ); ?>" style="color:red;" onclick="return confirm('Are you sure?');">Delete</a></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No time off scheduled.', 'fmr-booking' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		</div>
		<?php
	}
}
