const { apiFetch } = wp;
const { useState, useEffect } = wp.element;
const { SelectControl, Notice } = wp.components;
const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { createElement } = wp.element;

registerBlockType('taskmaster-pro/task-select-list', {
    title: __('Select Task List', 'taskmaster-pro'),
    icon: 'list-view',
    category: 'widgets',

    attributes: {
        selectedListId: {
            type: 'number',
            default: 0
        }
    },

    edit: ({ attributes, setAttributes }) => {
        const [lists, setLists] = useState([]);
        const [tasks, setTasks] = useState([]);
        const [error, setError] = useState('');
        const { selectedListId } = attributes;

        useEffect(() => {
            apiFetch({ path: '/taskmaster-pro/v1/lists/' })
                .then(data => setLists(data))
                .catch((err) => {
                    console.error('Error fetching lists:', err);
                    setError(__('Error fetching lists. Please try again later.', 'taskmaster-pro'));
                });
        }, []);

        useEffect(() => {
            if (selectedListId) {
                apiFetch({ path: `/taskmaster-pro/v1/tasks/?list_id=${selectedListId}` })
                    .then(data => setTasks(data))
                    .catch((err) => {
                        console.error('Error fetching tasks:', err);
                        setError(__('Error fetching tasks. Please try again later.', 'taskmaster-pro'));
                    });
            }
        }, [selectedListId]);
        console.log(selectedListId);
        const handleChange = (listId) => {
            setAttributes({ selectedListId: parseInt(listId, 10) });
            setError('');  // Clear previous errors on a new selection
        };

        return createElement('div', {},
            error ? createElement(Notice, {
                status: 'error',
                isDismissible: true,
                onRemove: () => setError('')
            }, error) : null,
            createElement(SelectControl, {
                label: __('Select a List:', 'taskmaster-pro'),
                value: selectedListId,
                options: lists.map(list => ({ label: list.list_name, value: list.list_id })),
                onChange: handleChange
            }),
            tasks.map(task =>
                createElement('div', { key: task.id },
                    createElement('h4', {}, task.title),
                    createElement('p', {}, task.description)
                )
            )
        );
    },

    save: () => null, // Dynamic block, content handled server-side
});
