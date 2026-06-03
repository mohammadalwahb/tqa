@once
@push('scripts')
<script>
(function () {
    function initCollegeDepartmentFilter(wrapper) {
        const college = wrapper.querySelector('[data-college-select]');
        const dept = wrapper.querySelector('[data-department-select]');
        if (!college || !dept) return;

        const placeholder = dept.querySelector('option[value=""]');
        const templates = Array.from(dept.querySelectorAll('option[data-college-id]'));

        function rebuild() {
            const collegeId = college.value;
            const previous = dept.value;

            dept.innerHTML = '';
            if (placeholder) {
                dept.appendChild(placeholder.cloneNode(true));
            }

            templates.forEach((template) => {
                if (!collegeId || template.dataset.collegeId === collegeId) {
                    dept.appendChild(template.cloneNode(true));
                }
            });

            const valid = Array.from(dept.options).some((o) => o.value === previous);
            dept.value = valid ? previous : '';
        }

        college.addEventListener('change', rebuild);
        rebuild();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-college-department-filter]').forEach(initCollegeDepartmentFilter);
    });
})();
</script>
@endpush
@endonce
