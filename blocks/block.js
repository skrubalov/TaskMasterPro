(function(blocks, element) {
    var el = element.createElement;

    blocks.registerBlockType('taskmaster-pro/tasks-block', {
        title: 'TaskMaster Pro Tasks',
        icon: 'list-view',
        category: 'widgets',

        edit: function() {
            return el('p', null, 'TaskMaster Pro Task List will be displayed here.');
        },

        save: function() {
            return null; // Dynamic blocks do not save content to the database.
        }
    });
})(window.wp.blocks, window.wp.element);
