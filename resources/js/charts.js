/* Grayscale Chart.js dashboards.
 *
 * Canvases opt in via data attributes (server-rendered, no inline JS):
 *
 *   <canvas data-chart="clicks"
 *           data-chart-labels='["Mon", ...]'
 *           data-chart-values='[3, 0, ...]'></canvas>
 *
 *   <canvas data-chart="browsers"
 *           data-chart-labels='["Chrome", ...]'
 *           data-chart-values='[12, 3]'></canvas>
 *
 * A doughnut may declare a server-rendered HTML legend that keeps
 * labels in the markup (SEO, no-JS, tests):
 *
 *   <ul data-chart-legend="browsers-canvas-id">…
 *     <span data-swatch="0"></span> Chrome …
 *
 * initCharts() (re)builds every chart under `root`. Previous instances
 * on the same canvas are destroyed first, so it is safe to call after
 * Livewire morphs, SPA navigations, and theme switches.
 */

import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/* Light → dark ramps cycle per slice index (keep legend order in sync). */
const RAMP_LIGHT = ['#171717', '#404040', '#737373', '#a3a3a3', '#d4d4d4', '#e5e5e5'];
const RAMP_DARK = ['#fafafa', '#e5e5e5', '#a3a3a3', '#737373', '#525252', '#404040'];

const pick = (dark, i) => (dark ? RAMP_DARK : RAMP_LIGHT)[i % RAMP_LIGHT.length];

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function readJson(canvas, key) {
    try {
        return JSON.parse(canvas.dataset[key] ?? '[]');
    } catch {
        return [];
    }
}

function baseScales(dark) {
    const grid = dark ? '#262626' : '#e5e5e5';
    const tick = dark ? '#a3a3a3' : '#737373';

    return {
        x: {
            grid: { display: false },
            ticks: { color: tick, maxTicksLimit: 6, font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            grid: { color: grid },
            ticks: { color: tick, precision: 0, font: { size: 11 } },
        },
    };
}

function tooltipStyle(dark) {
    return {
        backgroundColor: dark ? '#fafafa' : '#171717',
        titleColor: dark ? '#171717' : '#fafafa',
        bodyColor: dark ? '#404040' : '#d4d4d4',
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
    };
}

function paintLegend(canvas, colors) {
    const legend = document.querySelector(`[data-chart-legend="${canvas.id}"]`);
    if (!legend) return;

    legend.querySelectorAll('[data-swatch]').forEach((swatch) => {
        const i = Number(swatch.dataset.swatch ?? 0);
        swatch.style.backgroundColor = colors[i % colors.length];
    });
}

function buildClicksChart(canvas, dark) {
    const solid = dark ? '#fafafa' : '#171717';
    const soft = dark ? 'rgba(250,250,250,0.25)' : 'rgba(23,23,23,0.15)';

    canvas._tlChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: readJson(canvas, 'chartLabels'),
            datasets: [
                {
                    data: readJson(canvas, 'chartValues'),
                    backgroundColor: (ctx) => (ctx.raw > 0 ? solid : soft),
                    borderRadius: 3,
                    borderSkipped: 'start',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipStyle(dark),
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.y} click${ctx.parsed.y === 1 ? '' : 's'}`,
                    },
                },
            },
            scales: baseScales(dark),
        },
    });
}

function buildBrowsersChart(canvas, dark) {
    const values = readJson(canvas, 'chartValues');
    const colors = values.map((_, i) => pick(dark, i));

    canvas._tlChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: readJson(canvas, 'chartLabels'),
            datasets: [
                {
                    data: values,
                    backgroundColor: colors,
                    borderColor: dark ? '#09090b' : '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipStyle(dark),
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const share = total > 0 ? Math.round((ctx.parsed / total) * 1000) / 10 : 0;
                            return ` ${ctx.label}: ${ctx.parsed} (${share}%)`;
                        },
                    },
                },
            },
        },
    });

    paintLegend(canvas, colors);
}

export function initCharts(root = document) {
    if (typeof Chart === 'undefined') return;

    root.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        if (canvas._tlChart) {
            canvas._tlChart.destroy();
            canvas._tlChart = null;
        }

        const dark = isDark();

        if (canvas.dataset.chart === 'clicks') buildClicksChart(canvas, dark);
        if (canvas.dataset.chart === 'browsers') buildBrowsersChart(canvas, dark);
    });
}

/* First paint. */
initCharts();

/* Livewire SPA navigations + DOM morphs (period switches, verification). */
document.addEventListener('livewire:navigated', () => initCharts());

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', ({ el }) => initCharts(el));
});

/* Theme toggle repaints charts in the active palette. */
document.addEventListener('tl:theme-changed', () => initCharts());
