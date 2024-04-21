<!-- tasks_page_view.php -->
<div class="wrap">
    <h1>Task List</h1>

	<?php
	// Fetch task lists from the database
	global $wpdb;

	$lists = fetch_lists();
	?>

	<?php if (isset($edit_task)): ?>
        <h2>Edit Task</h2>
        <form method="post">
            <input type="hidden" name="id" value="<?= esc_attr($edit_task->id); ?>">
            <input type="text" name="title" placeholder="Title" value="<?= esc_attr($edit_task->title); ?>">
            <textarea name="description" placeholder="Description"><?= esc_html($edit_task->description); ?></textarea>
            <!-- Dropdown to select the list for the task -->
            <select name="list_id">
				<?php foreach ($lists as $list): ?>
                    <option value="<?= esc_attr($list->list_id); ?>" <?= $edit_task->list_id == $list->list_id ? 'selected' : ''; ?>>
						<?= esc_html($list->list_name); ?>
                    </option>
				<?php endforeach; ?>
            </select>
            <button type="submit" name="edittask">Update Task</button>
        </form>
	<?php else: ?>
        <form method="post" action="<?= admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="add_new_task">
            <input type="text" name="title" placeholder="Title">
            <textarea name="description" placeholder="Description"></textarea>
            <select name="list_id">
				<?php foreach ($lists as $list): ?>
                    <option value="<?= esc_attr($list->list_id); ?>"><?= esc_html($list->list_name); ?></option>
				<?php endforeach; ?>
            </select>
            <button type="submit" name="newtask">Add Task</button>
        </form>

	<?php endif; ?>


</div>

<?php // testing new look
$tasks = fetch_tasks_grouped_by_lists();
$current_list = '';
?>
<div class="task-lists">
   		<?php
		$current_list = '';
		foreach ($tasks as $task):
		if ($task->list_name != $current_list):
		if ($current_list != '') echo '</tr></tbody></table></div>'; // Close previous table
		$current_list = $task->list_name;
		?>
        <div class="task-list">
            <h3 onclick="toggleVisibility('list-<?= esc_attr($task->list_id); ?>')"><?= esc_html($task->list_name); ?></h3>
            <table id="list-<?= esc_attr($task->list_id); ?>" style="display:none;" class="task-table">
                <thead>
                <tr>
                    <th>Done</th>
                    <th>Task Name</th>
                    <th>Task Description</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
				<?php endif; ?>
                <tr class="<?= $task->completed ? 'completed-task' : ''; ?>">
                    <td>
                        <label>
                            <input type="checkbox" onchange="markTaskCompleted(<?= esc_attr($task->id); ?>, this
                                    .checked, this)" <?= $task->completed ? 'checked' : ''; ?>>
                        </label>
                    </td>
                    <td><?= esc_html($task->title); ?></td>
                    <td><?= esc_html($task->description); ?></td>
                    <td>
                        <a href="?page=taskmaster_pro&edit=<?= esc_attr($task->id); ?>" class="button">Edit</a>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="id" value="<?= esc_attr($task->id); ?>">
                            <button type="submit" name="delete" class="button">Delete</button>
                        </form>
                    </td>
                </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
</div>


