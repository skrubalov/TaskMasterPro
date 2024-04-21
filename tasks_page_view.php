<!-- tasks_page_view.php -->
<div class="wrap">
	<h1>Task List</h1>

	<?php if (isset($edit_task)): ?>
		<h2>Edit Task</h2>
		<form method="post">
			<input type="hidden" name="id" value="<?= esc_attr($edit_task->id); ?>">
			<input type="text" name="title" placeholder="Title" value="<?= esc_attr($edit_task->title); ?>">
			<textarea name="description" placeholder="Description"><?= esc_html($edit_task->description); ?></textarea>
			<button type="submit" name="edittask">Update Task</button>
		</form>
	<?php else: ?>
		<form method="post">
			<input type="text" name="title" placeholder="Title">
			<textarea name="description" placeholder="Description"></textarea>
			<button type="submit" name="newtask">Add Task</button>
		</form>
	<?php endif; ?>

	<ul>
		<?php
        $tasks = $tasks??[];
        foreach ($tasks as $task): ?>
			<li>
				<?= esc_html($task->title); ?>
				- <a href="?page=taskmaster_pro&edit=<?= esc_attr($task->id); ?>">Edit</a>
				<form method="post" style="display:inline;">
					<input type="hidden" name="id" value="<?= esc_attr($task->id); ?>">
					<button type="submit" name="delete">Delete</button>
				</form>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
