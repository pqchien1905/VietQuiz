/**
 * VietQuiz Charts — Chart.js wrapper with dark mode support
 * Ported from Recharts (React) to Chart.js
 */

/**
 * Ensure Chart.js is loaded, loading it dynamically if not
 * @returns {Promise<void>}
 */
export async function ensureChartJs() {
  if (window.Chart) return;
  await new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });
}

/**
 * Detect current color mode
 * @returns {'dark'|'light'}
 */
export function getColorMode() {
  return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

/**
 * Get adaptive colors for charts
 */
function getChartColors(mode) {
  return {
    text: mode === 'dark' ? '#f8fafc' : '#111111',
    textMuted: mode === 'dark' ? '#9ca3af' : '#6b7280',
    grid: mode === 'dark' ? '#374151' : '#e5e7eb',
    tooltip: mode === 'dark' ? '#1e2235' : '#ffffff',
    primary: mode === 'dark' ? '#60a5fa' : '#3b82f6',
    // Tailwind-ish palette
    palette: [
      mode === 'dark' ? '#60a5fa' : '#3b82f6', // blue
      mode === 'dark' ? '#fb923c' : '#f97316', // orange
      mode === 'dark' ? '#4ade80' : '#22c55e', // green
      mode === 'dark' ? '#fbbf24' : '#f59e0b', // yellow
      mode === 'dark' ? '#22d3ee' : '#06b6d4', // cyan
      mode === 'dark' ? '#f87171' : '#ef4444', // red
      mode === 'dark' ? '#c084fc' : '#a855f7', // purple
      mode === 'dark' ? '#e879f9' : '#ec4899', // pink
    ],
  };
}

/**
 * Default Chart.js options with dark mode support
 * @param {object} overrides
 */
export function getDefaultOptions(overrides = {}) {
  const mode = getColorMode();
  const c = getChartColors(mode);
  return {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: true,
        labels: {
          color: c.text,
          font: { family: "'Be Vietnam Pro', sans-serif", size: 12 },
          usePointStyle: true,
          pointStyleWidth: 10,
          padding: 16,
        },
      },
      tooltip: {
        backgroundColor: c.tooltip,
        titleColor: c.text,
        bodyColor: c.textMuted,
        borderColor: c.grid,
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        titleFont: { family: "'Be Vietnam Pro', sans-serif", weight: 'bold', size: 13 },
        bodyFont: { family: "'Be Vietnam Pro', sans-serif", size: 12 },
      },
    },
    scales: {
      x: {
        ticks: { color: c.textMuted, font: { family: "'Be Vietnam Pro', sans-serif", size: 11 } },
        grid: { color: c.grid, drawBorder: false },
        border: { display: false },
      },
      y: {
        ticks: { color: c.textMuted, font: { family: "'Be Vietnam Pro', sans-serif", size: 11 } },
        grid: { color: c.grid, drawBorder: false },
        border: { display: false },
      },
    },
    ...overrides,
  };
}

/**
 * Create a Line chart
 * @param {string} canvasId - ID of the <canvas> element
 * @param {object} config - { labels, datasets: [{ label, data, color? }] }
 * @param {object} options - Chart options override
 * @returns {Chart}
 */
export function createLineChart(canvasId, config, options = {}) {
  const mode = getColorMode();
  const c = getChartColors(mode);
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;
  const datasets = config.datasets.map((ds, i) => ({
    label: ds.label,
    data: ds.data,
    borderColor: ds.color || c.palette[i % c.palette.length],
    backgroundColor: (ds.color || c.palette[i % c.palette.length]) + '20',
    fill: ds.fill ?? false,
    tension: ds.tension ?? 0.4,
    pointRadius: ds.pointRadius ?? 3,
    pointHoverRadius: ds.pointHoverRadius ?? 6,
    borderWidth: ds.borderWidth ?? 2,
  }));
  return new window.Chart(ctx, {
    type: 'line',
    data: { labels: config.labels, datasets },
    options: getDefaultOptions(options),
  });
}

/**
 * Create a Bar chart
 * @param {string} canvasId
 * @param {object} config - { labels, datasets }
 * @param {object} options
 * @returns {Chart}
 */
export function createBarChart(canvasId, config, options = {}) {
  const mode = getColorMode();
  const c = getChartColors(mode);
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;
  const datasets = config.datasets.map((ds, i) => ({
    label: ds.label,
    data: ds.data,
    backgroundColor: (ds.color || c.palette[i % c.palette.length]) + 'cc',
    borderColor: ds.color || c.palette[i % c.palette.length],
    borderWidth: 1,
    borderRadius: 4,
    borderSkipped: false,
  }));
  return new window.Chart(ctx, {
    type: 'bar',
    data: { labels: config.labels, datasets },
    options: getDefaultOptions(options),
  });
}

/**
 * Create a Doughnut / Pie chart
 * @param {string} canvasId
 * @param {object} config - { labels, data, colors? }
 * @param {object} options
 * @returns {Chart}
 */
export function createDoughnutChart(canvasId, config, options = {}) {
  const mode = getColorMode();
  const c = getChartColors(mode);
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;
  const colors = config.colors || c.palette;
  const defaultOpts = {
    responsive: true,
    maintainAspectRatio: true,
    cutout: '65%',
    plugins: {
      legend: {
        display: true,
        position: 'bottom',
        labels: {
          color: c.text,
          font: { family: "'Be Vietnam Pro', sans-serif", size: 12 },
          padding: 12,
          usePointStyle: true,
        },
      },
      tooltip: {
        backgroundColor: c.tooltip,
        titleColor: c.text,
        bodyColor: c.textMuted,
        borderColor: c.grid,
        borderWidth: 1,
        cornerRadius: 8,
      },
    },
  };
  return new window.Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: config.labels,
      datasets: [{
        data: config.data,
        backgroundColor: config.labels.map((_, i) => colors[i % colors.length] + 'cc'),
        borderColor: config.labels.map((_, i) => colors[i % colors.length]),
        borderWidth: 1,
        hoverOffset: 6,
      }],
    },
    options: { ...defaultOpts, ...options },
  });
}

/**
 * Create a Horizontal Bar chart
 * @param {string} canvasId
 * @param {object} config - { labels, datasets }
 * @param {object} options
 * @returns {Chart}
 */
export function createHorizontalBarChart(canvasId, config, options = {}) {
  const mode = getColorMode();
  const c = getChartColors(mode);
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;
  const datasets = config.datasets.map((ds, i) => ({
    label: ds.label,
    data: ds.data,
    backgroundColor: (ds.color || c.palette[i % c.palette.length]) + 'cc',
    borderColor: ds.color || c.palette[i % c.palette.length],
    borderWidth: 1,
    borderRadius: 4,
    borderSkipped: false,
  }));
  return new window.Chart(ctx, {
    type: 'bar',
    data: { labels: config.labels, datasets },
    options: getDefaultOptions({
      indexAxis: 'y',
      scales: {
        x: {
          ticks: { color: c.textMuted, font: { family: "'Be Vietnam Pro', sans-serif", size: 11 } },
          grid: { color: c.grid, drawBorder: false },
          border: { display: false },
        },
        y: {
          ticks: { color: c.textMuted, font: { family: "'Be Vietnam Pro', sans-serif", size: 12 } },
          grid: { display: false },
          border: { display: false },
        },
      },
      ...options,
    }),
  });
}

/**
 * Destroy an existing chart (to avoid duplicates on re-render)
 * @param {string} canvasId
 */
export function destroyChart(canvasId) {
  const existing = window._vqCharts?.[canvasId];
  if (existing) { existing.destroy(); delete window._vqCharts[canvasId]; }
}

/**
 * Create a chart, destroying any existing one first
 * @param {string} type - 'line'|'bar'|'doughnut'|'horizontalBar'
 * @param {string} canvasId
 * @param {object} config
 * @param {object} options
 * @returns {Chart|null}
 */
export function createChart(type, canvasId, config, options = {}) {
  if (!window._vqCharts) window._vqCharts = {};
  destroyChart(canvasId);
  let chart = null;
  switch (type) {
    case 'line':    chart = createLineChart(canvasId, config, options); break;
    case 'bar':     chart = createBarChart(canvasId, config, options); break;
    case 'doughnut': chart = createDoughnutChart(canvasId, config, options); break;
    case 'horizontalBar': chart = createHorizontalBarChart(canvasId, config, options); break;
    default: console.warn(`Unknown chart type: ${type}`); return null;
  }
  if (chart) window._vqCharts[canvasId] = chart;
  return chart;
}

/**
 * Re-render all active charts (call after theme changes)
 */
export function reRenderCharts() {
  if (!window._vqCharts) return;
  Object.entries(window._vqCharts).forEach(([id, chart]) => {
    chart.destroy();
    delete window._vqCharts[id];
  });
}

export default { ensureChartJs, getColorMode, getChartColors, getDefaultOptions, createLineChart, createBarChart, createDoughnutChart, createHorizontalBarChart, destroyChart, createChart, reRenderCharts };
