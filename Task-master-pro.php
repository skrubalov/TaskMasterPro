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
	$table_name = $wpdb->prefix . 'tasks';

	// Prepare SQL query to create table
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

// Register the activation hook
register_activation_hook(__FILE__, 'taskmaster_pro_install');

function taskmaster_pro_menu() {
	add_menu_page('TaskMaster Pro', 'Tasks', 'manage_options', 'taskmaster_pro', 'taskmaster_pro_tasks_page');
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
add_action('wp_enqueue_scripts', 'taskmaster_pro_enqueue_styles');


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
