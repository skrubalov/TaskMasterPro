function toggleVisibility(listId) {
    var list = document.getElementById(listId);
    list.style.display = list.style.display === 'none' ? 'block' : 'none';
}

function markTaskCompleted(taskId, completed, obj) {
    jQuery.ajax({
        url: task_ajax_object.ajax_url,
        type: 'POST',
        data: {
            action: 'update_task_completion',
            taskId: taskId,
            completed: completed,
            // Add nonce if needed
            // '_ajax_nonce': task_ajax_object.nonce
        },
        success: function(response) {
            if(completed){
                jQuery(obj).closest('tr').addClass('completed-task');
            }else{
                jQuery(obj).closest('tr').removeClass('completed-task');
            }
        },
        error: function(xhr, status, error) {
            // Handle error response
        }
    });
}
