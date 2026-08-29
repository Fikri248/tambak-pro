const appendModalParameter = (value) => {
    const url = new URL(value, window.location.href);

    url.searchParams.set('modal', '1');

    return url;
};

const parseFragment = (html) => {
    const template = document.createElement('template');

    template.innerHTML = html.trim();

    return template.content.querySelector('[data-crud-modal-fragment]');
};

export const initializeCrudModal = () => {
    const dialog = document.querySelector('[data-crud-modal-shell]');

    if (typeof HTMLDialogElement === 'undefined'
        || !(dialog instanceof HTMLDialogElement)
        || dialog.dataset.crudModalReady === 'true') {
        return;
    }

    const title = dialog.querySelector('[data-crud-modal-title]');
    const body = dialog.querySelector('[data-crud-modal-body]');
    const closeButton = dialog.querySelector('[data-crud-modal-close]');

    if (!title || !body || !closeButton) {
        return;
    }

    dialog.dataset.crudModalReady = 'true';

    let activeController = null;
    let activeUrl = null;
    let returnFocusTo = null;

    const setLoading = (fallbackUrl) => {
        body.innerHTML = `
            <div class="flex min-h-48 flex-col items-center justify-center gap-3 text-center" role="status" aria-live="polite">
                <span class="size-6 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-700" aria-hidden="true"></span>
                <p class="text-sm text-neutral-600">Memuat data...</p>
                <a class="text-xs font-medium text-neutral-700 underline underline-offset-4" href="${fallbackUrl}">Buka sebagai halaman</a>
            </div>
        `;
    };

    const setError = (fallbackUrl) => {
        body.innerHTML = `
            <div class="flex min-h-48 flex-col items-center justify-center gap-3 text-center" role="alert">
                <p class="text-sm font-medium text-neutral-900">Gagal memuat data. Silakan coba lagi.</p>
                <a class="text-sm font-medium text-neutral-700 underline underline-offset-4" href="${fallbackUrl}">Buka sebagai halaman</a>
            </div>
        `;
    };

    const clearValidationErrors = () => {
        body.querySelector('[data-crud-modal-validation]')?.remove();

        body.querySelectorAll('[data-crud-modal-invalid]').forEach((field) => {
            const previousInvalid = field.dataset.crudModalPreviousInvalid;
            const previousDescribedBy = field.dataset.crudModalPreviousDescribedBy;

            if (previousInvalid === '') {
                field.removeAttribute('aria-invalid');
            } else {
                field.setAttribute('aria-invalid', previousInvalid);
            }

            if (previousDescribedBy === '') {
                field.removeAttribute('aria-describedby');
            } else {
                field.setAttribute('aria-describedby', previousDescribedBy);
            }

            delete field.dataset.crudModalInvalid;
            delete field.dataset.crudModalPreviousInvalid;
            delete field.dataset.crudModalPreviousDescribedBy;
        });
    };

    const markInvalidField = (field, summaryId) => {
        if (!(field instanceof HTMLElement) || field.dataset.crudModalInvalid !== undefined) {
            return;
        }

        field.dataset.crudModalInvalid = '';
        field.dataset.crudModalPreviousInvalid = field.getAttribute('aria-invalid') || '';
        field.dataset.crudModalPreviousDescribedBy = field.getAttribute('aria-describedby') || '';
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', [
            field.dataset.crudModalPreviousDescribedBy,
            summaryId,
        ].filter(Boolean).join(' '));
    };

    const showValidationErrors = (errors) => {
        clearValidationErrors();

        const messages = Object.values(errors || {}).flat();
        const summary = document.createElement('div');

        summary.id = 'crud-modal-validation-summary';
        summary.dataset.crudModalValidation = '';
        summary.tabIndex = -1;
        summary.setAttribute('role', 'alert');
        summary.className = 'mb-5 rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-3 text-sm text-neutral-800';

        const heading = document.createElement('p');
        heading.className = 'font-semibold text-neutral-950';
        heading.textContent = 'Periksa kembali data yang diisi.';
        summary.append(heading);

        if (messages.length > 0) {
            const list = document.createElement('ul');
            list.className = 'mt-2 list-disc space-y-1 pl-5 text-xs leading-5 text-neutral-700';

            messages.forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                list.append(item);
            });

            summary.append(list);
        }

        body.prepend(summary);

        Object.keys(errors || {}).forEach((name) => {
            const control = [...body.querySelectorAll('[name]')]
                .find((candidate) => candidate.name === name);

            markInvalidField(control, summary.id);
            markInvalidField(
                control?.closest('[data-filter-select]')?.querySelector('[data-filter-select-trigger]'),
                summary.id,
            );
        });

        summary.focus({ preventScroll: true });
    };

    const renderFragment = (fragment) => {
        title.textContent = fragment.dataset.crudModalTitle || 'Detail';
        body.replaceChildren(fragment);
        document.dispatchEvent(new CustomEvent('app:content-loaded', {
            detail: { container: body },
        }));
    };

    const open = async (trigger) => {
        const fallbackUrl = trigger.href;
        const requestUrl = appendModalParameter(fallbackUrl);

        activeController?.abort();
        activeController = new AbortController();
        activeUrl = new URL(fallbackUrl, window.location.href);
        if (!dialog.open) {
            returnFocusTo = trigger.dataset.crudModalReturnFocus
                ? document.querySelector(trigger.dataset.crudModalReturnFocus)
                : trigger;
        }
        dialog.dataset.size = trigger.dataset.crudModalSize || (activeUrl.pathname.endsWith('/create') || activeUrl.pathname.endsWith('/edit') ? 'xl' : 'lg');
        title.textContent = trigger.dataset.crudModalTitle || 'Memuat data';
        setLoading(fallbackUrl);

        if (!dialog.open) {
            dialog.showModal();
            document.body.classList.add('overflow-hidden');
        }

        try {
            const response = await fetch(requestUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const fragment = parseFragment(await response.text());

            if (!fragment) {
                throw new Error('Modal fragment missing');
            }

            renderFragment(fragment);
            closeButton.focus({ preventScroll: true });
        } catch (error) {
            if (error.name !== 'AbortError') {
                setError(fallbackUrl);
            }
        }
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('a[data-crud-modal]');

        if (!trigger
            || event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey) {
            return;
        }

        event.preventDefault();
        open(trigger);
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)
            || !dialog.open
            || !body.contains(form)
            || event.submitter?.matches('[data-coa-lookup-submit]')
            || form.dataset.confirm?.trim()) {
            return;
        }

        event.preventDefault();

        const submitter = event.submitter;
        const originalContent = submitter?.innerHTML;

        if (submitter instanceof HTMLButtonElement) {
            submitter.disabled = true;
            submitter.textContent = 'Menyimpan...';
        }

        clearValidationErrors();

        try {
            const formData = new FormData(form);

            if (submitter?.name) {
                formData.set(submitter.name, submitter.value);
            }

            const response = await fetch(appendModalParameter(form.action), {
                method: form.method || 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                referrer: activeUrl ? appendModalParameter(activeUrl.href).href : window.location.href,
            });

            if (response.status === 422) {
                const payload = await response.json();
                showValidationErrors(payload.errors);
                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            if (response.headers.get('content-type')?.includes('application/json')) {
                const payload = await response.json();

                dialog.close('success');
                document.dispatchEvent(new CustomEvent('app:notification', {
                    detail: { message: payload.message },
                }));

                return;
            }

            const responseUrl = new URL(response.url, window.location.href);

            if (response.redirected && responseUrl.pathname !== activeUrl?.pathname) {
                window.location.assign(responseUrl.href);
                return;
            }

            const fragment = parseFragment(await response.text());

            if (!fragment) {
                window.location.assign(responseUrl.href);
                return;
            }

            activeUrl = responseUrl;
            renderFragment(fragment);
        } catch {
            showValidationErrors({ request: ['Gagal menyimpan data. Silakan coba lagi.'] });
        } finally {
            if (submitter instanceof HTMLButtonElement && submitter.isConnected) {
                submitter.disabled = false;
                submitter.innerHTML = originalContent;
            }
        }
    });

    document.addEventListener('click', (event) => {
        const cancel = event.target.closest('[data-crud-modal-cancel]');

        if (!cancel
            || !dialog.open
            || !body.contains(cancel)
            || event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey) {
            return;
        }

        event.preventDefault();
        dialog.close('cancel');
    });

    closeButton.addEventListener('click', () => dialog.close('cancel'));
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) {
            return;
        }

        const bounds = dialog.getBoundingClientRect();
        const outside = event.clientX < bounds.left
            || event.clientX > bounds.right
            || event.clientY < bounds.top
            || event.clientY > bounds.bottom;

        if (outside) {
            dialog.close('cancel');
        }
    });
    dialog.addEventListener('close', () => {
        activeController?.abort();
        activeController = null;
        activeUrl = null;
        body.replaceChildren();
        document.dispatchEvent(new CustomEvent('app:content-loaded', {
            detail: { container: body },
        }));

        if (!document.querySelector('dialog[open]')) {
            document.body.classList.remove('overflow-hidden');
        }

        if (returnFocusTo instanceof HTMLElement && returnFocusTo.isConnected) {
            returnFocusTo.focus({ preventScroll: true });
        }

        returnFocusTo = null;
    });
};
