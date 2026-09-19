(function () {
  var chartInstances = [];

  function cssVar(name, fallback) {
    var value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
  }

  function palette() {
    return {
      primary: cssVar('--color-primary-600', '#3f5c34'),
      primaryLight: cssVar('--color-primary-300', '#9cb192'),
      accent: cssVar('--color-accent-500', '#b8992a'),
      green: cssVar('--color-primary-500', '#516b48'),
      purple: '#7c6fa8',
      red: '#dc2626',
      grid: document.documentElement.classList.contains('dark')
        ? 'rgba(148,163,184,0.15)'
        : 'rgba(148,163,184,0.25)',
      colors: [
        cssVar('--color-primary-600', '#3f5c34'),
        cssVar('--color-accent-500', '#b8992a'),
        cssVar('--color-primary-400', '#6f8764'),
        cssVar('--color-primary-300', '#9cb192'),
        '#7c6fa8',
        '#dc2626',
      ],
    };
  }

  function destroyCharts() {
    chartInstances.forEach(function (chart) {
      chart.destroy();
    });
    chartInstances = [];
  }

  function buildDataset(dataset, type, colors, index) {
    var base = {
      label: dataset.label || '',
      data: dataset.data || [],
    };

    if (type === 'line') {
      return Object.assign(base, {
        borderColor: colors.primary,
        backgroundColor: 'rgba(63, 92, 52, 0.12)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        pointBackgroundColor: colors.primary,
      });
    }

    if (type === 'doughnut' || type === 'pie') {
      return Object.assign(base, {
        backgroundColor: colors.colors,
        borderWidth: 0,
      });
    }

    return Object.assign(base, {
      backgroundColor: index % 2 === 0 ? colors.primary : colors.accent,
      borderRadius: 6,
      maxBarThickness: 42,
    });
  }

  function defaultOptions(type, colors, custom) {
    var options = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: type === 'doughnut' || type === 'pie' ? 'bottom' : 'top',
          display: type !== 'line',
        },
      },
    };

    if (type === 'doughnut' || type === 'pie') {
      options.cutout = '62%';
    }

    if (type === 'bar' || type === 'line') {
      options.scales = {
        x: { grid: { display: false } },
        y: {
          grid: { color: colors.grid },
          beginAtZero: true,
        },
      };
    }

    return Object.assign(options, custom || {});
  }

  window.initDashboardCharts = function (configs) {
    if (typeof Chart === 'undefined' || !Array.isArray(configs)) return;

    destroyCharts();

    var colors = palette();
    Chart.defaults.font.family = '"Plus Jakarta Sans", sans-serif';
    Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';

    configs.forEach(function (config, index) {
      var canvas = document.getElementById(config.id);
      if (!canvas) return;

      var datasets = (config.datasets || []).map(function (dataset, datasetIndex) {
        return buildDataset(dataset, config.type, colors, datasetIndex);
      });

      chartInstances.push(new Chart(canvas, {
        type: config.type,
        data: {
          labels: config.labels || [],
          datasets: datasets,
        },
        options: defaultOptions(config.type, colors, config.options || {}),
      }));
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    var dataEl = document.getElementById('dashboard-charts-data');
    if (!dataEl) return;

    try {
      var configs = JSON.parse(dataEl.textContent || '[]');
      window.initDashboardCharts(configs);
    } catch (e) {
      console.error('Dashboard charts failed to parse.', e);
    }
  });

  document.addEventListener('theme-changed', function () {
    var dataEl = document.getElementById('dashboard-charts-data');
    if (!dataEl) return;

    try {
      var configs = JSON.parse(dataEl.textContent || '[]');
      window.initDashboardCharts(configs);
    } catch (e) {
      console.error('Dashboard charts failed to refresh on theme change.', e);
    }
  });
})();
