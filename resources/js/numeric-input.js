export const initializeNumericInputWheelGuard = () => {
    if (document.documentElement.dataset.numericInputWheelGuardReady === 'true') {
        return;
    }

    document.documentElement.dataset.numericInputWheelGuardReady = 'true';

    document.addEventListener('wheel', (event) => {
        const input = event.target instanceof Element
            ? event.target.closest('input[type="number"]')
            : null;

        if (!(input instanceof HTMLInputElement)
            || input.disabled
            || input.readOnly
            || document.activeElement !== input) {
            return;
        }

        input.blur();

        window.requestAnimationFrame(() => {
            if (input.isConnected && document.activeElement === document.body) {
                input.focus({ preventScroll: true });
            }
        });
    }, { capture: true, passive: true });
};
