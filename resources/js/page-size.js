export const initializePageSizeControls = () => {
    document.querySelectorAll('[data-page-size-control]:not([data-page-size-ready])').forEach((control) => {
        const select = control.querySelector('[data-page-size-select]');
        const form = select?.form;

        if (!select || !form) {
            return;
        }

        control.setAttribute('data-page-size-ready', '');
        select.addEventListener('change', () => {
            form.querySelectorAll('[name="page"]').forEach((field) => field.remove());

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();

                return;
            }

            form.submit();
        });
    });
};
