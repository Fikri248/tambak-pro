export const initializeConfirmationDialog = () => {
    const dialog = document.querySelector('[data-confirmation-dialog]');

    if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
        return;
    }

    const title = dialog.querySelector('[data-confirmation-dialog-title]');
    const message = dialog.querySelector('[data-confirmation-dialog-message]');
    const description = dialog.querySelector('[data-confirmation-dialog-description]');
    const cancelButton = dialog.querySelector('[data-confirmation-dialog-cancel]');
    const confirmButton = dialog.querySelector('[data-confirmation-dialog-confirm]');
    const confirmedForms = new WeakSet();
    let pendingForm = null;
    let pendingSubmitter = null;
    let returnFocusTo = null;

    if (!title || !message || !description || !cancelButton || !confirmButton) {
        return;
    }

    const closeDialog = (returnValue = 'cancel') => {
        if (dialog.open) {
            dialog.close(returnValue);
        }
    };

    const openDialog = (form, submitter) => {
        if (dialog.open) {
            return;
        }

        const supportingText = form.dataset.confirmDescription?.trim() || '';
        const actionLabel = form.dataset.confirmAction?.trim()
            || submitter?.textContent?.trim()
            || 'Lanjutkan';

        pendingForm = form;
        pendingSubmitter = submitter;
        returnFocusTo = submitter instanceof HTMLElement ? submitter : document.activeElement;
        title.textContent = form.dataset.confirmTitle?.trim() || 'Konfirmasi Tindakan';
        message.textContent = form.dataset.confirm.trim();
        description.textContent = supportingText;
        description.classList.toggle('hidden', supportingText === '');
        confirmButton.textContent = actionLabel;
        dialog.dataset.tone = form.dataset.confirmTone === 'danger' ? 'danger' : 'neutral';

        dialog.showModal();
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => cancelButton.focus({ preventScroll: true }));
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm?.trim()) {
            return;
        }

        if (confirmedForms.has(form)) {
            confirmedForms.delete(form);
            return;
        }

        event.preventDefault();
        openDialog(form, event.submitter);
    });

    cancelButton.addEventListener('click', () => closeDialog('cancel'));
    confirmButton.addEventListener('click', () => {
        const form = pendingForm;
        const submitter = pendingSubmitter;

        if (!form) {
            closeDialog('cancel');
            return;
        }

        pendingForm = null;
        pendingSubmitter = null;
        confirmedForms.add(form);
        closeDialog('confirm');

        requestAnimationFrame(() => {
            if (!form.isConnected) {
                return;
            }

            if (submitter instanceof HTMLElement && submitter.isConnected && submitter.form === form) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });
    });

    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) {
            return;
        }

        const bounds = dialog.getBoundingClientRect();
        const isOutside = event.clientX < bounds.left
            || event.clientX > bounds.right
            || event.clientY < bounds.top
            || event.clientY > bounds.bottom;

        if (isOutside) {
            closeDialog('cancel');
        }
    });

    dialog.addEventListener('close', () => {
        if (!document.querySelector('dialog[open]')) {
            document.body.classList.remove('overflow-hidden');
        }

        if (dialog.returnValue !== 'confirm' && returnFocusTo instanceof HTMLElement && returnFocusTo.isConnected) {
            returnFocusTo.focus({ preventScroll: true });
        }

        if (dialog.returnValue !== 'confirm') {
            pendingForm = null;
            pendingSubmitter = null;
        }

        returnFocusTo = null;
    });
};
