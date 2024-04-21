<?php
/**
 * Plugin Name: TaskMaster Pro
 * Description: A to-do list manager for WordPress.
 * Version: 0.1
 * Author: Ivan Rusev
 */
/** @noinspection PhpUndefinedConstantInspection */
// Function to run upon plugin activation to create the 'tasks' table
function taskmaster_pro_install() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$tasks_table_name = $wpdb->prefix . 'tasks';
	$lists_table_name = $wpdb->prefix . 'task_lists';

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// Create the 'task_lists' table with the user_id foreign key
	$sql_lists = "CREATE TABLE IF NOT EXISTS $lists_table_name (
        list_id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        list_name varchar(255) NOT NULL,
        visibility ENUM('public', 'private') DEFAULT 'private',
        PRIMARY KEY (list_id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";
	dbDelta($sql_lists);

	// Create the 'tasks' table including the 'list_id' column and foreign key in the initial statement
	$sql_tasks = "CREATE TABLE IF NOT EXISTS $tasks_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        list_id mediumint(9) UNSIGNED NOT NULL,
        completed BOOLEAN NOT NULL DEFAULT FALSE,
        PRIMARY KEY (id),
        FOREIGN KEY (list_id) REFERENCES $lists_table_name(list_id) ON DELETE CASCADE
    ) $charset_collate;";
	dbDelta($sql_tasks);
}

// Register the installation function to run when the plugin is activated
register_activation_hook(__FILE__, 'taskmaster_pro_install');


// Register the activation hook
register_activation_hook(__FILE__, 'taskmaster_pro_install');


function taskmaster_pro_menu() {
	add_menu_page('TaskMaster Pro', 'Tasks', 'edit_posts', 'taskmaster_pro', 'taskmaster_pro_tasks_page');
}
add_action('admin_menu', 'taskmaster_pro_menu');


function taskmaster_pro_tasks_page() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'tasks';

	// Handle task form submission
	if ('POST' == $_SERVER['REQUEST_METHOD']) {
		if (isset($_POST['newtask'])) {
			// Add new task
			$wpdb->insert($table_name, [
				'title' => sanitize_text_field($_POST['title']),
				'description' => sanitize_textarea_field($_POST['description'])
			]);
		} elseif (isset($_POST['edittask'])) {
			// Save updated task
			$wpdb->update($table_name, [
				'title' => sanitize_text_field($_POST['title']),
				'description' => sanitize_textarea_field($_POST['description'])
			], ['id' => intval($_POST['id'])]);
		} elseif (isset($_POST['delete'])) {
			// Delete task
			$wpdb->delete($table_name, ['id' => intval($_POST['id'])]);
		}
	}

	$edit_task = null;
	if (isset($_GET['edit'])) {
		// Get task details for editing
		$edit_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
	}

	// Fetch tasks from database
	$tasks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

	// Include the view for displaying tasks
	include('tasks_page_view.php');
}


function taskmaster_pro_display_tasks_shortcode() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'tasks';
	$tasks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

	ob_start(); // Start output buffering to capture the HTML output
	?>
	<div class="taskmaster-pro-tasks-list">
		<?php foreach ($tasks as $task): ?>
			<div class="task">
				<h2><?= esc_html($task->title); ?></h2>
				<p><?= esc_html($task->description); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean(); // Return the buffered output
}
add_shortcode('taskmaster_pro_tasks', 'taskmaster_pro_display_tasks_shortcode');


function taskmaster_pro_enqueue_styles() {
	wp_enqueue_style('taskmaster_pro_styles', plugins_url('/css/taskmaster_pro_styles.css', __FILE__));

}
add_action('admin_enqueue_scripts', 'taskmaster_pro_enqueue_styles');


function taskmaster_pro_register_block() {
	wp_register_script(
		'taskmaster-pro-block',
		plugins_url('/blocks/block.js', __FILE__),
		array('wp-blocks', 'wp-element')
	);

	register_block_type('taskmaster-pro/tasks-block', array(
		'editor_script' => 'taskmaster-pro-block',
		'render_callback' => 'taskmaster_pro_display_tasks_shortcode', // Use the same render callback as the shortcode
	));
}
add_action('init', 'taskmaster_pro_register_block');

function create_task_list($user_id, $list_name, $visibility) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'task_lists';

	$wpdb->insert($table_name, [
		'user_id' => $user_id,
		'list_name' => sanitize_text_field($list_name),
		'visibility' => in_array($visibility, ['public', 'private']) ? $visibility : 'private'
	]);
}
function handle_task_submission() {
	if (isset($_POST['newtask'])) {  // Check if the form was submitted
		global $wpdb;
		$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
		$list_id = isset($_POST['list_id']) ? intval($_POST['list_id']) : 0;

		if (!empty($title) && !empty($list_id)) {  // Check if the title and list_id are not empty
			$table_name = $wpdb->prefix . 'tasks';

			$result = $wpdb->insert(
				$table_name,
				[
					'title' => $title,
					'description' => $description,
					'list_id' => $list_id,
					'completed' => 0  // Assuming 'completed' column exists and default is 0 (false)
				],
				['%s', '%s', '%d', '%d']
			);

			if (false === $result) {
				// Handle error; possibly output a message to the user
				error_log('Error inserting new task: ' . $wpdb->last_error);
			} else {
				// Handle success; possibly redirect or output a success message
				 wp_redirect('admin.php?page=taskmaster_pro');
				// exit;
			}
		}
	}
}
add_action('admin_post_add_new_task', 'handle_task_submission');  // Use 'admin_post_{action}' if this is in admin area


function add_task_to_list($list_id, $title, $description) {
	global $wpdb;
	$tasks_table = $wpdb->prefix . 'tasks';
	$wpdb->insert($tasks_table, [
		'list_id' => $list_id,
		'title' => sanitize_text_field($title),
		'description' => sanitize_textarea_field($description)
	]);
}

function fetch_user_tasks($user_id) {
	global $wpdb;
	$tasks_table = $wpdb->prefix . 'tasks';
	$lists_table = $wpdb->prefix . 'task_lists';
	$sql = $wpdb->prepare(
		"SELECT t.*, l.list_name FROM $tasks_table t
         INNER JOIN $lists_table l ON t.list_id = l.list_id
         WHERE l.user_id = %d OR l.visibility = 'public'",
		$user_id
	);
	return $wpdb->get_results($sql);
}

function fetch_all_tasks_for_admin() {
	if (current_user_can('administrator')) {
		global $wpdb;
		$tasks_table = $wpdb->prefix . 'tasks';
		$lists_table = $wpdb->prefix . 'task_lists';
		$users_table = $wpdb->prefix . 'users';
		$sql = "SELECT t.*, l.list_name, u.user_login FROM $tasks_table t
                INNER JOIN $lists_table l ON t.list_id = l.list_id
                INNER JOIN $users_table u ON l.user_id = u.ID";
		return $wpdb->get_results($sql);
	} else {
		return [];
	}
}

function taskmaster_pro_add_admin_menu() {
	add_menu_page(
		'TaskMaster Pro Lists',           // Page title
		'Task Lists',                     // Menu title
		'edit_posts',                     // Capability required
		'taskmaster_pro_lists',           // Menu slug
		'taskmaster_pro_render_lists_page', // Function to display the page
		'dashicons-list-view'             // Icon (optional)
	);
}
add_action('admin_menu', 'taskmaster_pro_add_admin_menu');

function taskmaster_pro_render_lists_page() {
	// Handle POST requests to create/update lists
	// Display existing lists with options to edit visibility or delete
	include('lists_admin_page.php'); // Create this file for the lists interface
}
function fetch_tasks_based_on_role_and_visibility() {
	global $wpdb;
	$current_user = wp_get_current_user();
	$tasks_table = $wpdb->prefix . 'tasks';
	$lists_table = $wpdb->prefix . 'task_lists';

	if (in_array('administrator', $current_user->roles)) {
		// Administrator: Fetch all tasks from all lists
		$query = "SELECT t.*, l.list_name, l.visibility, u.user_login
                  FROM $tasks_table t
                  JOIN $lists_table l ON t.list_id = l.list_id
                  JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
                  ORDER BY l.list_name, t.created_at DESC";
	} else {
		// Regular user: Fetch tasks from user's lists and public lists
		$query = $wpdb->prepare(
			"SELECT t.*, l.list_name, l.visibility
             FROM $tasks_table t
             JOIN $lists_table l ON t.list_id = l.list_id
             WHERE l.user_id = %d OR l.visibility = 'public'
             ORDER BY l.list_name, t.created_at DESC",
			$current_user->ID
		);
	}

	return $wpdb->get_results($query);
}


function taskmaster_pro_handle_new_list() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}
	if (!isset($_POST['taskmaster_pro_add_list_nonce']) || !wp_verify_nonce($_POST['taskmaster_pro_add_list_nonce'], 'taskmaster_pro_add_list_action')) {
		wp_die('Nonce validation failed');
	}


	// Check for nonce for security here (recommended)

	$list_name = isset($_POST['list_name']) ? sanitize_text_field($_POST['list_name']) : '';
	$visibility = isset($_POST['visibility']) && in_array($_POST['visibility'], ['public', 'private']) ? $_POST['visibility'] : 'private';

	global $wpdb;
	$table_name = $wpdb->prefix . 'task_lists';

	$wpdb->insert(
		$table_name,
		[
			'user_id' => get_current_user_id(),
			'list_name' => $list_name,
			'visibility' => $visibility
		],
		['%d', '%s', '%s']
	);

	// Redirect back to the lists page with a query parameter for success or failure
	$redirect_url = add_query_arg('page', 'taskmaster_pro_lists', admin_url('admin.php'));
	$redirect_url = add_query_arg('success', '1', $redirect_url); // Add more logic for error handling
	wp_redirect($redirect_url);
	exit;
}
add_action('admin_post_taskmaster_pro_add_list', 'taskmaster_pro_handle_new_list');

function taskmaster_pro_handle_edit_list() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}

	// Check if the nonce field is set and verify the nonce
	if (!isset($_POST['taskmaster_pro_edit_list_nonce']) || !wp_verify_nonce($_POST['taskmaster_pro_edit_list_nonce'], 'taskmaster_pro_edit_list_action')) {
		wp_die('Security check failed');
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'task_lists';

	// Sanitize and validate input
	$list_id = isset($_POST['list_id']) ? intval($_POST['list_id']) : 0;
	$list_name = isset($_POST['list_name']) ? sanitize_text_field($_POST['list_name']) : '';
	$visibility = isset($_POST['visibility']) && in_array($_POST['visibility'], ['public', 'private']) ? $_POST['visibility'] : 'private';

	// Update the list in the database
	$wpdb->update(
		$table_name,
		[
			'list_name' => $list_name,
			'visibility' => $visibility
		],
		['list_id' => $list_id],
		['%s', '%s'], // Data format
		['%d']  // Where format
	);

	// Redirect back to the lists page with a query parameter for success or failure
	$redirect_url = add_query_arg('page', 'taskmaster_pro_lists', admin_url('admin.php'));
	$redirect_url = add_query_arg('success', '1', $redirect_url); // Optionally, handle errors and adjust the redirect accordingly
	wp_redirect($redirect_url);
	exit;
}


add_action('admin_post_taskmaster_pro_edit_list', 'taskmaster_pro_handle_edit_list');

function fetch_user_lists() {
	global $wpdb;
	$user_id = get_current_user_id();
	$table_name = $wpdb->prefix . 'task_lists';

	// Check if the user is an administrator
	if (current_user_can('manage_options')) {
		$query = "SELECT * FROM $table_name";  // Admins see all lists
	} else {
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id);  // Others see only their lists
	}

	return $wpdb->get_results($query);
}

function update_task_list($list_id, $new_data) {
	global $wpdb;
	$user_id = get_current_user_id();
	$table_name = $wpdb->prefix . 'task_lists';

	// Fetch the current owner of the list
	$owner_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table_name WHERE list_id = %d", $list_id));

	if ($user_id == $owner_id || current_user_can('manage_options')) {
		$wpdb->update($table_name, $new_data, ['list_id' => $list_id]);  // Proceed with update
	} else {
		return false;  // Optionally handle error
	}
}
function fetch_tasks_grouped_by_lists() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'tasks';
	$lists_table = $wpdb->prefix . 'task_lists';

	// Check if user is admin
	if (current_user_can('manage_options')) {
		// Admins can view everything
		$query = "SELECT t.*, l.list_name FROM $table_name t
            JOIN $lists_table l ON t.list_id = l.list_id
            ORDER BY l.list_name, t.created_at DESC";
	} else {
		// Regular users can only view their own tasks and lists
		$current_user_id = get_current_user_id();
		$query = $wpdb->prepare(
			"SELECT t.*, l.list_name FROM $table_name t
            JOIN $lists_table l ON t.list_id = l.list_id
            WHERE l.user_id = %d
            ORDER BY l.list_name, t.created_at DESC",
			$current_user_id
		);
	}

	return $wpdb->get_results($query);
}

function enqueue_custom_scripts() {
	// Enqueue your JavaScript file
	wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'js/scripts.js', array('jquery'), '1.0', true);

	// Localize script with the necessary data
	wp_localize_script('custom-script', 'task_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'enqueue_custom_scripts');


function handle_update_task_completion() {
	//check_ajax_referer('update_task_completion_nonce');

	$taskId = isset($_POST['taskId']) ? intval($_POST['taskId']) : 0;
	$completed = isset($_POST['completed']) ? (bool) $_POST['completed'] : false;
	if ($taskId > 0) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'tasks';

		$wpdb->update(
			$table_name,
			array('completed' => $completed),
			array('id' => $taskId),
			array('%d'),
			array('%d')
		);

		wp_send_json_success(array('message' => 'Task updated successfully!'));
	} else {
		wp_send_json_error(array('message' => 'Invalid task ID.'));
	}
}
add_action('wp_ajax_update_task_completion', 'handle_update_task_completion');
add_action('wp_ajax_nopriv_update_task_completion', 'handle_update_task_completion');
function fetch_lists() {
	global $wpdb;
	$lists_table = $wpdb->prefix . 'task_lists';

	// Check if user is admin
	if (current_user_can('manage_options')) {
		// Admins can view everything
		$query = "SELECT list_id, list_name FROM $lists_table ORDER BY list_name ASC";
	} else {
		// Regular users can only view their own lists
		$current_user_id = get_current_user_id();
		$query = $wpdb->prepare(
			"SELECT list_id, list_name FROM $lists_table WHERE user_id = %d ORDER BY list_name ASC",
			$current_user_id
		);
	}

	return $wpdb->get_results($query);
}
