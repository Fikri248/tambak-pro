const filterPanelControllers = new Set();
const filterSelectControllers = new Set();

const setInert = (element, isInert) => {
    element.toggleAttribute('inert', isInert);
};

const fitPanelToViewport = (root, content) => {
    const viewportWidth = document.documentElement.clientWidth;
    const rootRect = root.getBoundingClientRect();
    const contentWidth = content.offsetWidth;
    const viewportGutter = Math.min(16, Math.max(0, (viewportWidth - contentWidth) / 2));
    const isLeftAligned = root.dataset.filterAlign === 'left';
    const naturalLeft = isLeftAligned ? rootRect.left : rootRect.right - contentWidth;
    const maximumRight = viewportWidth - viewportGutter;
    let horizontalShift = 0;

    if (naturalLeft < viewportGutter) {
        horizontalShift = viewportGutter - naturalLeft;
    }

    if (naturalLeft + contentWidth + horizontalShift > maximumRight) {
        horizontalShift -= naturalLeft + contentWidth + horizontalShift - maximumRight;
    }

    content.style.setProperty('--filter-panel-shift-x', `${horizontalShift}px`);
};

const closeSelectsWithin = (container) => {
    filterSelectControllers.forEach((controller) => {
        if (container.contains(controller.root)) {
            controller.setOpen(false);
        }
    });
};

export const initializeFilterPanels = () => {
    document.querySelectorAll('[data-filter-panel]:not([data-filter-panel-ready])').forEach((root) => {
        const trigger = root.querySelector('[data-filter-panel-trigger]');
        const content = root.querySelector('[data-filter-panel-content]');

        if (!trigger || !content) {
            return;
        }

        let isOpen = false;
        const positionContent = () => fitPanelToViewport(root, content);
        const controller = {
            root,
            setOpen(nextOpen, returnFocus = false) {
                if (nextOpen) {
                    filterPanelControllers.forEach((otherController) => {
                        if (otherController !== controller) {
                            otherController.setOpen(false);
                        }
                    });
                }

                isOpen = nextOpen;
                root.dataset.open = String(nextOpen);
                trigger.setAttribute('aria-expanded', String(nextOpen));
                content.setAttribute('aria-hidden', String(!nextOpen));
                setInert(content, !nextOpen);

                if (nextOpen) {
                    positionContent();
                }

                if (!nextOpen) {
                    closeSelectsWithin(content);

                    if (returnFocus) {
                        trigger.focus();
                    }
                }
            },
        };

        filterPanelControllers.add(controller);
        root.setAttribute('data-filter-panel-ready', '');
        trigger.hidden = false;
        controller.setOpen(root.dataset.initialOpen === 'true');

        trigger.addEventListener('click', () => controller.setOpen(!isOpen));

        window.addEventListener('resize', () => {
            if (isOpen) {
                positionContent();
            }
        }, { passive: true });

        document.addEventListener('pointerdown', (event) => {
            if (isOpen && !event.composedPath().includes(root)) {
                controller.setOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen && !event.defaultPrevented) {
                event.preventDefault();
                controller.setOpen(false, true);
            }
        });
    });
};

const createOptionNode = (option, index, listboxId) => {
    const node = document.createElement('div');
    node.id = `${listboxId}-option-${index}`;
    node.dataset.filterSelectOption = '';
    node.dataset.optionIndex = String(index);
    node.setAttribute('role', 'option');
    node.setAttribute('aria-selected', 'false');
    node.setAttribute('aria-disabled', String(option.disabled));
    node.textContent = option.textContent?.trim() || '';
    node.hidden = option.hidden;

    return node;
};

export const initializeFilterSelects = () => {
    filterSelectControllers.forEach((controller) => {
        if (!controller.root.isConnected) {
            controller.destroy();
        }
    });

    document.querySelectorAll('[data-filter-select]:not([data-filter-select-ready])').forEach((root) => {
        const nativeSelect = root.querySelector('[data-filter-select-native]');
        const trigger = root.querySelector('[data-filter-select-trigger]');
        const value = root.querySelector('[data-filter-select-value]');
        const listbox = root.querySelector('[data-filter-select-listbox]');

        if (!nativeSelect || !trigger || !value || !listbox) {
            return;
        }

        if (typeof listbox.showPopover !== 'function' || typeof listbox.hidePopover !== 'function') {
            return;
        }

        let nativeOptions = [];
        let optionNodes = [];
        let activeIndex = 0;
        let isOpen = false;
        let typeahead = '';
        let typeaheadTimer;
        let hideTimer;
        let optionObserver;
        const eventController = new AbortController();

        const syncSelectedState = () => {
            const selectedIndex = nativeSelect.selectedIndex;
            const selectedOption = nativeOptions[selectedIndex];

            value.textContent = selectedOption?.textContent?.trim() || '';
            optionNodes.forEach((node, index) => {
                const isSelected = index === selectedIndex;
                node.setAttribute('aria-selected', String(isSelected));
                node.toggleAttribute('data-selected', isSelected);
            });

            if (selectedIndex >= 0) {
                activeIndex = selectedIndex;
            }

            trigger.disabled = nativeSelect.disabled;
            trigger.toggleAttribute('aria-required', nativeSelect.required);
        };

        const scrollOptionIntoView = (option) => {
            const optionTop = option.offsetTop;
            const optionBottom = optionTop + option.offsetHeight;
            const visibleTop = listbox.scrollTop;
            const visibleBottom = visibleTop + listbox.clientHeight;

            if (optionTop < visibleTop) {
                listbox.scrollTop = Math.max(0, optionTop - 4);
            } else if (optionBottom > visibleBottom) {
                listbox.scrollTop = optionBottom - listbox.clientHeight + 4;
            }
        };

        const setActiveIndex = (nextIndex) => {
            if (nextIndex < 0
                || nextIndex >= optionNodes.length
                || nativeOptions[nextIndex]?.disabled
                || nativeOptions[nextIndex]?.hidden) {
                return;
            }

            activeIndex = nextIndex;
            optionNodes.forEach((node, index) => node.toggleAttribute('data-active', index === activeIndex));
            listbox.setAttribute('aria-activedescendant', optionNodes[activeIndex].id);
            scrollOptionIntoView(optionNodes[activeIndex]);
        };

        const positionListbox = () => {
            if (!isOpen) {
                return;
            }

            const viewportWidth = document.documentElement.clientWidth;
            const viewportHeight = document.documentElement.clientHeight;
            const triggerRect = trigger.getBoundingClientRect();
            const viewportGutter = 8;
            const popupGap = 6;
            const preferredMaxHeight = 256;
            const spaceBelow = viewportHeight - viewportGutter - triggerRect.bottom - popupGap;
            const spaceAbove = triggerRect.top - viewportGutter - popupGap;
            const desiredHeight = Math.min(preferredMaxHeight, listbox.scrollHeight);
            const opensAbove = spaceBelow < desiredHeight && spaceAbove > spaceBelow;
            const availableHeight = Math.max(80, opensAbove ? spaceAbove : spaceBelow);
            const maxHeight = Math.min(preferredMaxHeight, availableHeight);
            const renderedHeight = Math.min(desiredHeight, maxHeight);
            const popupWidth = triggerRect.width;
            const maximumLeft = Math.max(viewportGutter, viewportWidth - viewportGutter - popupWidth);
            const popupLeft = Math.min(Math.max(viewportGutter, triggerRect.left), maximumLeft);
            const popupTop = opensAbove
                ? triggerRect.top - popupGap - renderedHeight
                : triggerRect.bottom + popupGap;

            root.dataset.listboxPlacement = opensAbove ? 'top' : 'bottom';
            listbox.style.setProperty('--filter-listbox-top', `${Math.max(viewportGutter, popupTop)}px`);
            listbox.style.setProperty('--filter-listbox-left', `${popupLeft}px`);
            listbox.style.setProperty('--filter-listbox-width', `${popupWidth}px`);
            listbox.style.setProperty('--filter-listbox-max-height', `${maxHeight}px`);
        };

        const firstEnabledIndex = (fromEnd = false) => {
            const indices = nativeOptions.map((_, index) => index);
            const orderedIndices = fromEnd ? indices.reverse() : indices;

            return orderedIndices.find((index) => !nativeOptions[index].disabled && !nativeOptions[index].hidden) ?? -1;
        };

        const moveActive = (direction) => {
            if (nativeOptions.length === 0) {
                return;
            }

            let nextIndex = activeIndex;

            for (let attempt = 0; attempt < nativeOptions.length; attempt += 1) {
                nextIndex = (nextIndex + direction + nativeOptions.length) % nativeOptions.length;

                if (!nativeOptions[nextIndex].disabled && !nativeOptions[nextIndex].hidden) {
                    setActiveIndex(nextIndex);
                    return;
                }
            }
        };

        const controller = {
            root,
            setOpen(nextOpen, returnFocus = false, initialDirection = 0) {
                if (nextOpen && (nativeSelect.disabled || optionNodes.length === 0)) {
                    return;
                }

                if (nextOpen) {
                    filterSelectControllers.forEach((otherController) => {
                        if (otherController !== controller) {
                            otherController.setOpen(false);
                        }
                    });
                }

                window.clearTimeout(hideTimer);

                isOpen = nextOpen;
                root.dataset.open = String(nextOpen);
                trigger.setAttribute('aria-expanded', String(nextOpen));
                listbox.setAttribute('aria-hidden', String(!nextOpen));
                setInert(listbox, !nextOpen);

                if (nextOpen) {
                    if (!listbox.matches(':popover-open')) {
                        listbox.showPopover();
                    }

                    positionListbox();
                    setActiveIndex(nativeSelect.selectedIndex >= 0 ? nativeSelect.selectedIndex : firstEnabledIndex());

                    if (initialDirection !== 0) {
                        moveActive(initialDirection);
                    }

                    requestAnimationFrame(() => {
                        if (isOpen) {
                            positionListbox();
                            listbox.focus({ preventScroll: true });
                        }
                    });
                } else {
                    optionNodes.forEach((node) => node.removeAttribute('data-active'));
                    listbox.removeAttribute('aria-activedescendant');

                    if (returnFocus) {
                        trigger.focus();
                    }

                    if (listbox.matches(':popover-open')) {
                        hideTimer = window.setTimeout(() => {
                            if (!isOpen && listbox.matches(':popover-open')) {
                                listbox.hidePopover();
                            }
                        }, 180);
                    }
                }
            },
            destroy() {
                this.setOpen(false);
                optionObserver?.disconnect();
                eventController.abort();
                filterSelectControllers.delete(this);
            },
        };

        const selectIndex = (index) => {
            if (index < 0
                || index >= nativeOptions.length
                || nativeOptions[index].disabled
                || nativeOptions[index].hidden) {
                return;
            }

            nativeSelect.selectedIndex = index;
            syncSelectedState();
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            controller.setOpen(false, true);
        };

        const refreshOptions = () => {
            nativeOptions = Array.from(nativeSelect.options);
            optionNodes = nativeOptions.map((option, index) => createOptionNode(option, index, listbox.id));
            listbox.replaceChildren(...optionNodes);
            activeIndex = nativeSelect.selectedIndex >= 0
                ? nativeSelect.selectedIndex
                : firstEnabledIndex();
            syncSelectedState();

            if (isOpen) {
                positionListbox();
                setActiveIndex(activeIndex);
            }
        };

        filterSelectControllers.add(controller);
        nativeSelect.classList.add('filter-select-native--enhanced');
        nativeSelect.tabIndex = -1;
        nativeSelect.setAttribute('aria-hidden', 'true');
        root.setAttribute('data-filter-select-ready', '');
        trigger.hidden = false;
        listbox.hidden = false;
        refreshOptions();
        controller.setOpen(false);

        trigger.addEventListener('click', () => controller.setOpen(!isOpen), { signal: eventController.signal });
        window.addEventListener('resize', positionListbox, { passive: true, signal: eventController.signal });
        window.addEventListener('scroll', positionListbox, { capture: true, passive: true, signal: eventController.signal });
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                controller.setOpen(true, false, event.key === 'ArrowDown' ? 1 : -1);
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                controller.setOpen(true);
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                controller.setOpen(true);
                setActiveIndex(firstEnabledIndex(event.key === 'End'));
            }
        }, { signal: eventController.signal });

        listbox.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(event.key === 'ArrowDown' ? 1 : -1);
                return;
            }

            if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                setActiveIndex(firstEnabledIndex(event.key === 'End'));
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectIndex(activeIndex);
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                controller.setOpen(false, true);
                return;
            }

            if (event.key === 'Tab') {
                window.setTimeout(() => controller.setOpen(false));
                return;
            }

            if (event.key.length === 1 && !event.altKey && !event.ctrlKey && !event.metaKey) {
                typeahead += event.key.toLocaleLowerCase('id-ID');
                window.clearTimeout(typeaheadTimer);
                typeaheadTimer = window.setTimeout(() => {
                    typeahead = '';
                }, 500);

                const matchingIndex = nativeOptions.findIndex((option) => !option.disabled
                    && (option.textContent?.trim().toLocaleLowerCase('id-ID') || '').startsWith(typeahead));

                if (matchingIndex >= 0) {
                    setActiveIndex(matchingIndex);
                }
            }
        }, { signal: eventController.signal });

        listbox.addEventListener('click', (event) => {
            const option = event.target.closest('[data-filter-select-option]');

            if (option && listbox.contains(option)) {
                selectIndex(Number.parseInt(option.dataset.optionIndex || '-1', 10));
            }
        }, { signal: eventController.signal });
        listbox.addEventListener('pointermove', (event) => {
            const option = event.target.closest('[data-filter-select-option]');

            if (!option || !listbox.contains(option)) {
                return;
            }

            const index = Number.parseInt(option.dataset.optionIndex || '-1', 10);

            if (index >= 0 && !nativeOptions[index]?.disabled && !nativeOptions[index]?.hidden) {
                setActiveIndex(index);
            }
        }, { signal: eventController.signal });

        nativeSelect.addEventListener('change', syncSelectedState, { signal: eventController.signal });
        nativeSelect.addEventListener('input', syncSelectedState, { signal: eventController.signal });
        nativeSelect.addEventListener('invalid', () => {
            window.setTimeout(() => trigger.focus({ preventScroll: true }));
        }, { signal: eventController.signal });
        nativeSelect.form?.addEventListener('reset', () => window.setTimeout(syncSelectedState), { signal: eventController.signal });

        optionObserver = new MutationObserver(refreshOptions);
        optionObserver.observe(nativeSelect, {
            attributes: true,
            attributeFilter: ['disabled', 'hidden', 'label', 'required', 'selected', 'value'],
            childList: true,
            subtree: true,
            characterData: true,
        });

        document.addEventListener('pointerdown', (event) => {
            if (isOpen && !event.composedPath().includes(root)) {
                controller.setOpen(false);
            }
        }, { signal: eventController.signal });
    });
};

export const initializeDateRangeFilters = () => {
    document.querySelectorAll('[data-date-range-filter]:not([data-date-range-ready])').forEach((root) => {
        const from = root.querySelector('[data-date-range-from]');
        const to = root.querySelector('[data-date-range-to]');

        if (!from || !to) {
            return;
        }

        const syncBounds = () => {
            if (from.value) {
                to.min = from.value;
            } else {
                to.removeAttribute('min');
            }

            if (to.value) {
                from.max = to.value;
            } else {
                from.removeAttribute('max');
            }
        };

        root.setAttribute('data-date-range-ready', '');
        from.addEventListener('change', syncBounds);
        to.addEventListener('change', syncBounds);
        syncBounds();
    });
};

export const initializeFilterControls = () => {
    initializeFilterPanels();
    initializeFilterSelects();
    initializeDateRangeFilters();
};
