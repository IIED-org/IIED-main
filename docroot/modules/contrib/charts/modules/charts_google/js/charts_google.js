/**
 * @file
 * JavaScript integration between Google and Drupal.
 */
(function (Drupal, once) {

  'use strict';

  Drupal.googleCharts = Drupal.googleCharts || {charts: []};

  /**
   * Behavior to initialize Google Charts.
   *
   * @type {{attach: Drupal.behaviors.chartsGooglecharts.attach}}
   */
  Drupal.behaviors.chartsGooglecharts = {
    attach: function (context) {
      // Load Google Charts API.
      google.charts.load('current', {packages: ['corechart', 'gauge', 'table']});

      // Re-draw charts if viewport size has been changed.
      window.addEventListener('resize', function () {
        Drupal.googleCharts.waitForFinalEvent(function () {
          // Re-draw Google Charts.
          Drupal.googleCharts.drawCharts();
        }, 200, 'reload-google-charts');
      });

      // Draw Google Charts.
      Drupal.googleCharts.drawCharts();
    }
  };

  /**
   * Helper function to draw Google Charts.
   */
  Drupal.googleCharts.drawCharts = function () {
    const contents = new Drupal.Charts.Contents();
    once('load-google-charts-item', '.charts-google').forEach(function (element) {
      if (element.dataset.hasOwnProperty('chart')) {
        const chartId = element.id;
        const dataAttributes = contents.getData(chartId);
        google.charts.setOnLoadCallback(Drupal.googleCharts.drawChart(chartId, dataAttributes['visualization'], dataAttributes['data'], dataAttributes['options']));
        if (element.nextElementSibling && element.nextElementSibling.hasAttribute('data-charts-debug-container')) {
          element.nextElementSibling.querySelector('code').innerText = JSON.stringify(dataAttributes, null, ' ');
        }
      }
    });
  };

  /**
   * Helper function to draw a Google Chart.
   *
   * @param {string} chartId - Chart Id.
   * @param {string} chartType - Chart Type.
   * @param {string} dataTable - Data.
   * @param {string} googleChartOptions - Options.
   *
   * @return {function} Draw Chart.
   */
  Drupal.googleCharts.drawChart = function (chartId, chartType, dataTable, googleChartOptions) {
    return function () {
      const data = google.visualization.arrayToDataTable(dataTable);
      const options = googleChartOptions;
      const googleChartTypeFormatted = chartType;

      let chart;
      switch (googleChartTypeFormatted) {
        case 'BarChart':
          chart = new google.visualization.BarChart(document.getElementById(chartId));
          break;
        case 'ColumnChart':
          chart = new google.visualization.ColumnChart(document.getElementById(chartId));
          break;
        case 'DonutChart':
        case 'PieChart':
          chart = new google.visualization.PieChart(document.getElementById(chartId));
          break;
        case 'ScatterChart':
          chart = new google.visualization.ScatterChart(document.getElementById(chartId));
          break;
        case 'BubbleChart':
          chart = new google.visualization.BubbleChart(document.getElementById(chartId));
          break;
        case 'AreaChart':
          chart = new google.visualization.AreaChart(document.getElementById(chartId));
          break;
        case 'LineChart':
        case 'SplineChart':
          chart = new google.visualization.LineChart(document.getElementById(chartId));
          break;
        case 'Gauge':
          chart = new google.visualization.Gauge(document.getElementById(chartId));
          break;
        case 'ComboChart':
          chart = new google.visualization.ComboChart(document.getElementById(chartId));
          break;
        case 'GeoChart':
          chart = new google.visualization.GeoChart(document.getElementById(chartId));
          break;
        case 'TableChart':
          chart = new google.visualization.Table(document.getElementById(chartId));
      }

      // Fix for https://www.drupal.org/project/charts/issues/2950654.
      // Would be interested in a different approach that allowed the default
      // colors to be applied first, rather than unsetting.
      if (options['colors'].length > 10) {
        for (const i in options) {
          if (i === 'colors') {
            delete options[i];
            break;
          }
        }
      }

      // Rewrite the colorAxis item to include the colors: key
      if (typeof options['colorAxis'] != 'undefined') {
        const colors = options['colorAxis'];
        const num_colors = colors.length;
        options['colorAxis'] = colors.splice(num_colors);
        options['colorAxis'] = {colors: colors};
      }
      chart.draw(data, options);
    };
  };

  /**
   * Helper function to run a callback function once when triggering an event
   * multiple times.
   *
   * Example usage:
   * @code
   *  window.addEventListener('resize', function () {
   *    Drupal.googleCharts.waitForFinalEvent(function(){
   *      alert('Resize...');
   *    }, 500, "some unique string");
   *  });
   * @endcode
   */
  Drupal.googleCharts.waitForFinalEvent = (function () {
    let timers = {};
    return function (callback, ms, uniqueId) {
      if (!uniqueId) {
        uniqueId = "Don't call this twice without a uniqueId";
      }
      if (timers[uniqueId]) {
        clearTimeout(timers[uniqueId]);
      }
      timers[uniqueId] = setTimeout(callback, ms);
    };
  })();
}(Drupal, once));
