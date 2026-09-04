const errorMessage = (payload, fallback) => Object.values(payload?.errors || {}).flat()[0] || payload?.message || fallback;

const feedback = (panel, message, error = false) => {
    const target = panel.querySelector('[data-item-type-feedback]');
    if (!target) return;
    target.textContent = message;
    target.classList.remove('hidden');
    target.classList.toggle('border-red-200', error);
    target.classList.toggle('bg-red-50', error);
    target.classList.toggle('text-red-700', error);
};

const send = async (form, url, method, values = {}) => {
    const data = new FormData();
    const token = form.elements.namedItem('_token')?.value || '';
    data.set('_token', token);
    data.set('_method', method);
    Object.entries(values).forEach(([key, value]) => data.set(key, value));
    const response = await fetch(url, {
        method: 'POST', body: data, credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
    });
    return { response, payload: await response.json().catch(() => ({})) };
};

const configureRow = (row, option) => {
    row.dataset.optionId = String(option.id);
    row.dataset.optionName = option.name;
    row.dataset.system = option.is_system ? 'true' : 'false';
    row.dataset.updateUrl = option.update_url;
    row.dataset.deleteUrl = option.delete_url;
    row.querySelector('[data-item-type-row-name]').textContent = option.name;
};

export const initializeItemTypeLookups = () => {
    document.querySelectorAll('[data-item-form]:not([data-item-form-ready])').forEach((form) => {
        form.dataset.itemFormReady = 'true';
        const field = form.querySelector('[data-item-type-field]');
        const panel = field?.querySelector('[data-item-type-panel]');
        const select = form.elements.namedItem('item_type_id');
        if (!(panel instanceof HTMLDetailsElement) || !(select instanceof HTMLSelectElement)) return;

        panel.querySelectorAll('[data-item-type-actions]').forEach((node) => { node.hidden = false; });
        const editor = panel.querySelector('[data-item-type-editor]');
        const editInput = panel.querySelector('[data-item-type-edit-input]');
        const editSave = panel.querySelector('[data-item-type-edit-save]');
        const deleteBox = panel.querySelector('[data-item-type-delete-confirm]');
        const deleteSave = panel.querySelector('[data-item-type-delete-confirm-button]');
        let editRow = null;
        let deleteRow = null;

        panel.addEventListener('click', (event) => {
            const edit = event.target.closest('[data-item-type-edit]');
            const remove = event.target.closest('[data-item-type-delete]');
            if (edit) {
                editRow = edit.closest('[data-item-type-row]');
                editInput.value = editRow.dataset.optionName;
                editor.hidden = false;
                deleteBox.hidden = true;
                editInput.focus();
            }
            if (remove) {
                deleteRow = remove.closest('[data-item-type-row]');
                const system = deleteRow.dataset.system === 'true';
                panel.querySelector('[data-item-type-delete-message]').textContent = system
                    ? 'Jenis bawaan sistem tidak dapat dihapus.'
                    : `Jenis '${deleteRow.dataset.optionName}' akan dihapus permanen jika belum digunakan.`;
                deleteBox.hidden = false;
                editor.hidden = true;
            }
        });
        panel.querySelector('[data-item-type-edit-cancel]')?.addEventListener('click', () => { editor.hidden = true; editRow = null; });
        panel.querySelector('[data-item-type-delete-cancel]')?.addEventListener('click', () => { deleteBox.hidden = true; deleteRow = null; });

        editSave?.addEventListener('click', async () => {
            const name = editInput.value.trim();
            if (!editRow || !name) return feedback(panel, 'Nama Jenis Barang/Item wajib diisi.', true);
            editSave.disabled = true;
            try {
                const { response, payload } = await send(form, editRow.dataset.updateUrl, 'PATCH', { item_type_name: name });
                if (!response.ok || !payload.option) return feedback(panel, errorMessage(payload, 'Jenis Barang/Item gagal diperbarui.'), true);
                configureRow(editRow, payload.option);
                const option = Array.from(select.options).find((entry) => entry.value === String(payload.option.id));
                if (option) option.textContent = payload.option.name;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                editor.hidden = true;
                feedback(panel, payload.message);
            } catch {
                feedback(panel, 'Jenis Barang/Item gagal diperbarui. Silakan coba lagi.', true);
            } finally { editSave.disabled = false; }
        });

        deleteSave?.addEventListener('click', async () => {
            if (!deleteRow) return;
            deleteSave.disabled = true;
            try {
                const { response, payload } = await send(form, deleteRow.dataset.deleteUrl, 'DELETE');
                if (!response.ok) return feedback(panel, errorMessage(payload, 'Jenis Barang/Item tidak dapat dihapus.'), true);
                const option = Array.from(select.options).find((entry) => entry.value === String(payload.deleted_id));
                if (option?.selected) select.value = '';
                option?.remove();
                deleteRow.remove();
                select.dispatchEvent(new Event('change', { bubbles: true }));
                deleteBox.hidden = true;
                deleteRow = null;
                feedback(panel, payload.message);
            } catch {
                feedback(panel, 'Jenis Barang/Item gagal dihapus. Silakan coba lagi.', true);
            } finally { deleteSave.disabled = false; }
        });

        form.addEventListener('submit', async (event) => {
            const button = event.submitter;
            if (!(button instanceof HTMLButtonElement) || !button.matches('[data-item-type-submit]')) return;
            event.preventDefault();
            event.stopPropagation();
            const input = form.elements.namedItem('item_type_name');
            if (!(input instanceof HTMLInputElement)) return;
            button.disabled = true;
            try {
                const data = new FormData(form);
                data.delete('_method');
                const response = await fetch(button.formAction, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload.option) return feedback(panel, errorMessage(payload, 'Periksa kembali nama Jenis Barang/Item.'), true);
                select.append(new Option(payload.option.name, payload.option.id, true, true));
                select.value = String(payload.option.id);
                select.dispatchEvent(new Event('change', { bubbles: true }));
                const template = panel.querySelector('[data-item-type-row-template]');
                const row = template.content.firstElementChild.cloneNode(true);
                configureRow(row, payload.option);
                panel.querySelector('[data-item-type-list]').append(row);
                input.value = '';
                panel.querySelector('[data-item-type-add]').open = false;
                feedback(panel, payload.message);
            } catch {
                feedback(panel, 'Jenis Barang/Item gagal disimpan. Silakan coba lagi.', true);
            } finally { button.disabled = false; }
        });
    });
};
