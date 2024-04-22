function toggleVisibility(id) {
    var element = document.getElementById(id);
    var isVisible = element.style.display !== 'none';
    element.style.display = isVisible ? 'none' : 'table';
    // Update aria-expanded for accessibility
    var trigger = document.querySelector(`h3[onclick*="${id}"]`);
    if (trigger) {
        trigger.setAttribute('aria-expanded', !isVisible);
    }
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
