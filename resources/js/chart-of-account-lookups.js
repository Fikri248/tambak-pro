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

const firstError = (payload, fallback) => Object.values(payload?.errors || {}).flat()[0]
    || payload?.message
    || fallback;

const requestJson = async (form, url, method, values = {}) => {
    const data = new FormData();
    const token = form.elements.namedItem('_token');
    const csrfToken = token instanceof HTMLInputElement ? token.value : '';

    if (csrfToken !== '') {
        data.set('_token', csrfToken);
    }

    data.set('_method', method);

    Object.entries(values).forEach(([key, value]) => data.set(key, value));

    const response = await fetch(url, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
    });
    const payload = await response.json().catch(() => ({}));

    return { response, payload };
};

const updateSelectOption = (select, option) => {
    const nativeOption = Array.from(select.options).find((item) => item.value === String(option.id));

    if (!nativeOption) {
        return;
    }

    nativeOption.textContent = `${option.name}${option.status === 'INACTIVE' ? ' (Tidak Aktif)' : ''}`;
    select.dispatchEvent(new Event('change', { bubbles: true }));
};

const configureRow = (field, row, option) => {
    const label = field.querySelector('label')?.textContent.replace('*', '').trim() || 'pilihan';
    const editButton = row.querySelector('[data-coa-lookup-edit]');
    const deleteButton = row.querySelector('[data-coa-lookup-delete]');

    row.dataset.optionId = String(option.id);
    row.dataset.optionName = option.name;
    row.dataset.optionStatus = option.status;
    row.dataset.updateUrl = option.update_url;
    row.dataset.deleteUrl = option.delete_url;
    row.querySelector('[data-coa-lookup-row-name]').textContent = option.name;

    editButton?.setAttribute('aria-label', `Edit ${label.toLocaleLowerCase('id-ID')} ${option.name}`);
    deleteButton?.setAttribute('aria-label', `Hapus ${label.toLocaleLowerCase('id-ID')} ${option.name}`);
};

const appendManagementRow = (field, panel, option) => {
    const template = panel.querySelector('[data-coa-lookup-row-template]');
    const list = panel.querySelector('[data-coa-lookup-list]');

    if (!(template instanceof HTMLTemplateElement) || !list) {
        return null;
    }

    const row = template.content.firstElementChild.cloneNode(true);
    configureRow(field, row, option);
    list.append(row);
    panel.querySelector('[data-coa-lookup-empty]')?.classList.add('hidden');

    return row;
};

const initializeManagementPanel = (form, field) => {
    const panel = field.querySelector('[data-coa-lookup-panel]');
    const select = form.elements.namedItem(field.dataset.selectName);
    const editor = panel?.querySelector('[data-coa-lookup-editor]');
    const editorInput = panel?.querySelector('[data-coa-lookup-edit-input]');
    const editorSave = panel?.querySelector('[data-coa-lookup-edit-save]');
    const editorCancel = panel?.querySelector('[data-coa-lookup-edit-cancel]');
    const deleteConfirm = panel?.querySelector('[data-coa-lookup-delete-confirm]');
    const deleteConfirmButton = panel?.querySelector('[data-coa-lookup-delete-confirm-button]');
    const deleteCancel = panel?.querySelector('[data-coa-lookup-delete-cancel]');
    let editedRow = null;
    let deletedRow = null;
    let returnFocus = null;

    if (!(panel instanceof HTMLDetailsElement)
        || !(select instanceof HTMLSelectElement)
        || !(editorInput instanceof HTMLInputElement)
        || !(editorSave instanceof HTMLButtonElement)
        || !(deleteConfirmButton instanceof HTMLButtonElement)) {
        return;
    }

    panel.querySelectorAll('[data-coa-lookup-actions]').forEach((actions) => {
        actions.hidden = false;
    });

    const closeEditor = (focus = true) => {
        editor.hidden = true;
        editorInput.value = '';
        editedRow = null;

        if (focus && returnFocus?.isConnected) {
            returnFocus.focus({ preventScroll: true });
        }
    };

    const closeDelete = (focus = true) => {
        deleteConfirm.hidden = true;
        deletedRow = null;

        if (focus && returnFocus?.isConnected) {
            returnFocus.focus({ preventScroll: true });
        }
    };

    panel.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-coa-lookup-edit]');
        const deleteButton = event.target.closest('[data-coa-lookup-delete]');

        if (editButton) {
            const row = editButton.closest('[data-coa-lookup-row]');

            if (!row) {
                return;
            }

            closeDelete(false);
            editedRow = row;
            returnFocus = editButton;
            editorInput.value = row.dataset.optionName || '';
            editor.hidden = false;
            editorInput.focus({ preventScroll: true });
            return;
        }

        if (deleteButton) {
            const row = deleteButton.closest('[data-coa-lookup-row]');

            if (!row) {
                return;
            }

            closeEditor(false);
            deletedRow = row;
            returnFocus = deleteButton;
            const name = row.dataset.optionName || '';
            const label = field.querySelector('label')?.textContent.replace('*', '').trim() || 'Pilihan';
            panel.querySelector('[data-coa-lookup-delete-title]').textContent = `Hapus ${label}?`;
            panel.querySelector('[data-coa-lookup-delete-message]').textContent = `${label} '${name}' akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`;
            deleteConfirm.hidden = false;
            deleteCancel?.focus({ preventScroll: true });
        }
    });

    editorCancel?.addEventListener('click', () => closeEditor());
    editorInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeEditor();
        }
    });
    editorSave.addEventListener('click', async () => {
        const name = editorInput.value.trim();

        if (!editedRow || name === '') {
            showFeedback(panel, 'Nama pilihan wajib diisi.', true);
            editorInput.focus();
            return;
        }

        editorSave.disabled = true;
        editorSave.textContent = 'Menyimpan...';
        showFeedback(panel, 'Menyimpan perubahan...');

        try {
            const { response, payload } = await requestJson(form, editedRow.dataset.updateUrl, 'PATCH', {
                lookup_name: name,
            });

            if (!response.ok || !payload.option) {
                showFeedback(panel, firstError(payload, 'Gagal memperbarui pilihan.'), true);
                editorInput.focus();
                return;
            }

            const focusTarget = editedRow.querySelector('[data-coa-lookup-edit]');
            configureRow(field, editedRow, payload.option);
            updateSelectOption(select, payload.option);
            returnFocus = focusTarget;
            showFeedback(panel, payload.message || 'Pilihan berhasil diperbarui.');
            closeEditor();
        } catch {
            showFeedback(panel, 'Gagal memperbarui pilihan. Silakan coba lagi.', true);
            editorInput.focus();
        } finally {
            editorSave.disabled = false;
            editorSave.textContent = 'Simpan';
        }
    });

    deleteCancel?.addEventListener('click', () => closeDelete());
    deleteConfirmButton.addEventListener('click', async () => {
        if (!deletedRow) {
            return;
        }

        const row = deletedRow;
        const focusCandidate = row.nextElementSibling?.querySelector('[data-coa-lookup-edit]')
            || row.previousElementSibling?.querySelector('[data-coa-lookup-edit]')
            || panel.querySelector('[data-coa-lookup-add-summary]');
        deleteConfirmButton.disabled = true;
        deleteConfirmButton.textContent = 'Menghapus...';
        showFeedback(panel, 'Memeriksa penggunaan pilihan...');

        try {
            const { response, payload } = await requestJson(form, row.dataset.deleteUrl, 'DELETE');

            if (!response.ok) {
                showFeedback(panel, firstError(payload, 'Pilihan tidak dapat dihapus.'), true);
                closeDelete();
                return;
            }

            const nativeOption = Array.from(select.options).find((item) => item.value === String(payload.deleted_id));

            if (nativeOption?.selected) {
                select.value = '';
            }

            nativeOption?.remove();
            select.dispatchEvent(new Event('change', { bubbles: true }));
            row.remove();
            panel.querySelector('[data-coa-lookup-empty]')?.classList.toggle(
                'hidden',
                Boolean(panel.querySelector('[data-coa-lookup-row]')),
            );
            returnFocus = focusCandidate;
            showFeedback(panel, payload.message || 'Pilihan berhasil dihapus.');
            closeDelete();
        } catch {
            showFeedback(panel, 'Gagal menghapus pilihan. Silakan coba lagi.', true);
            closeDelete();
        } finally {
            deleteConfirmButton.disabled = false;
            deleteConfirmButton.textContent = 'Hapus';
        }
    });
};

export const initializeChartOfAccountLookups = () => {
    document.querySelectorAll('[data-coa-form]:not([data-coa-form-ready])').forEach((form) => {
        form.dataset.coaFormReady = 'true';
        form.querySelectorAll('[data-coa-lookup-field]').forEach((field) => initializeManagementPanel(form, field));

        form.addEventListener('submit', async (event) => {
            const submitter = event.submitter;

            if (!(submitter instanceof HTMLButtonElement) || !submitter.matches('[data-coa-lookup-submit]')) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const field = submitter.closest('[data-coa-lookup-field]');
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
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.option) {
                    showFeedback(panel, firstError(payload, 'Periksa kembali nama pilihan.'), true);
                    input.focus();
                    return;
                }

                const option = new Option(payload.option.name, payload.option.id, true, true);
                select.append(option);
                select.value = payload.option.id;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                const row = appendManagementRow(field, panel, payload.option);
                input.value = '';
                showFeedback(panel, payload.message || 'Pilihan berhasil ditambahkan.');

                window.setTimeout(() => {
                    const addPanel = panel.querySelector('[data-coa-lookup-add]');

                    if (addPanel instanceof HTMLDetailsElement) {
                        addPanel.open = false;
                    }

                    row?.querySelector('[data-coa-lookup-edit]')?.focus({ preventScroll: true });
                }, 250);
            } catch {
                showFeedback(panel, 'Gagal menyimpan pilihan. Silakan coba lagi.', true);
            } finally {
                submitter.disabled = false;
                submitter.innerHTML = originalContent;
            }
        });
    });
};
