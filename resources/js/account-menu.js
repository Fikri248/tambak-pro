export const initializeAccountMenu = () => {
    const container = document.querySelector('[data-account-menu]');
    const trigger = container?.querySelector('[data-account-menu-trigger]');
    const panel = container?.querySelector('[data-account-menu-panel]');
    const chevron = container?.querySelector('[data-account-menu-chevron]');

    if (!container || !trigger || !panel || container.dataset.accountMenuReady === 'true') {
        return;
    }

    container.dataset.accountMenuReady = 'true';

    const menuItems = () => [...panel.querySelectorAll('[role="menuitem"]')]
        .filter((item) => !item.disabled);
    const isOpen = () => trigger.getAttribute('aria-expanded') === 'true';
    const setOpen = (open, focusTarget = null) => {
        trigger.setAttribute('aria-expanded', String(open));
        panel.setAttribute('aria-hidden', String(!open));
        panel.classList.toggle('pointer-events-none', !open);
        panel.classList.toggle('invisible', !open);
        panel.classList.toggle('opacity-0', !open);
        panel.classList.toggle('-translate-y-1', !open);
        panel.classList.toggle('scale-[.98]', !open);
        panel.classList.toggle('opacity-100', open);
        panel.classList.toggle('translate-y-0', open);
        panel.classList.toggle('scale-100', open);
        chevron?.classList.toggle('rotate-180', open);

        if (focusTarget instanceof HTMLElement) {
            focusTarget.focus({ preventScroll: true });
        }
    };

    trigger.addEventListener('click', () => setOpen(!isOpen()));
    trigger.addEventListener('keydown', (event) => {
        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) {
            return;
        }

        event.preventDefault();
        const items = menuItems();
        setOpen(true, event.key === 'ArrowUp' ? items.at(-1) : items[0]);
    });

    panel.addEventListener('keydown', (event) => {
        const items = menuItems();
        const currentIndex = items.indexOf(document.activeElement);

        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false, trigger);
        } else if (event.key === 'ArrowDown' && items.length > 0) {
            event.preventDefault();
            items[(currentIndex + 1) % items.length].focus();
        } else if (event.key === 'ArrowUp' && items.length > 0) {
            event.preventDefault();
            items[(currentIndex - 1 + items.length) % items.length].focus();
        } else if (event.key === 'Home' && items.length > 0) {
            event.preventDefault();
            items[0].focus();
        } else if (event.key === 'End' && items.length > 0) {
            event.preventDefault();
            items.at(-1).focus();
        }
    });

    panel.addEventListener('click', (event) => {
        if (event.target.closest('[data-account-menu-action]')) {
            setOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (isOpen() && !container.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            setOpen(false, trigger);
        }
    });
};
