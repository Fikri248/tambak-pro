const firstError = (payload, fallback) => Object.values(payload?.errors || {}).flat()[0]
    || payload?.message
    || fallback;

const showFeedback = (panel, message, isError = false) => {
    const feedback = panel?.querySelector('[data-vendor-type-feedback]');

    if (!feedback) return;

    feedback.textContent = message;
    feedback.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700');
    feedback.classList.toggle('border-red-200', isError);
    feedback.classList.toggle('bg-red-50', isError);
    feedback.classList.toggle('text-red-700', isError);
};

const requestJson = async (form, url, method, values = {}) => {
    const data = new FormData();
    const token = form.elements.namedItem('_token');
    const csrfToken = token instanceof HTMLInputElement ? token.value : '';

    data.set('_token', csrfToken);
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

const configureRow = (row, option) => {
    row.dataset.optionId = String(option.id);
    row.dataset.optionName = option.name;
    row.dataset.system = option.is_system ? 'true' : 'false';
    row.dataset.updateUrl = option.update_url;
    row.dataset.deleteUrl = option.delete_url;
    row.querySelector('[data-vendor-type-row-name]').textContent = option.name;
    row.querySelector('[data-vendor-type-edit]')?.setAttribute('aria-label', `Edit Jenis Vendor ${option.name}`);
    row.querySelector('[data-vendor-type-delete]')?.setAttribute('aria-label', `Hapus Jenis Vendor ${option.name}`);
};

const syncOption = (select, option) => {
    const nativeOption = Array.from(select.options).find((item) => item.value === String(option.id));

    if (nativeOption) {
        nativeOption.textContent = option.name;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

const appendRow = (panel, option) => {
    const template = panel.querySelector('[data-vendor-type-row-template]');
    const list = panel.querySelector('[data-vendor-type-list]');

    if (!(template instanceof HTMLTemplateElement) || !list) return null;

    const row = template.content.firstElementChild.cloneNode(true);
    configureRow(row, option);
    list.append(row);
    panel.querySelector('[data-vendor-type-empty]')?.classList.add('hidden');

    return row;
};

const initializePanel = (form, field) => {
    const panel = field.querySelector('[data-vendor-type-panel]');
    const select = form.elements.namedItem(field.dataset.selectName);
    const editor = panel?.querySelector('[data-vendor-type-editor]');
    const editorInput = panel?.querySelector('[data-vendor-type-edit-input]');
    const editorSave = panel?.querySelector('[data-vendor-type-edit-save]');
    const deleteConfirm = panel?.querySelector('[data-vendor-type-delete-confirm]');
    const deleteButton = panel?.querySelector('[data-vendor-type-delete-confirm-button]');
    let editedRow = null;
    let deletedRow = null;
    let returnFocus = null;

    if (!(panel instanceof HTMLDetailsElement)
        || !(select instanceof HTMLSelectElement)
        || !(editorInput instanceof HTMLInputElement)
        || !(editorSave instanceof HTMLButtonElement)
        || !(deleteButton instanceof HTMLButtonElement)) return;

    panel.querySelectorAll('[data-vendor-type-actions]').forEach((actions) => { actions.hidden = false; });

    const closeEditor = (focus = true) => {
        editor.hidden = true;
        editorInput.value = '';
        editedRow = null;
        if (focus && returnFocus?.isConnected) returnFocus.focus({ preventScroll: true });
    };
    const closeDelete = (focus = true) => {
        deleteConfirm.hidden = true;
        deletedRow = null;
        if (focus && returnFocus?.isConnected) returnFocus.focus({ preventScroll: true });
    };

    panel.addEventListener('click', (event) => {
        const editTrigger = event.target.closest('[data-vendor-type-edit]');
        const deleteTrigger = event.target.closest('[data-vendor-type-delete]');

        if (editTrigger) {
            closeDelete(false);
            editedRow = editTrigger.closest('[data-vendor-type-row]');
            returnFocus = editTrigger;
            editorInput.value = editedRow?.dataset.optionName || '';
            editor.hidden = false;
            editorInput.focus({ preventScroll: true });
        } else if (deleteTrigger) {
            closeEditor(false);
            deletedRow = deleteTrigger.closest('[data-vendor-type-row]');
            returnFocus = deleteTrigger;
            const name = deletedRow?.dataset.optionName || '';
            const systemMessage = deletedRow?.dataset.system === 'true'
                ? 'Jenis bawaan sistem akan diperiksa dan tidak dapat dihapus.'
                : `Jenis Vendor '${name}' akan dihapus permanen jika belum digunakan.`;
            panel.querySelector('[data-vendor-type-delete-title]').textContent = 'Hapus Jenis Vendor?';
            panel.querySelector('[data-vendor-type-delete-message]').textContent = systemMessage;
            deleteConfirm.hidden = false;
            panel.querySelector('[data-vendor-type-delete-cancel]')?.focus({ preventScroll: true });
        }
    });

    panel.querySelector('[data-vendor-type-edit-cancel]')?.addEventListener('click', () => closeEditor());
    panel.querySelector('[data-vendor-type-delete-cancel]')?.addEventListener('click', () => closeDelete());
    editorInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeEditor();
        }
    });
    editorSave.addEventListener('click', async () => {
        const name = editorInput.value.trim();

        if (!editedRow || name === '') {
            showFeedback(panel, 'Nama Jenis Vendor wajib diisi.', true);
            editorInput.focus();
            return;
        }

        editorSave.disabled = true;
        editorSave.textContent = 'Menyimpan...';

        try {
            const { response, payload } = await requestJson(form, editedRow.dataset.updateUrl, 'PATCH', {
                vendor_type_name: name,
            });

            if (!response.ok || !payload.option) {
                showFeedback(panel, firstError(payload, 'Jenis Vendor gagal diperbarui.'), true);
                return;
            }

            const focusTarget = editedRow.querySelector('[data-vendor-type-edit]');
            configureRow(editedRow, payload.option);
            syncOption(select, payload.option);
            returnFocus = focusTarget;
            showFeedback(panel, payload.message);
            closeEditor();
        } catch {
            showFeedback(panel, 'Jenis Vendor gagal diperbarui. Silakan coba lagi.', true);
        } finally {
            editorSave.disabled = false;
            editorSave.textContent = 'Simpan';
        }
    });

    deleteButton.addEventListener('click', async () => {
        if (!deletedRow) return;

        const row = deletedRow;
        const focusTarget = row.nextElementSibling?.querySelector('[data-vendor-type-edit]')
            || row.previousElementSibling?.querySelector('[data-vendor-type-edit]')
            || panel.querySelector('[data-vendor-type-add-summary]');
        deleteButton.disabled = true;
        deleteButton.textContent = 'Menghapus...';

        try {
            const { response, payload } = await requestJson(form, row.dataset.deleteUrl, 'DELETE');

            if (!response.ok) {
                showFeedback(panel, firstError(payload, 'Jenis Vendor tidak dapat dihapus.'), true);
                closeDelete();
                return;
            }

            const nativeOption = Array.from(select.options).find((item) => item.value === String(payload.deleted_id));
            if (nativeOption?.selected) select.value = '';
            nativeOption?.remove();
            select.dispatchEvent(new Event('change', { bubbles: true }));
            row.remove();
            panel.querySelector('[data-vendor-type-empty]')?.classList.toggle(
                'hidden',
                Boolean(panel.querySelector('[data-vendor-type-row]')),
            );
            returnFocus = focusTarget;
            showFeedback(panel, payload.message);
            closeDelete();
        } catch {
            showFeedback(panel, 'Jenis Vendor gagal dihapus. Silakan coba lagi.', true);
            closeDelete();
        } finally {
            deleteButton.disabled = false;
            deleteButton.textContent = 'Hapus';
        }
    });
};

export const initializeVendorTypeLookups = () => {
    document.querySelectorAll('[data-vendor-form]:not([data-vendor-form-ready])').forEach((form) => {
        form.dataset.vendorFormReady = 'true';
        form.querySelectorAll('[data-vendor-type-field]').forEach((field) => initializePanel(form, field));

        form.addEventListener('submit', async (event) => {
            const submitter = event.submitter;

            if (!(submitter instanceof HTMLButtonElement) || !submitter.matches('[data-vendor-type-submit]')) return;

            event.preventDefault();
            event.stopPropagation();
            const field = submitter.closest('[data-vendor-type-field]');
            const panel = field?.querySelector('[data-vendor-type-panel]');
            const input = form.elements.namedItem(submitter.dataset.lookupInput);
            const select = form.elements.namedItem(submitter.dataset.selectName);
            const original = submitter.innerHTML;

            if (!(input instanceof HTMLInputElement) || !(select instanceof HTMLSelectElement)) return;

            submitter.disabled = true;
            submitter.textContent = 'Menyimpan...';

            try {
                const data = new FormData(form);
                data.delete('_method');
                const response = await fetch(submitter.formAction, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.option) {
                    showFeedback(panel, firstError(payload, 'Periksa kembali nama Jenis Vendor.'), true);
                    input.focus();
                    return;
                }

                select.append(new Option(payload.option.name, payload.option.id, true, true));
                select.value = payload.option.id;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                const row = appendRow(panel, payload.option);
                input.value = '';
                showFeedback(panel, payload.message);
                panel.querySelector('[data-vendor-type-add]').open = false;
                row?.querySelector('[data-vendor-type-edit]')?.focus({ preventScroll: true });
            } catch {
                showFeedback(panel, 'Jenis Vendor gagal disimpan. Silakan coba lagi.', true);
            } finally {
                submitter.disabled = false;
                submitter.innerHTML = original;
            }
        });
    });
};
