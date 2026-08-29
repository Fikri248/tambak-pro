import Chart from 'chart.js/auto';

const palette = ['#171717', '#404040', '#737373', '#a3a3a3', '#d4d4d4'];
const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 });
const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const parseChartData = () => {
    const element = document.querySelector('[data-dashboard-chart-data]');

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent || '{}');
    } catch {
        return null;
    }
};

const formatValue = (value, format, unit = null) => {
    if (format === 'currency') {
        return currencyFormatter.format(value);
    }

    if (format === 'count') {
        return `${numberFormatter.format(value)} transaksi`;
    }

    if (format === 'activity') {
        return `${numberFormatter.format(value)} aktivitas`;
    }

    return `${numberFormatter.format(value)} ${unit || 'ekor'}`;
};

const compactCurrency = (value) => {
    const absolute = Math.abs(value);

    if (absolute >= 1_000_000_000) {
        return `Rp${numberFormatter.format(value / 1_000_000_000)} M`;
    }

    if (absolute >= 1_000_000) {
        return `Rp${numberFormatter.format(value / 1_000_000)} jt`;
    }

    if (absolute >= 1_000) {
        return `Rp${numberFormatter.format(value / 1_000)} rb`;
    }

    return `Rp${numberFormatter.format(value)}`;
};

const baseOptions = (data) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#171717',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 10,
            callbacks: {
                label: (context) => {
                    const unit = data.units?.[context.dataIndex];
                    const value = context.chart.options.indexAxis === 'y' ? context.parsed.x : context.parsed.y;

                    return `${context.dataset.label}: ${formatValue(value, data.format, unit)}`;
                },
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: '#e5e5e5' },
            ticks: { color: '#737373', maxTicksLimit: 7 },
        },
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: '#e5e5e5' },
            ticks: { color: '#737373' },
        },
    },
});

const categoryConfig = (data) => {
    const options = baseOptions(data);
    options.indexAxis = 'y';
    options.interaction.mode = 'nearest';
    options.scales.y.grid.display = false;
    options.scales.x.ticks.callback = (value) => numberFormatter.format(value);

    return {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.datasets[0].label,
                data: data.values,
                backgroundColor: data.values.map((_, index) => palette[index % palette.length]),
                borderRadius: 3,
                borderSkipped: false,
                barPercentage: 0.72,
            }],
        },
        options,
    };
};

const lineConfig = (data, key) => {
    const options = baseOptions(data);
    const lineStyles = {
        stockingTrend: { color: '#171717', dash: [] },
        mortalityTrend: { color: '#525252', dash: [7, 4] },
        feedingCostTrend: { color: '#737373', dash: [] },
    };
    const style = lineStyles[key] || lineStyles.stockingTrend;

    options.scales.x.grid.display = false;
    options.scales.x.ticks.maxRotation = 0;
    options.scales.x.ticks.autoSkip = true;
    options.scales.y.ticks.callback = data.format === 'currency'
        ? compactCurrency
        : (value) => numberFormatter.format(value);

    return {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.datasets[0].label,
                data: data.values,
                borderColor: style.color,
                backgroundColor: 'rgba(23, 23, 23, 0.06)',
                borderDash: style.dash,
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: style.color,
                pointRadius: data.labels.length > 15 ? 0 : 2.5,
                pointHoverRadius: 4,
                tension: 0.25,
                fill: true,
            }],
        },
        options,
    };
};

const activityConfig = (data) => {
    const options = baseOptions(data);
    const dashes = [[], [7, 4], [2, 3], [10, 3, 2, 3]];

    options.plugins.legend = {
        display: true,
        position: 'bottom',
        align: 'start',
        labels: { color: '#525252', boxWidth: 22, boxHeight: 2, padding: 16 },
    };
    options.scales.x.grid.display = false;
    options.scales.x.ticks.maxRotation = 0;
    options.scales.y.ticks.precision = 0;

    return {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map((dataset, index) => ({
                label: dataset.label,
                data: dataset.values,
                borderColor: palette[index],
                backgroundColor: palette[index],
                borderDash: dashes[index],
                borderWidth: 2,
                pointRadius: data.labels.length > 15 ? 0 : 2,
                pointHoverRadius: 4,
                tension: 0.2,
            })),
        },
        options,
    };
};

export const initializeDashboardCharts = () => {
    const charts = parseChartData();

    if (!charts) {
        return;
    }

    document.querySelectorAll('[data-dashboard-chart]').forEach((canvas) => {
        const key = canvas.dataset.dashboardChart;
        const data = charts[key];

        if (!data?.hasData) {
            return;
        }

        const config = key === 'transactionActivity'
            ? activityConfig(data)
            : (['stockByTambak', 'stockByCommodity'].includes(key)
                ? categoryConfig(data)
                : lineConfig(data, key));

        new Chart(canvas, config);
    });
};
