/* Chart.js visualisations across ternis.link, dashboards, and admin.
 *
 * Emerald inside `.nd` (public dashboard), indigo inside `.pd` (legacy
 * public dashboard), grayscale by default, semantic colour accents
 * (`amber`, `blue`, `red`, `emerald`, `purple`, `admin`) via
 * `data-chart-color` / `data-chart-palette`, and per-domain distinct
 * brand hex colours via `colorLight` / `colorDark` or
 * `data-chart-colors-light` / `data-chart-colors-dark`.
 *
 * Supported `data-chart` modes:
 *   - "clicks"         : single-series bar or line area chart (supports per-bar domain colours)
 *   - "multi"          : multi-series line ("Graph") or stacked/grouped bar ("Bar Graph") chart
 *   - "browsers"       : doughnut chart (+ optional `[data-chart-legend]`)
 *   - "doughnut"       : alias of browsers with palette support
 *   - "horizontal-bar" : horizontal ranked bar chart
 *
 * Mode switcher buttons inside `[data-chart-card]`:
 *   <button type="button" data-chart-switch="line">Graph</button>
 *   <button type="button" data-chart-switch="bar">Bar Graph</button>
 */

import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/* Light -> dark ramps cycle per slice index (keep legend order in sync). */
const RAMP_LIGHT = ['#171717', '#404040', '#737373', '#a3a3a3', '#d4d4d4', '#e5e5e5'];
const RAMP_DARK = ['#fafafa', '#e5e5e5', '#a3a3a3', '#737373', '#525252', '#404040'];

/* Legacy public dashboard palette: indigo family instead of grayscale. */
const RAMP_PD_LIGHT = ['#4f46e5', '#7c3aed', '#a855f7', '#c084fc', '#d8b4fe', '#e9d5ff'];
const RAMP_PD_DARK = ['#a5b4fc', '#818cf8', '#c4b5fd', '#6366f1', '#4f46e5', '#3730a3'];

const SOLID_PD_LIGHT = '#4f46e5';
const SOLID_PD_DARK = '#a5b4fc';

/* Public dashboard palette: emerald family instead of grayscale. */
const RAMP_ND_LIGHT = ['#047857', '#059669', '#10b981', '#34d399', '#6ee7b7', '#a7f3d0'];
const RAMP_ND_DARK = ['#6ee7b7', '#34d399', '#10b981', '#059669', '#047857', '#065f46'];

const SOLID_ND_LIGHT = '#059669';
const SOLID_ND_DARK = '#34d399';

/* Admin categorical palette for doughnuts and ranked comparisons. */
const RAMP_ADMIN_LIGHT = ['#d97706', '#2563eb', '#059669', '#7c3aed', '#dc2626', '#0891b2'];
const RAMP_ADMIN_DARK = ['#fbbf24', '#60a5fa', '#34d399', '#c084fc', '#f87171', '#22d3ee'];

const PALETTES = {
    amber: {
        light: { solid: '#d97706', soft: 'rgba(217, 119, 6, 0.16)', fade: 'rgba(217, 119, 6, 0.0)' },
        dark: { solid: '#fbbf24', soft: 'rgba(251, 191, 36, 0.26)', fade: 'rgba(251, 191, 36, 0.0)' },
        rampLight: ['#b45309', '#d97706', '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a'],
        rampDark: ['#fcd34d', '#fbbf24', '#f59e0b', '#d97706', '#b45309', '#92400e'],
    },
    blue: {
        light: { solid: '#2563eb', soft: 'rgba(37, 99, 235, 0.16)', fade: 'rgba(37, 99, 235, 0.0)' },
        dark: { solid: '#60a5fa', soft: 'rgba(96, 165, 250, 0.26)', fade: 'rgba(96, 165, 250, 0.0)' },
        rampLight: ['#1d4ed8', '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe'],
        rampDark: ['#93c5fd', '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8', '#1e40af'],
    },
    red: {
        light: { solid: '#dc2626', soft: 'rgba(220, 38, 38, 0.16)', fade: 'rgba(220, 38, 38, 0.0)' },
        dark: { solid: '#f87171', soft: 'rgba(248, 113, 113, 0.26)', fade: 'rgba(248, 113, 113, 0.0)' },
        rampLight: ['#b91c1c', '#dc2626', '#ef4444', '#f87171', '#fca5a5', '#fecaca'],
        rampDark: ['#fca5a5', '#f87171', '#ef4444', '#dc2626', '#b91c1c', '#991b1b'],
    },
    emerald: {
        light: { solid: '#059669', soft: 'rgba(5, 150, 105, 0.16)', fade: 'rgba(5, 150, 105, 0.0)' },
        dark: { solid: '#34d399', soft: 'rgba(52, 211, 153, 0.26)', fade: 'rgba(52, 211, 153, 0.0)' },
        rampLight: RAMP_ND_LIGHT,
        rampDark: RAMP_ND_DARK,
    },
    purple: {
        light: { solid: '#7c3aed', soft: 'rgba(124, 58, 237, 0.16)', fade: 'rgba(124, 58, 237, 0.0)' },
        dark: { solid: '#c084fc', soft: 'rgba(192, 132, 252, 0.26)', fade: 'rgba(192, 132, 252, 0.0)' },
        rampLight: ['#6d28d9', '#7c3aed', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'],
        rampDark: ['#d8b4fe', '#c084fc', '#a855f7', '#9333ea', '#7e22ce', '#6b21a8'],
    },
};

const fmt = new Intl.NumberFormat();

function hexToRgba(hex, alpha) {
    const clean = String(hex).replace('#', '');
    if (clean.length !== 6) return `rgba(115, 115, 115, ${alpha})`;
    const r = parseInt(clean.slice(0, 2), 16);
    const g = parseInt(clean.slice(2, 4), 16);
    const b = parseInt(clean.slice(4, 6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function hexPair(hex, dark) {
    return {
        solid: hex,
        soft: hexToRgba(hex, dark ? 0.24 : 0.15),
        fade: hexToRgba(hex, 0),
    };
}

/* Theme of the surface a canvas sits on: 'nd' | 'pd' | 'default'. */
function themeOf(element) {
    if (element.closest('.nd')) return 'nd';
    if (element.closest('.pd')) return 'pd';
    return 'default';
}

function resolveColorPair(color, dark, theme = 'default') {
    if (color && String(color).startsWith('#')) {
        return hexPair(color, dark);
    }

    if (color && PALETTES[color]) {
        return dark ? PALETTES[color].dark : PALETTES[color].light;
    }

    if (theme === 'nd') {
        return dark
            ? { solid: SOLID_ND_DARK, soft: 'rgba(52, 211, 153, 0.25)', fade: 'rgba(52, 211, 153, 0.0)' }
            : { solid: SOLID_ND_LIGHT, soft: 'rgba(5, 150, 105, 0.15)', fade: 'rgba(5, 150, 105, 0.0)' };
    }

    if (theme === 'pd') {
        return dark
            ? { solid: SOLID_PD_DARK, soft: 'rgba(165, 180, 252, 0.25)', fade: 'rgba(165, 180, 252, 0.0)' }
            : { solid: SOLID_PD_LIGHT, soft: 'rgba(79, 70, 229, 0.15)', fade: 'rgba(79, 70, 229, 0.0)' };
    }

    return dark
        ? { solid: '#fafafa', soft: 'rgba(250, 250, 250, 0.22)', fade: 'rgba(250, 250, 250, 0.0)' }
        : { solid: '#171717', soft: 'rgba(23, 23, 23, 0.14)', fade: 'rgba(23, 23, 23, 0.0)' };
}

const pick = (dark, i, theme = 'default', palette = '') => {
    if (palette === 'admin') {
        return (dark ? RAMP_ADMIN_DARK : RAMP_ADMIN_LIGHT)[i % RAMP_ADMIN_LIGHT.length];
    }
    if (palette && PALETTES[palette]) {
        const ramp = dark ? PALETTES[palette].rampDark : PALETTES[palette].rampLight;
        return ramp[i % ramp.length];
    }
    if (theme === 'nd') return (dark ? RAMP_ND_DARK : RAMP_ND_LIGHT)[i % RAMP_ND_LIGHT.length];
    if (theme === 'pd') return (dark ? RAMP_PD_DARK : RAMP_PD_LIGHT)[i % RAMP_PD_LIGHT.length];
    return (dark ? RAMP_DARK : RAMP_LIGHT)[i % RAMP_LIGHT.length];
};

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

function areaGradient(context, pair) {
    const { chart } = context;
    const { ctx, chartArea } = chart;
    if (!chartArea) return pair.soft;

    const grad = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    grad.addColorStop(0, pair.soft);
    grad.addColorStop(1, pair.fade);
    return grad;
}

function baseScales(dark, maxTicks = 8) {
    const grid = dark ? '#262626' : '#e5e5e5';
    const tick = dark ? '#a3a3a3' : '#737373';

    return {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: tick, maxTicksLimit: maxTicks, font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: grid },
            ticks: {
                color: tick,
                precision: 0,
                font: { size: 11 },
                callback: (val) => (typeof val === 'number' ? fmt.format(val) : val),
            },
        },
    };
}

function tooltipStyle(dark, displayColors = false) {
    return {
        backgroundColor: dark ? '#fafafa' : '#171717',
        titleColor: dark ? '#171717' : '#fafafa',
        bodyColor: dark ? '#404040' : '#d4d4d4',
        footerColor: dark ? '#171717' : '#fafafa',
        padding: 10,
        cornerRadius: 8,
        displayColors,
    };
}

function paintLegend(canvas, colors) {
    if (!canvas.id) return;
    const legend = document.querySelector(`[data-chart-legend="${canvas.id}"]`);
    if (!legend) return;

    legend.querySelectorAll('[data-swatch]').forEach((swatch) => {
        const i = Number(swatch.dataset.swatch ?? 0);
        swatch.style.backgroundColor = colors[i % colors.length];
    });
}

function pluralUnit(value, unit, suffix = '', verb = '') {
    if (suffix) {
        return `${fmt.format(value)}${suffix}`;
    }
    const tail = verb ? ` ${verb}` : '';
    return `${fmt.format(value)} ${unit}${value === 1 ? '' : 's'}${tail}`;
}

function buildClicksChart(canvas, dark, theme = 'default') {
    const pair = resolveColorPair(canvas.dataset.chartColor, dark, theme);
    const unit = canvas.dataset.chartUnit ?? 'click';
    const verb = canvas.dataset.chartVerb ?? '';
    const suffix = canvas.dataset.chartSuffix ?? '';
    const line = canvas.dataset.chartType === 'line';
    const labels = readJson(canvas, 'chartLabels');
    const values = readJson(canvas, 'chartValues');
    const perItemColors = readJson(canvas, dark ? 'chartColorsDark' : 'chartColorsLight');
    const hasPerItemColors = Array.isArray(perItemColors) && perItemColors.length > 0;
    const maxTicks = Number(
        canvas.dataset.chartMaxTicks ?? (hasPerItemColors ? labels.length : labels.length === 24 ? 8 : 7),
    );

    canvas._tlChart = new Chart(canvas, {
        type: line ? 'line' : 'bar',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    ...(line
                        ? {
                              borderColor: pair.solid,
                              backgroundColor: (ctx) => areaGradient(ctx, pair),
                              fill: true,
                              tension: 0.35,
                              borderWidth: 2,
                              pointRadius: hasPerItemColors ? 5 : labels.length > 35 ? 0 : 2.5,
                              pointHoverRadius: hasPerItemColors ? 7 : 5,
                              pointBackgroundColor: hasPerItemColors ? perItemColors : pair.solid,
                              pointBorderColor: dark ? '#171717' : '#ffffff',
                              pointBorderWidth: 1.5,
                          }
                        : {
                              backgroundColor: hasPerItemColors
                                  ? (ctx) => {
                                        const c = perItemColors[ctx.dataIndex % perItemColors.length] ?? pair.solid;
                                        return ctx.raw > 0 ? c : hexToRgba(c, dark ? 0.3 : 0.22);
                                    }
                                  : (ctx) => (ctx.raw > 0 ? pair.solid : pair.soft),
                              borderRadius: 4,
                              borderSkipped: 'start',
                              maxBarThickness: 36,
                          }),
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipStyle(dark, hasPerItemColors),
                    callbacks: {
                        label: (ctx) => ` ${pluralUnit(ctx.parsed.y, unit, suffix, verb)}`,
                    },
                },
            },
            scales: baseScales(dark, maxTicks),
        },
    });
}

function buildMultiChart(canvas, dark, theme = 'default') {
    const labels = readJson(canvas, 'chartLabels');
    const rawDatasets = readJson(canvas, 'chartDatasets');
    const line = (canvas.dataset.chartType ?? 'line') === 'line';
    const stacked = canvas.dataset.chartStacked === 'true';
    const maxTicks = Number(canvas.dataset.chartMaxTicks ?? 8);
    const tickColor = dark ? '#a3a3a3' : '#737373';
    const fallbackUnit = canvas.dataset.chartUnit ?? 'event';
    const fallbackVerb = canvas.dataset.chartVerb ?? '';

    const datasets = rawDatasets.map((ds, idx) => {
        const customHex = dark ? ds.colorDark || ds.colorLight : ds.colorLight || ds.colorDark;
        const pair = customHex
            ? hexPair(customHex, dark)
            : ds.color
              ? resolveColorPair(ds.color, dark, theme)
              : hexPair(pick(dark, idx, theme, 'admin'), dark);

        return {
            label: ds.label ?? `Series ${idx + 1}`,
            data: Array.isArray(ds.values) ? ds.values : [],
            _unit: ds.unit ?? fallbackUnit,
            _verb: ds.verb ?? fallbackVerb,
            ...(line
                ? {
                      borderColor: pair.solid,
                      backgroundColor: (ctx) => areaGradient(ctx, pair),
                      fill: ds.fill !== false && (rawDatasets.length <= 4 || idx === 0),
                      tension: 0.35,
                      borderWidth: 2.25,
                      pointRadius: labels.length > 35 ? 0 : 2.5,
                      pointHoverRadius: 5,
                      pointBackgroundColor: pair.solid,
                      pointBorderColor: dark ? '#171717' : '#ffffff',
                      pointBorderWidth: 1.5,
                  }
                : {
                      backgroundColor: pair.solid,
                      borderRadius: stacked ? 2 : 4,
                      maxBarThickness: 28,
                  }),
        };
    });

    const scales = baseScales(dark, maxTicks);
    if (stacked && !line) {
        scales.x.stacked = true;
        scales.y.stacked = true;
    }

    const daySumAt = (chart, dataIndex) =>
        chart.data.datasets.reduce((acc, ds) => acc + Number(ds.data?.[dataIndex] ?? 0), 0);

    canvas._tlChart = new Chart(canvas, {
        type: line ? 'line' : 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: datasets.length >= 1 && (datasets.length > 1 || Boolean(canvas.dataset.chartShowLegend)),
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: tickColor,
                        boxWidth: 10,
                        boxHeight: 10,
                        useBorderRadius: true,
                        borderRadius: 3,
                        font: { size: 11, weight: '600' },
                    },
                },
                tooltip: {
                    ...tooltipStyle(dark, true),
                    boxPadding: 4,
                    filter: (item, index, items, data) => {
                        if (data.datasets.length <= 1) return true;
                        const sum = data.datasets.reduce(
                            (acc, ds) => acc + Number(ds.data?.[item.dataIndex] ?? 0),
                            0,
                        );
                        if (sum === 0) return index === 0;
                        return Number(item.parsed?.y ?? 0) > 0;
                    },
                    callbacks: {
                        label: (ctx) => {
                            const unit = ctx.dataset._unit ?? fallbackUnit;
                            const verb = ctx.dataset._verb ?? fallbackVerb;
                            if (ctx.chart.data.datasets.length > 1 && daySumAt(ctx.chart, ctx.dataIndex) === 0) {
                                return ` Total: ${pluralUnit(0, unit, '', verb)}`;
                            }
                            return ` ${ctx.dataset.label}: ${pluralUnit(ctx.parsed.y, unit, '', verb)}`;
                        },
                        footer: (items) => {
                            if (!items.length) return '';
                            const first = items[0];
                            if (first.chart.data.datasets.length <= 1) return '';
                            const sum = daySumAt(first.chart, first.dataIndex);
                            if (sum === 0) return '';
                            const sameUnit = first.chart.data.datasets.every(
                                (ds) => ds._unit === first.dataset._unit,
                            );
                            if (!sameUnit) return '';
                            return `Total: ${pluralUnit(sum, first.dataset._unit ?? fallbackUnit, '', first.dataset._verb ?? fallbackVerb)}`;
                        },
                    },
                },
            },
            scales,
        },
    });
}

function buildBrowsersChart(canvas, dark, theme = 'default') {
    const values = readJson(canvas, 'chartValues');
    const palette = canvas.dataset.chartPalette ?? canvas.dataset.chartColor ?? '';
    const colors = values.map((_, i) => pick(dark, i, theme, palette));

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
                            return ` ${ctx.label}: ${fmt.format(ctx.parsed)} (${share}%)`;
                        },
                    },
                },
            },
        },
    });

    paintLegend(canvas, colors);
}

function buildHorizontalBarChart(canvas, dark, theme = 'default') {
    const labels = readJson(canvas, 'chartLabels');
    const values = readJson(canvas, 'chartValues');
    const unit = canvas.dataset.chartUnit ?? 'item';
    const verb = canvas.dataset.chartVerb ?? '';
    const suffix = canvas.dataset.chartSuffix ?? '';
    const palette = canvas.dataset.chartPalette ?? '';
    const pair = resolveColorPair(canvas.dataset.chartColor, dark, theme);
    const grid = dark ? '#262626' : '#e5e5e5';
    const tick = dark ? '#a3a3a3' : '#737373';

    const colors = palette
        ? values.map((_, i) => pick(dark, i, theme, palette))
        : values.map((v) => (v > 0 ? pair.solid : pair.soft));

    canvas._tlChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
                    maxBarThickness: 22,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipStyle(dark),
                    callbacks: {
                        label: (ctx) => ` ${pluralUnit(ctx.parsed.x, unit, suffix, verb)}`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: grid },
                    ticks: {
                        color: tick,
                        precision: suffix ? 1 : 0,
                        font: { size: 11 },
                        callback: (val) => (typeof val === 'number' ? `${fmt.format(val)}${suffix}` : val),
                    },
                },
                y: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: tick, font: { size: 11 } },
                },
            },
        },
    });
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
        const theme = themeOf(canvas);
        const kind = canvas.dataset.chart;

        if (kind === 'clicks') buildClicksChart(canvas, dark, theme);
        else if (kind === 'multi') buildMultiChart(canvas, dark, theme);
        else if (kind === 'browsers' || kind === 'doughnut') buildBrowsersChart(canvas, dark, theme);
        else if (kind === 'horizontal-bar') buildHorizontalBarChart(canvas, dark, theme);
    });
}

window.tlInitCharts = initCharts;

/* Interactive Graph (line) <-> Bar Graph (bar) switcher on any `[data-chart-card]`. */
document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-chart-switch]');
    if (!btn) return;

    const card = btn.closest('[data-chart-card]');
    if (!card) return;

    const nextType = btn.dataset.chartSwitch;
    if (!nextType) return;

    const canvases = card.querySelectorAll('canvas[data-chart]');
    if (!canvases.length) return;

    canvases.forEach((canvas) => {
        canvas.dataset.chartType = nextType;
        initCharts(canvas);
    });

    card.querySelectorAll('[data-chart-switch]').forEach((item) => {
        const active = item.dataset.chartSwitch === nextType;
        item.dataset.active = active ? 'true' : 'false';
        item.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
});

/* First paint. */
initCharts();

/* Livewire SPA navigations + DOM morphs (period switches, verification). */
document.addEventListener('livewire:navigated', () => initCharts());

/* Morphs fire per element, top-down: deferring to a microtask rebuilds
 * once, after the whole walk, with fresh data. */
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

/* Theme toggle or manual refresh repaints charts in the active palette. */
document.addEventListener('tl:theme-changed', () => initCharts());
document.addEventListener('tl:charts-refresh', (e) => initCharts(e.detail?.root ?? document));
