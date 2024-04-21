<div class="wrap">
	<h1>Manage Task Lists</h1>
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="taskmaster_pro_add_list">
		<?php wp_nonce_field('taskmaster_pro_add_list_action', 'taskmaster_pro_add_list_nonce'); ?>
		<input type="text" name="list_name" placeholder="List Name" required>
		<select name="visibility">
			<option value="public">Public</option>
			<option value="private">Private</option>
		</select>
		<input type="submit" value="Create List">
	</form>

	<?php
	global $wpdb;
	$user_id = get_current_user_id();
	$table_name = $wpdb->prefix . 'task_lists';
	$lists = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
		$edit_id = intval($_GET['edit']);
		$list_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE list_id = %d AND user_id = %d", $edit_id, $user_id));

		if ($list_to_edit): ?>
			<h2>Edit List</h2>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="taskmaster_pro_edit_list">
				<input type="hidden" name="list_id" value="<?= esc_attr($list_to_edit->list_id); ?>">
				<?php wp_nonce_field('taskmaster_pro_edit_list_action', 'taskmaster_pro_edit_list_nonce'); ?>
				<input type="text" name="list_name" value="<?= esc_attr($list_to_edit->list_name); ?>" required>
				<select name="visibility">
					<option value="public" <?= $list_to_edit->visibility === 'public' ? 'selected' : ''; ?>>Public</option>
					<option value="private" <?= $list_to_edit->visibility === 'private' ? 'selected' : ''; ?>>Private</option>
				</select>
				<input type="submit" value="Update List">
			</form>
		<?php
		endif;
	}

	if ($lists): ?>
		<h2>Your Lists</h2>
		<table class="wp-list-table widefat fixed striped ">
			<thead>
			<tr>
				<th>List Name</th>
				<th>Visibility</th>
				<th>Actions</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($lists as $list): ?>
				<tr>
					<td><?= esc_html($list->list_name); ?></td>
					<td><?= esc_html($list->visibility); ?></td>
					<td>
						<a href="<?php echo admin_url('admin.php?page=taskmaster_pro_lists&edit=' . $list->list_id); ?>" class="button">Edit</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>No lists found. Create your first task list.</p>
	<?php endif; ?>
</div>
