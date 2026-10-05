/* Grayscale Chart.js dashboards — indigo inside `.pd` (my.href.nz).
 *
 * Canvases opt in via data attributes (server-rendered, no inline JS):
 *
 *   <canvas data-chart="clicks"
 *           data-chart-labels='["Mon", ...]'
 *           data-chart-values='[3, 0, ...]'></canvas>
 *
 * A clicks chart may render as bars (default) or a line:
 *
 *   <canvas data-chart="clicks" data-chart-type="line" …></canvas>
 *
 * A bar chart may override the tooltip unit (default "click"/"clicks"):
 *
 *   <canvas data-chart="clicks" data-chart-unit="link" …></canvas>
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
 * initCharts() (re)builds every chart under `root` — including `root`
 * itself when it is a chart canvas. Previous instances on the same
 * canvas are destroyed first, so rebuilds are idempotent.
 */

import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/* Light → dark ramps cycle per slice index (keep legend order in sync). */
const RAMP_LIGHT = ['#171717', '#404040', '#737373', '#a3a3a3', '#d4d4d4', '#e5e5e5'];
const RAMP_DARK = ['#fafafa', '#e5e5e5', '#a3a3a3', '#737373', '#525252', '#404040'];

/* Public dashboard (my.href.nz) palette: indigo family instead of grayscale. */
const RAMP_PD_LIGHT = ['#4f46e5', '#7c3aed', '#a855f7', '#c084fc', '#d8b4fe', '#e9d5ff'];
const RAMP_PD_DARK = ['#a5b4fc', '#818cf8', '#c4b5fd', '#6366f1', '#4f46e5', '#3730a3'];

const SOLID_PD_LIGHT = '#4f46e5';
const SOLID_PD_DARK = '#a5b4fc';

const pick = (dark, i, pd = false) =>
    (pd ? (dark ? RAMP_PD_DARK : RAMP_PD_LIGHT) : dark ? RAMP_DARK : RAMP_LIGHT)[i % RAMP_LIGHT.length];

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

function buildClicksChart(canvas, dark, pd = false) {
    const solid = pd ? (dark ? SOLID_PD_DARK : SOLID_PD_LIGHT) : dark ? '#fafafa' : '#171717';
    const soft = pd
        ? dark
            ? 'rgba(165,180,252,0.25)'
            : 'rgba(79,70,229,0.15)'
        : dark
          ? 'rgba(250,250,250,0.25)'
          : 'rgba(23,23,23,0.15)';
    const unit = canvas.dataset.chartUnit ?? 'click';
    const line = canvas.dataset.chartType === 'line';

    canvas._tlChart = new Chart(canvas, {
        type: line ? 'line' : 'bar',
        data: {
            labels: readJson(canvas, 'chartLabels'),
            datasets: [
                {
                    data: readJson(canvas, 'chartValues'),
                    ...(line
                        ? {
                            borderColor: solid,
                            backgroundColor: soft,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: solid,
                            pointBorderColor: solid,
                        }
                        : {
                            backgroundColor: (ctx) => (ctx.raw > 0 ? solid : soft),
                            borderRadius: 3,
                            borderSkipped: 'start',
                        }),
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
                        label: (ctx) => ` ${ctx.parsed.y} ${unit}${ctx.parsed.y === 1 ? '' : 's'}`,
                    },
                },
            },
            scales: baseScales(dark),
        },
    });
}

function buildBrowsersChart(canvas, dark, pd = false) {
    const values = readJson(canvas, 'chartValues');
    const colors = values.map((_, i) => pick(dark, i, pd));

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

    // The root itself may be a freshly morphed chart canvas (Livewire
    // fires morph.updated per element) — querySelectorAll only finds
    // descendants, so include it explicitly.
    const canvases = [...root.querySelectorAll('canvas[data-chart]')];
    if (root instanceof HTMLCanvasElement && root.hasAttribute('data-chart') && !canvases.includes(root)) {
        canvases.unshift(root);
    }

    canvases.forEach((canvas) => {
        if (canvas._tlChart) {
            canvas._tlChart.destroy();
            canvas._tlChart = null;
        }

        const dark = isDark();
        // Canvases under the public dashboard theme (.pd) paint indigo.
        const pd = canvas.closest('.pd') !== null;

        if (canvas.dataset.chart === 'clicks') buildClicksChart(canvas, dark, pd);
        if (canvas.dataset.chart === 'browsers') buildBrowsersChart(canvas, dark, pd);
    });
}

/* First paint. */
initCharts();

/* Livewire SPA navigations + DOM morphs (period switches, verification). */
document.addEventListener('livewire:navigated', () => initCharts());

/* Morphs fire per element, top-down: an ancestor's rebuild would read
 * not-yet-morphed child attributes (stale chart), while the canvas's
 * own event finds no descendants (missed rebuild) — the visible chart
 * lagged one interaction behind ("click twice"). Deferring to a
 * microtask rebuilds once, after the whole walk, with fresh data. */
let morphRebuildQueued = false;

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', () => {
        if (morphRebuildQueued) return;
        morphRebuildQueued = true;
        queueMicrotask(() => {
            morphRebuildQueued = false;
            initCharts();
        });
    });
});

/* Theme toggle repaints charts in the active palette. */
document.addEventListener('tl:theme-changed', () => initCharts());
