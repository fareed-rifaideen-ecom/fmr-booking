<?php
/**
 * Admin controller for handling services and resources.
 *
 * @link       https://fmr.com
 * @since      1.0.0
 * @package    FMR_Booking
 * @subpackage FMR_Booking/inc/Admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class FMR_Admin_Service_Controller {

	private $service_repo;
	private $resource_repo;
	private $client_id;

	public function __construct( FMR_Service_Repository $service_repo, FMR_Resource_Repository $resource_repo ) {
		$this->service_repo  = $service_repo;
		$this->resource_repo = $resource_repo;

		$this->ensure_client_exists();

		add_action( 'admin_init', array( $this, 'process_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	private function ensure_client_exists() {
		global $wpdb;
		$client_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}fmr_client_profiles LIMIT 1" );
		
		if ( ! $client_id ) {
			$wpdb->insert( 
				$wpdb->prefix . 'fmr_client_profiles', 
				array( 'client_name' => 'Default Client', 'slug' => 'default-client' ),
				array( '%s', '%s' )
			);
			$this->client_id = (int) $wpdb->insert_id;
		} else {
			$this->client_id = (int) $client_id;
		}
	}

	public function register_menus() {
		add_submenu_page( 'fmr-booking', __( 'Services', 'fmr-booking' ), __( 'Services', 'fmr-booking' ), 'manage_options', 'fmr-booking-services', array( $this, 'render_services_page' ) );
		add_submenu_page( 'fmr-booking', __( 'Resources', 'fmr-booking' ), __( 'Resources', 'fmr-booking' ), 'manage_options', 'fmr-booking-resources', array( $this, 'render_resources_page' ) );
	}

	public function process_actions() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		global $wpdb;

		// --- PROCESS SERVICES ---
		if ( isset( $_POST['fmr_action'] ) && $_POST['fmr_action'] === 'save_service' ) {
			check_admin_referer( 'fmr_save_service', 'fmr_service_nonce' );
			$table = $wpdb->prefix . 'fmr_services';
			
			$data = array(
				'client_id'     => $this->client_id,
				'title'         => sanitize_text_field( $_POST['title'] ),
				'description'   => sanitize_textarea_field( $_POST['description'] ),
				'duration'      => absint( $_POST['duration'] ),
				'buffer_before' => isset( $_POST['buffer_before'] ) ? absint( $_POST['buffer_before'] ) : 0,
				'buffer_after'  => isset( $_POST['buffer_after'] ) ? absint( $_POST['buffer_after'] ) : 0,
				'price'         => floatval( $_POST['price'] ),
				'is_active'     => isset( $_POST['is_active'] ) ? 1 : 0,
			);

			if ( ! empty( $_POST['service_id'] ) ) {
				$result = $wpdb->update( $table, $data, array( 'id' => absint( $_POST['service_id'] ) ), array( '%d', '%s', '%s', '%d', '%d', '%d', '%f', '%d' ), array( '%d' ) );
				$message = ( $result !== false ) ? 'updated' : 'error';
			} else {
				$result = $wpdb->insert( $table, $data, array( '%d', '%s', '%s', '%d', '%d', '%d', '%f', '%d' ) );
				$message = ( $result !== false ) ? 'created' : 'error';
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-services', 'msg' => $message ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_service' && isset( $_GET['id'] ) ) {
			check_admin_referer( 'delete_service_' . $_GET['id'] );
			$result = $wpdb->delete( $wpdb->prefix . 'fmr_services', array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
			$message = ( $result !== false ) ? 'deleted' : 'error';
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-services', 'msg' => $message ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// --- PROCESS RESOURCES ---
		if ( isset( $_POST['fmr_action'] ) && $_POST['fmr_action'] === 'save_resource' ) {
			check_admin_referer( 'fmr_save_resource', 'fmr_resource_nonce' );
			$table = $wpdb->prefix . 'fmr_resources';
			
			$valid_types = array( 'staff', 'room', 'equipment', 'virtual' );
			$type = in_array( $_POST['type'], $valid_types, true ) ? $_POST['type'] : 'staff';

			$data = array(
				'client_id'   => $this->client_id,
				'name'        => sanitize_text_field( $_POST['name'] ),
				'type'        => $type,
				'capacity'    => absint( $_POST['capacity'] ),
				'description' => sanitize_textarea_field( $_POST['description'] ),
				'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
			);

			if ( ! empty( $_POST['resource_id'] ) ) {
				$result = $wpdb->update( $table, $data, array( 'id' => absint( $_POST['resource_id'] ) ), array( '%d', '%s', '%s', '%d', '%s', '%d' ), array( '%d' ) );
				$message = ( $result !== false ) ? 'updated' : 'error';
			} else {
				$result = $wpdb->insert( $table, $data, array( '%d', '%s', '%s', '%d', '%s', '%d' ) );
				$message = ( $result !== false ) ? 'created' : 'error';
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-resources', 'msg' => $message ), admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_resource' && isset( $_GET['id'] ) ) {
			check_admin_referer( 'delete_resource_' . $_GET['id'] );
			$result = $wpdb->delete( $wpdb->prefix . 'fmr_resources', array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
			$message = ( $result !== false ) ? 'deleted' : 'error';
			wp_safe_redirect( add_query_arg( array( 'page' => 'fmr-booking-resources', 'msg' => $message ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	public function display_notices() {
		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], array( 'fmr-booking-services', 'fmr-booking-resources' ) ) ) return;
		if ( ! isset( $_GET['msg'] ) ) return;

		$msgs = array(
			'created' => __( 'Item successfully created.', 'fmr-booking' ),
			'updated' => __( 'Item successfully updated.', 'fmr-booking' ),
			'deleted' => __( 'Item successfully deleted.', 'fmr-booking' ),
			'error'   => __( 'Database error: Action failed.', 'fmr-booking' ),
		);

		if ( isset( $msgs[ $_GET['msg'] ] ) ) {
			$class = ( $_GET['msg'] === 'error' ) ? 'notice-error' : 'notice-success';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msgs[ $_GET['msg'] ] ) . '</p></div>';
		}
	}

	// ==========================================
	// SERVICES UI
	// ==========================================

	public function render_services_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__( 'Services', 'fmr-booking' ) . '</h1>';
		
		if ( $action === 'list' ) {
			echo '<a href="' . esc_url( add_query_arg( 'action', 'add' ) ) . '" class="page-title-action">' . esc_html__( 'Add New', 'fmr-booking' ) . '</a><hr class="wp-header-end">';
			$this->render_services_list();
		} else {
			echo '<hr class="wp-header-end">';
			$this->render_service_form();
		}
		echo '</div>';
	}

	private function render_services_list() {
		global $wpdb;
		$services = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_services WHERE client_id = %d ORDER BY title ASC", $this->client_id ) );

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr><th>' . esc_html__( 'Title', 'fmr-booking' ) . '</th><th>' . esc_html__( 'Duration', 'fmr-booking' ) . '</th><th>' . esc_html__( 'Price', 'fmr-booking' ) . '</th><th>' . esc_html__( 'Status', 'fmr-booking' ) . '</th></tr></thead><tbody>';

		if ( $services ) {
			foreach ( $services as $service ) {
				$edit_url = add_query_arg( array( 'action' => 'edit', 'id' => $service->id ) );
				$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete_service', 'id' => $service->id ) ), 'delete_service_' . $service->id );
				
				echo '<tr>';
				echo '<td><strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $service->title ) . '</a></strong>';
				echo '<div class="row-actions"><span class="edit"><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'fmr-booking' ) . '</a> | </span><span class="trash"><a href="' . esc_url( $delete_url ) . '" class="submitdelete" onclick="return confirm(\'Are you sure?\');">' . esc_html__( 'Delete', 'fmr-booking' ) . '</a></span></div></td>';
				echo '<td>' . esc_html( $service->duration ) . ' mins</td>';
				echo '<td>$' . esc_html( number_format( $service->price, 2 ) ) . '</td>';
				echo '<td>' . ( $service->is_active ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>' ) . '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="4">' . esc_html__( 'No services found.', 'fmr-booking' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private function render_service_form() {
		global $wpdb;
		$service_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$service = (object) array( 'title' => '', 'description' => '', 'duration' => 30, 'buffer_before' => 0, 'buffer_after' => 0, 'price' => '0.00', 'is_active' => 1 );

		if ( $service_id ) {
			$service = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_services WHERE id = %d", $service_id ) );
		}
		?>
		<form method="post" action="">
			<input type="hidden" name="fmr_action" value="save_service">
			<input type="hidden" name="service_id" value="<?php echo esc_attr( $service_id ); ?>">
			<?php wp_nonce_field( 'fmr_save_service', 'fmr_service_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="title"><?php esc_html_e( 'Service Title', 'fmr-booking' ); ?></label></th>
					<td><input name="title" type="text" id="title" value="<?php echo esc_attr( $service->title ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="description"><?php esc_html_e( 'Description', 'fmr-booking' ); ?></label></th>
					<td><textarea name="description" id="description" rows="4" class="regular-text"><?php echo esc_textarea( $service->description ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="duration"><?php esc_html_e( 'Duration (minutes)', 'fmr-booking' ); ?></label></th>
					<td><input name="duration" type="number" id="duration" value="<?php echo esc_attr( $service->duration ); ?>" class="small-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="price"><?php esc_html_e( 'Price', 'fmr-booking' ); ?></label></th>
					<td>$<input name="price" type="number" step="0.01" id="price" value="<?php echo esc_attr( $service->price ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'fmr-booking' ); ?></th>
					<td>
						<label for="is_active">
							<input name="is_active" type="checkbox" id="is_active" value="1" <?php checked( $service->is_active, 1 ); ?>>
							<?php esc_html_e( 'Active (Bookable by clients)', 'fmr-booking' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Service', 'fmr-booking' ) ); ?>
		</form>
		<?php
	}

	// ==========================================
	// RESOURCES UI
	// ==========================================

	public function render_resources_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__( 'Resources', 'fmr-booking' ) . '</h1>';
		
		if ( $action === 'list' ) {
			echo '<a href="' . esc_url( add_query_arg( 'action', 'add' ) ) . '" class="page-title-action">' . esc_html__( 'Add New', 'fmr-booking' ) . '</a><hr class="wp-header-end">';
			$this->render_resources_list();
		} else {
			echo '<hr class="wp-header-end">';
			$this->render_resource_form();
		}
		echo '</div>';
	}

	private function render_resources_list() {
		global $wpdb;
		$resources = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_resources WHERE client_id = %d ORDER BY name ASC", $this->client_id ) );

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr><th>' . esc_html__( 'Name', 'fmr-booking' ) . '</th><th>' . esc_html__( 'Type', 'fmr-booking' ) . '</th><th>' . esc_html__( 'Capacity', 'fmr-booking' ) . '</th><th>' . esc_html__( 'Status', 'fmr-booking' ) . '</th></tr></thead><tbody>';

		if ( $resources ) {
			foreach ( $resources as $resource ) {
				$edit_url = add_query_arg( array( 'action' => 'edit', 'id' => $resource->id ) );
				$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete_resource', 'id' => $resource->id ) ), 'delete_resource_' . $resource->id );
				
				echo '<tr>';
				echo '<td><strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $resource->name ) . '</a></strong>';
				echo '<div class="row-actions"><span class="edit"><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'fmr-booking' ) . '</a> | </span><span class="trash"><a href="' . esc_url( $delete_url ) . '" class="submitdelete" onclick="return confirm(\'Are you sure?\');">' . esc_html__( 'Delete', 'fmr-booking' ) . '</a></span></div></td>';
				echo '<td>' . esc_html( ucfirst( $resource->type ) ) . '</td>';
				echo '<td>' . esc_html( $resource->capacity ) . '</td>';
				echo '<td>' . ( $resource->is_active ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>' ) . '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="4">' . esc_html__( 'No resources found.', 'fmr-booking' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private function render_resource_form() {
		global $wpdb;
		$resource_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$resource = (object) array( 'name' => '', 'description' => '', 'type' => 'staff', 'capacity' => 1, 'is_active' => 1 );

		if ( $resource_id ) {
			$resource = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fmr_resources WHERE id = %d", $resource_id ) );
		}
		?>
		<form method="post" action="">
			<input type="hidden" name="fmr_action" value="save_resource">
			<input type="hidden" name="resource_id" value="<?php echo esc_attr( $resource_id ); ?>">
			<?php wp_nonce_field( 'fmr_save_resource', 'fmr_resource_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="name"><?php esc_html_e( 'Resource Name', 'fmr-booking' ); ?></label></th>
					<td><input name="name" type="text" id="name" value="<?php echo esc_attr( $resource->name ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="type"><?php esc_html_e( 'Resource Type', 'fmr-booking' ); ?></label></th>
					<td>
						<select name="type" id="type">
							<option value="staff" <?php selected( $resource->type, 'staff' ); ?>><?php esc_html_e( 'Staff Member', 'fmr-booking' ); ?></option>
							<option value="room" <?php selected( $resource->type, 'room' ); ?>><?php esc_html_e( 'Room', 'fmr-booking' ); ?></option>
							<option value="equipment" <?php selected( $resource->type, 'equipment' ); ?>><?php esc_html_e( 'Equipment', 'fmr-booking' ); ?></option>
							<option value="virtual" <?php selected( $resource->type, 'virtual' ); ?>><?php esc_html_e( 'Virtual (Zoom/Meet)', 'fmr-booking' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="capacity"><?php esc_html_e( 'Capacity', 'fmr-booking' ); ?></label></th>
					<td>
						<input name="capacity" type="number" id="capacity" value="<?php echo esc_attr( $resource->capacity ); ?>" class="small-text" min="1" required>
						<p class="description"><?php esc_html_e( 'How many concurrent bookings can this resource handle?', 'fmr-booking' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="description"><?php esc_html_e( 'Description', 'fmr-booking' ); ?></label></th>
					<td><textarea name="description" id="description" rows="4" class="regular-text"><?php echo esc_textarea( $service->description ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'fmr-booking' ); ?></th>
					<td>
						<label for="is_active">
							<input name="is_active" type="checkbox" id="is_active" value="1" <?php checked( $resource->is_active, 1 ); ?>>
							<?php esc_html_e( 'Active (Can be assigned to services)', 'fmr-booking' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Resource', 'fmr-booking' ) ); ?>
		</form>
		<?php
	}
}
