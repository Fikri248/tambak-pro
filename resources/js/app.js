import './bootstrap';
import { initializeAccountMenu } from './account-menu';
import { initializeConfirmationDialog } from './confirmation-dialog';
import { initializeChartOfAccountLookups } from './chart-of-account-lookups';
import { initializeCrudModal } from './crud-modal';
import { initializeDashboardCharts } from './dashboard-charts';
import { initializeFilterControls } from './filter-controls';
import { initializePageSizeControls } from './page-size';
import { initializeVendorTypeLookups } from './vendor-type-lookups';

const initializeSidebar = () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const openButtons = document.querySelectorAll('[data-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-sidebar-close]');
    const collapseButton = document.querySelector('[data-sidebar-collapse]');
    const collapseIcon = document.querySelector('[data-sidebar-collapse-icon]');

    if (!sidebar || !backdrop) {
        return;
    }

    const setSidebarOpen = (isOpen) => {
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        backdrop.classList.toggle('hidden', !isOpen);
        document.body.classList.toggle('overflow-hidden', isOpen && window.innerWidth < 1024);

        openButtons.forEach((button) => {
            button.setAttribute('aria-expanded', String(isOpen));
        });
    };

    const setDesktopCollapsed = (isCollapsed, persist = true) => {
        document.documentElement.dataset.sidebarCollapsed = String(isCollapsed);

        if (collapseButton) {
            const label = isCollapsed ? 'Tampilkan sidebar' : 'Sembunyikan sidebar';
            collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
            collapseButton.setAttribute('aria-label', label);
            collapseButton.setAttribute('title', label);
            collapseButton.querySelector('[data-sidebar-label]')?.replaceChildren(label);
        }

        collapseIcon?.classList.toggle('rotate-180', isCollapsed);

        if (persist) {
            try {
                localStorage.setItem('sidebar-collapsed', String(isCollapsed));
            } catch (_) {}
        }
    };

    setDesktopCollapsed(document.documentElement.dataset.sidebarCollapsed === 'true', false);

    openButtons.forEach((button) => button.addEventListener('click', () => setSidebarOpen(true)));
    closeButtons.forEach((button) => button.addEventListener('click', () => setSidebarOpen(false)));
    collapseButton?.addEventListener('click', () => {
        setDesktopCollapsed(document.documentElement.dataset.sidebarCollapsed !== 'true');
    });
    backdrop.addEventListener('click', () => setSidebarOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebarOpen(false);
        }
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) {
            setSidebarOpen(false);
        }
    });
};

const initializePasswordVisibility = () => {
    document.querySelectorAll('[data-password-toggle]:not([data-password-toggle-ready])').forEach((toggle) => {
        const controlledId = toggle.getAttribute('aria-controls');
        const input = controlledId
            ? document.getElementById(controlledId)
            : toggle.parentElement?.querySelector('[data-password-input]');

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        toggle.setAttribute('data-password-toggle-ready', '');
        const showIcon = toggle.querySelector('[data-password-show-icon]');
        const hideIcon = toggle.querySelector('[data-password-hide-icon]');

        toggle.addEventListener('click', () => {
            const isVisible = input.type === 'text';

            input.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!isVisible));
            toggle.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
            showIcon?.classList.toggle('hidden', !isVisible);
            hideIcon?.classList.toggle('hidden', isVisible);
        });
    });
};

const initializeNotifications = () => {
    document.addEventListener('app:notification', (event) => {
        const existing = document.querySelector('[data-app-notification]');
        const notification = document.createElement('div');

        existing?.remove();
        notification.dataset.appNotification = '';
        notification.setAttribute('role', 'status');
        notification.className = 'fixed right-4 top-20 z-[70] max-w-[calc(100vw-2rem)] rounded-lg border border-neutral-200 bg-white px-4 py-3 text-sm font-medium text-neutral-900 shadow-lg shadow-neutral-950/10';
        notification.textContent = event.detail?.message || 'Perubahan berhasil disimpan.';
        document.body.append(notification);
        window.setTimeout(() => notification.remove(), 4500);
    });
};

const initializeStockingForm = () => {
    const form = document.querySelector('[data-stocking-form]:not([data-stocking-form-ready])');

    if (!form) {
        return;
    }

    form.setAttribute('data-stocking-form-ready', '');

    const fields = {
        location: form.querySelector('[data-summary-source="location"]'),
        commodity: form.querySelector('[data-summary-source="commodity"]'),
        vendor: form.querySelector('[data-summary-source="vendor"]'),
        batch: form.querySelector('[data-summary-source="batch"]'),
        quantity: form.querySelector('[data-summary-source="quantity"]'),
        cost: form.querySelector('[data-summary-source="cost"]'),
    };
    const formatNumber = (value, maximumFractionDigits = 3) => new Intl.NumberFormat('id-ID', {
        maximumFractionDigits,
    }).format(Number.isFinite(value) ? value : 0);
    const formatCurrency = (value) => `Rp${formatNumber(value, 2)}`;
    const selectedOption = (select) => select?.options[select.selectedIndex];
    const output = (selector, value) => {
        const element = form.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    };

    const updateSummary = () => {
        const location = selectedOption(fields.location);
        const commodity = selectedOption(fields.commodity);
        const vendor = selectedOption(fields.vendor);
        const unit = commodity?.dataset.unit || 'unit';
        const quantity = Number.parseFloat(fields.quantity?.value || '0');
        const totalCost = Number.parseFloat(fields.cost?.value || '0');
        const unitCost = quantity > 0 ? totalCost / quantity : 0;

        output('[data-commodity-unit]', unit);
        output('[data-unit-label]', unit);
        output('[data-unit-cost]', formatCurrency(unitCost));
        output('[data-summary-location]', location?.dataset.label || 'Belum dipilih');
        output('[data-summary-commodity]', commodity?.dataset.label || 'Belum dipilih');
        output('[data-summary-vendor]', vendor?.dataset.label || 'Belum dipilih');
        const batchCode = fields.batch?.value ?? fields.batch?.dataset.value ?? '';
        output('[data-summary-batch]', batchCode.trim().toUpperCase() || 'Dibuat otomatis');
        output('[data-summary-quantity]', `${formatNumber(quantity)} ${unit}`);
        output('[data-summary-cost]', formatCurrency(totalCost));
        output('[data-summary-unit-cost]', `${formatCurrency(unitCost)} / ${unit}`);
    };

    Object.values(fields).forEach((field) => {
        field?.addEventListener('input', updateSummary);
        field?.addEventListener('change', updateSummary);
    });

    form.addEventListener('submit', () => {
        if (form.closest('[data-crud-modal-body]')) {
            return;
        }

        const submitButton = form.querySelector('[data-submit-button]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
        }
    });

    updateSummary();
};

const initializeMovementForm = () => {
    const form = document.querySelector('[data-movement-form]:not([data-movement-form-ready])');

    if (!form) {
        return;
    }

    form.setAttribute('data-movement-form-ready', '');

    const parseData = (selector) => {
        const element = form.querySelector(selector);

        try {
            return JSON.parse(element?.textContent || '{}');
        } catch {
            return {};
        }
    };
    const batchOptions = parseData('[data-movement-batches]');
    const destinationStocks = parseData('[data-destination-stocks]');
    const source = form.querySelector('[data-movement-source]');
    const batch = form.querySelector('[data-movement-batch]');
    const destination = form.querySelector('[data-movement-destination]');
    const quantity = form.querySelector('[data-movement-quantity]');
    const formatNumber = (value) => new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 3,
    }).format(Number.isFinite(value) ? value : 0);
    const selectedOption = (select) => select?.options[select.selectedIndex];
    const output = (selector, value) => {
        const element = form.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    };
    const selectedBatch = () => (batchOptions[source?.value] || []).find(
        (item) => String(item.id) === String(batch?.value),
    );

    const updateDestinationOptions = () => {
        let selectionChanged = false;

        Array.from(destination?.options || []).forEach((option) => {
            const isSource = option.value !== '' && option.value === source?.value;
            option.disabled = isSource;
            option.hidden = isSource;

            if (isSource && option.selected) {
                destination.value = '';
                selectionChanged = true;
            }
        });

        if (selectionChanged) {
            destination.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const updatePreview = () => {
        const item = selectedBatch();
        const sourceOption = selectedOption(source);
        const destinationOption = selectedOption(destination);
        const unit = item?.unit || 'unit';
        const available = Number(item?.quantity || 0);
        const moved = Number.parseFloat(quantity?.value || '0');
        const safeMoved = moved > 0 && moved <= available ? moved : 0;
        const destinationCurrent = Number(destinationStocks[item?.id]?.[destination?.value] || 0);
        const warning = form.querySelector('[data-preview-warning]');

        if (quantity) {
            if (available > 0) {
                quantity.max = String(available);
            } else {
                quantity.removeAttribute('max');
            }
        }

        output('[data-available-stock]', `${formatNumber(available)} ${unit}`);
        output('[data-movement-unit]', unit);
        output('[data-preview-batch]', item ? `${item.batch_code} — ${item.commodity}` : 'Batch belum dipilih');
        output('[data-preview-moved]', `${formatNumber(safeMoved)} ${unit}`);
        output('[data-preview-source]', sourceOption?.dataset.label || 'Petak asal belum dipilih');
        output('[data-preview-destination]', destinationOption?.dataset.label || 'Petak tujuan belum dipilih');
        output('[data-preview-source-stock]', `${formatNumber(available)} → ${formatNumber(available - safeMoved)} ${unit}`);
        output('[data-preview-destination-stock]', `${formatNumber(destinationCurrent)} → ${formatNumber(destinationCurrent + safeMoved)} ${unit}`);

        if (warning) {
            const isOverStock = moved > available && available > 0;
            const isNonPositive = quantity?.value !== '' && moved <= 0;
            warning.textContent = isOverStock
                ? 'Jumlah melebihi stok tersedia.'
                : (isNonPositive ? 'Jumlah yang dipindahkan harus lebih dari 0.' : '');
            warning.classList.toggle('hidden', !isOverStock && !isNonPositive);
        }
    };

    const populateBatches = (preferredBatch = '') => {
        const options = batchOptions[source?.value] || [];
        batch.innerHTML = '';
        batch.append(new Option(options.length ? 'Pilih Batch' : 'Tidak ada Batch tersedia', ''));

        options.forEach((item) => {
            const label = `${item.batch_code} — ${item.commodity} — ${formatNumber(Number(item.quantity))} ${item.unit}`;
            batch.append(new Option(label, item.id, false, String(item.id) === String(preferredBatch)));
        });

        batch.disabled = options.length === 0;
        updatePreview();
    };

    source?.addEventListener('change', () => {
        populateBatches();
        updateDestinationOptions();
        updatePreview();
    });
    batch?.addEventListener('change', updatePreview);
    destination?.addEventListener('change', updatePreview);
    quantity?.addEventListener('input', updatePreview);

    form.addEventListener('submit', () => {
        if (form.closest('[data-crud-modal-body]')) {
            return;
        }

        const submitButton = form.querySelector('[data-movement-submit]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
        }
    });

    if (form.dataset.oldSource) {
        source.value = form.dataset.oldSource;
    }

    populateBatches(form.dataset.oldBatch || '');
    updateDestinationOptions();

    if (form.dataset.oldDestination && form.dataset.oldDestination !== source.value) {
        destination.value = form.dataset.oldDestination;
    }

    updatePreview();
};

const initializeAdjustmentForm = () => {
    const form = document.querySelector('[data-adjustment-form]:not([data-adjustment-form-ready])');

    if (!form) {
        return;
    }

    form.setAttribute('data-adjustment-form-ready', '');

    const dataElement = form.querySelector('[data-adjustment-batches]');
    let batchOptions = {};

    try {
        batchOptions = JSON.parse(dataElement?.textContent || '{}');
    } catch {
        batchOptions = {};
    }

    const location = form.querySelector('[data-adjustment-location]');
    const batch = form.querySelector('[data-adjustment-batch]');
    const type = form.querySelector('[data-adjustment-type]');
    const direction = form.querySelector('[data-adjustment-direction]');
    const directionField = form.querySelector('[data-adjustment-direction-field]');
    const quantity = form.querySelector('[data-adjustment-quantity]');
    const reason = form.querySelector('[name="reason"]');
    const formatNumber = (value) => new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 3,
    }).format(Number.isFinite(value) ? value : 0);
    const selectedOption = (select) => select?.options[select.selectedIndex];
    const output = (selector, value) => {
        const element = form.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    };
    const selectedBatch = () => (batchOptions[location?.value] || []).find(
        (item) => String(item.id) === String(batch?.value),
    );
    const isIncrease = () => type?.value === 'CORRECTION_IN'
        || (type?.value === 'OTHER' && direction?.value === 'IN');
    const hasDirection = () => type?.value !== 'OTHER' || ['IN', 'OUT'].includes(direction?.value);

    const updateDirection = () => {
        const showDirection = type?.value === 'OTHER';
        directionField?.classList.toggle('hidden', !showDirection);

        if (direction) {
            direction.required = showDirection;

            if (!showDirection) {
                direction.value = '';
                direction.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    };

    const updatePreview = () => {
        const item = selectedBatch();
        const current = Number(item?.quantity || 0);
        const requested = Number.parseFloat(quantity?.value || '0');
        const increase = isIncrease();
        const directionReady = hasDirection();
        const validQuantity = requested > 0 && directionReady && (increase || requested <= current);
        const signedChange = validQuantity ? (increase ? requested : -requested) : 0;
        const after = current + signedChange;
        const unit = item?.unit || 'unit';
        const typeOption = selectedOption(type);
        const warning = form.querySelector('[data-adjustment-preview-warning]');

        if (quantity) {
            if (!increase && current > 0) {
                quantity.max = String(current);
            } else {
                quantity.removeAttribute('max');
            }
        }

        output('[data-adjustment-stock]', `${formatNumber(current)} ${unit}`);
        output('[data-adjustment-unit]', unit);
        output('[data-adjustment-preview-location]', selectedOption(location)?.dataset.label || 'Belum dipilih');
        output('[data-adjustment-preview-batch]', item ? `${item.batch_code} — ${item.commodity}` : 'Batch belum dipilih');
        output('[data-adjustment-preview-type]', typeOption?.dataset.label || 'Jenis belum dipilih');
        output('[data-adjustment-preview-before]', `${formatNumber(current)} ${unit}`);
        output('[data-adjustment-preview-change]', `${signedChange > 0 ? '+' : ''}${formatNumber(signedChange)} ${unit}`);
        output('[data-adjustment-preview-after]', `${formatNumber(after)} ${unit}`);
        output('[data-adjustment-preview-reason]', reason?.value.trim() || 'Belum diisi');

        if (warning) {
            const isOverStock = requested > current && !increase && current >= 0;
            const isNonPositive = quantity?.value !== '' && requested <= 0;
            const needsDirection = type?.value === 'OTHER' && !directionReady;
            warning.textContent = isOverStock
                ? 'Stok tidak mencukupi untuk perubahan ini.'
                : (isNonPositive
                    ? 'Jumlah perubahan harus lebih dari 0.'
                    : (needsDirection ? 'Pilih arah perubahan untuk jenis Lainnya.' : ''));
            warning.classList.toggle('hidden', !isOverStock && !isNonPositive && !needsDirection);
        }
    };

    const populateBatches = (preferredBatch = '') => {
        const options = batchOptions[location?.value] || [];
        batch.innerHTML = '';
        batch.append(new Option(options.length ? 'Pilih Batch' : 'Tidak ada Batch tersedia', ''));

        options.forEach((item) => {
            const label = `${item.batch_code} — ${item.commodity} — ${formatNumber(Number(item.quantity))} ${item.unit}`;
            batch.append(new Option(label, item.id, false, String(item.id) === String(preferredBatch)));
        });

        batch.disabled = options.length === 0;
        updatePreview();
    };

    location?.addEventListener('change', () => populateBatches());
    batch?.addEventListener('change', updatePreview);
    type?.addEventListener('change', () => {
        updateDirection();
        updatePreview();
    });
    direction?.addEventListener('change', updatePreview);
    quantity?.addEventListener('input', updatePreview);
    reason?.addEventListener('input', updatePreview);

    form.addEventListener('submit', () => {
        if (form.closest('[data-crud-modal-body]')) {
            return;
        }

        const submitButton = form.querySelector('[data-adjustment-submit]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
        }
    });

    if (form.dataset.oldLocation) {
        location.value = form.dataset.oldLocation;
    }

    populateBatches(form.dataset.oldBatch || '');
    updateDirection();
    updatePreview();
};

const initializeFeedingForm = () => {
    const form = document.querySelector('[data-feeding-form]:not([data-feeding-form-ready])');

    if (!form) {
        return;
    }

    form.setAttribute('data-feeding-form-ready', '');

    const parseData = (selector) => {
        const element = form.querySelector(selector);

        try {
            return JSON.parse(element?.textContent || '{}');
        } catch {
            return {};
        }
    };
    const scopes = parseData('[data-feeding-scopes]');
    const items = parseData('[data-feeding-items]');
    const location = form.querySelector('[data-feeding-location]');
    const batch = form.querySelector('[data-feeding-batch]');
    const item = form.querySelector('[data-feeding-item]');
    const vendor = form.querySelector('[data-feeding-vendor]');
    const quantity = form.querySelector('[data-feeding-quantity]');
    const unitCost = form.querySelector('[data-feeding-cost]');
    const formatNumber = (value, maximumFractionDigits = 3) => new Intl.NumberFormat('id-ID', {
        maximumFractionDigits,
    }).format(Number.isFinite(value) ? value : 0);
    const formatCurrency = (value) => `Rp${formatNumber(value, 2)}`;
    const selectedOption = (select) => select?.options[select.selectedIndex];
    const output = (selector, value) => {
        const element = form.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    };
    const selectedItem = () => items[item?.value] || null;
    const selectedScope = () => {
        const locationScope = scopes[location?.value];

        if (!locationScope) {
            return null;
        }

        if (!batch?.value) {
            return {
                label: 'Seluruh Petak',
                quantity: Number(locationScope.total || 0),
                unit: locationScope.unit || 'unit',
            };
        }

        const selectedBatch = (locationScope.batches || []).find(
            (entry) => String(entry.id) === String(batch.value),
        );

        return selectedBatch ? {
            label: `${selectedBatch.batch_code} — ${selectedBatch.commodity}`,
            quantity: Number(selectedBatch.quantity || 0),
            unit: selectedBatch.unit || 'unit',
        } : null;
    };

    const updatePreview = () => {
        const itemData = selectedItem();
        const scope = selectedScope();
        const usedQuantity = Number.parseFloat(quantity?.value || '0');
        const price = Number.parseFloat(unitCost?.value || '0');
        const total = Math.max(usedQuantity, 0) * Math.max(price, 0);
        const itemUnit = itemData?.unit || 'unit';
        const vendorOption = selectedOption(vendor);
        const locationOption = selectedOption(location);

        output('[data-feeding-stock]', `${formatNumber(scope?.quantity || 0)} ${scope?.unit || 'unit'}`);
        output('[data-feeding-unit]', itemUnit);
        output('[data-feeding-cost-helper]', `${formatCurrency(price)} / ${itemUnit}`);
        output('[data-feeding-total]', formatCurrency(total));
        output('[data-feeding-summary-location]', locationOption?.dataset.label || 'Belum dipilih');
        output('[data-feeding-summary-scope]', scope?.label || 'Belum dipilih');
        output('[data-feeding-summary-stock]', `${formatNumber(scope?.quantity || 0)} ${scope?.unit || 'unit'}`);
        output('[data-feeding-summary-item]', itemData ? `${itemData.code} — ${itemData.name}` : 'Item belum dipilih');
        output('[data-feeding-summary-type]', itemData?.type_label || 'Jenis belum dipilih');
        output('[data-feeding-summary-vendor]', vendorOption?.dataset.label || 'Tanpa Vendor');
        output('[data-feeding-summary-quantity]', `${formatNumber(usedQuantity)} ${itemUnit}`);
        output('[data-feeding-summary-cost]', `${formatCurrency(price)} / ${itemUnit}`);
        output('[data-feeding-summary-total]', formatCurrency(total));
    };

    const populateScopes = (preferredBatch = '') => {
        const locationScope = scopes[location?.value];
        batch.innerHTML = '';

        if (!locationScope) {
            batch.append(new Option('Pilih petak terlebih dahulu', ''));
            batch.disabled = true;
            updatePreview();
            return;
        }

        batch.append(new Option(
            `Seluruh Petak — ${formatNumber(Number(locationScope.total))} ${locationScope.unit}`,
            '',
            false,
            preferredBatch === '',
        ));
        (locationScope.batches || []).forEach((entry) => {
            const label = `${entry.batch_code} — ${entry.commodity} — ${formatNumber(Number(entry.quantity))} ${entry.unit}`;
            batch.append(new Option(label, entry.id, false, String(entry.id) === String(preferredBatch)));
        });
        batch.disabled = false;
        updatePreview();
    };

    const applyItemDefaults = () => {
        const itemData = selectedItem();

        if (vendor) {
            vendor.value = itemData?.default_vendor_id ? String(itemData.default_vendor_id) : '';
            vendor.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (unitCost) {
            unitCost.value = itemData ? String(itemData.default_price) : '';
        }

        updatePreview();
    };

    location?.addEventListener('change', () => populateScopes());
    batch?.addEventListener('change', updatePreview);
    item?.addEventListener('change', applyItemDefaults);
    vendor?.addEventListener('change', updatePreview);
    quantity?.addEventListener('input', updatePreview);
    unitCost?.addEventListener('input', updatePreview);

    form.addEventListener('submit', () => {
        if (form.closest('[data-crud-modal-body]')) {
            return;
        }

        const submitButton = form.querySelector('[data-feeding-submit]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
        }
    });

    if (form.dataset.oldLocation) {
        location.value = form.dataset.oldLocation;
    }

    if (form.dataset.oldItem) {
        item.value = form.dataset.oldItem;
    }

    populateScopes(form.dataset.oldBatch || '');

    if (form.dataset.oldVendor) {
        vendor.value = form.dataset.oldVendor;
    }

    updatePreview();
};

const initializeDynamicContent = () => {
    initializeChartOfAccountLookups();
    initializeVendorTypeLookups();
    initializePasswordVisibility();
    initializeStockingForm();
    initializeMovementForm();
    initializeAdjustmentForm();
    initializeFeedingForm();
    initializeFilterControls();
};

document.addEventListener('DOMContentLoaded', initializeSidebar);
document.addEventListener('DOMContentLoaded', initializeAccountMenu);
document.addEventListener('DOMContentLoaded', initializeNotifications);
document.addEventListener('DOMContentLoaded', initializeDynamicContent);
document.addEventListener('DOMContentLoaded', initializeDashboardCharts);
document.addEventListener('DOMContentLoaded', initializeConfirmationDialog);
document.addEventListener('DOMContentLoaded', initializeCrudModal);
document.addEventListener('DOMContentLoaded', initializePageSizeControls);
document.addEventListener('app:content-loaded', initializeDynamicContent);
