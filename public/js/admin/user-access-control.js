(() => {
    const initAccessForm = (form) => {
        const accessSelect = form.querySelector('[data-access-level]');
        const roleSelect = form.querySelector('[data-permission-role]');

        if (!accessSelect || !roleSelect) return;

        const syncRoleRequirement = () => {
            const isAdministrator = accessSelect.value === 'admin';
            roleSelect.required = isAdministrator;

            if (!isAdministrator) {
                roleSelect.value = '';
            }
        };

        accessSelect.addEventListener('change', syncRoleRequirement);

        roleSelect.addEventListener('change', () => {
            // Selecting a permission role is an explicit request for admin
            // access. Keep the two controls in sync instead of silently
            // discarding the role on submit.
            if (roleSelect.value) {
                accessSelect.value = 'admin';
                roleSelect.required = true;
            }
        });

        form.addEventListener('submit', (event) => {
            if (accessSelect.value === 'admin' && !roleSelect.value) {
                event.preventDefault();
                roleSelect.required = true;
                roleSelect.reportValidity();
            }
        });

        syncRoleRequirement();
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-user-access-form]').forEach(initAccessForm);
    });
})();
