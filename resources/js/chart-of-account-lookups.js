const showFeedback = (panel, message, isError = false) => {
    const feedback = panel?.querySelector('[data-coa-lookup-feedback]');

    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700');
    feedback.classList.toggle('border-red-200', isError);
    feedback.classList.toggle('bg-red-50', isError);
    feedback.classList.toggle('text-red-700', isError);
};

export const initializeChartOfAccountLookups = () => {
    document.querySelectorAll('[data-coa-form]:not([data-coa-form-ready])').forEach((form) => {
        form.dataset.coaFormReady = 'true';

        form.addEventListener('submit', async (event) => {
            const submitter = event.submitter;

            if (!(submitter instanceof HTMLButtonElement) || !submitter.matches('[data-coa-lookup-submit]')) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const panel = submitter.closest('[data-coa-lookup-panel]');
            const input = form.elements.namedItem(submitter.dataset.lookupInput);
            const select = form.elements.namedItem(submitter.dataset.selectName);
            const originalContent = submitter.innerHTML;

            if (!(input instanceof HTMLInputElement) || !(select instanceof HTMLSelectElement)) {
                showFeedback(panel, 'Form pilihan tidak dapat diproses.', true);
                return;
            }

            submitter.disabled = true;
            submitter.textContent = 'Menyimpan...';
            showFeedback(panel, 'Menyimpan pilihan baru...');

            try {
                const data = new FormData(form);
                data.delete('_method');
                data.set(submitter.name, submitter.value);

                const response = await fetch(submitter.formAction, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json();

                if (response.status === 422) {
                    const message = Object.values(payload.errors || {}).flat()[0]
                        || 'Periksa kembali nama pilihan.';
                    showFeedback(panel, message, true);
                    input.focus();
                    return;
                }

                if (!response.ok || !payload.option) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const option = new Option(payload.option.label, payload.option.value, true, true);
                select.append(option);
                select.value = payload.option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                input.value = '';
                showFeedback(panel, payload.message || 'Pilihan berhasil ditambahkan.');

                window.setTimeout(() => {
                    if (panel instanceof HTMLDetailsElement) {
                        panel.open = false;
                    }
                }, 500);
            } catch {
                showFeedback(panel, 'Gagal menyimpan pilihan. Silakan coba lagi.', true);
            } finally {
                submitter.disabled = false;
                submitter.innerHTML = originalContent;
            }
        });
    });
};
