(function () {
  async function loadText(url) {
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`Failed to load ${url} (${response.status})`);
    }

    return await response.text();
  }

  async function loadJson(url) {
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`Failed to load ${url} (${response.status})`);
    }

    return await response.json();
  }

  function parseCSV(text) {
    const lines = text
      .replace(/\r\n?/g, '\n')
      .split('\n')
      .map(line => line.trim())
      .filter(Boolean);

    const headers = lines.shift().split(',').map(h => h.trim().toLowerCase());

    return lines.map(line => {
      const cols = line.split(',');
      const obj = {};

      headers.forEach((h, i) => {
        obj[h] = (cols[i] ?? '').trim();
      });

      const val = parseFloat(obj.fsi_base);

      return {
        'iso-a3': obj.iso3,
        name: obj.country,
        value: Number.isNaN(val) ? null : val,
        income: obj.income || 'N/A',
        region: obj.region || 'N/A'
      };
    });
  }

  window.AdvancedCharts.register('fsi_world_baseline', {
    render: async function (el, settings) {
      if (!settings.dataUrl) {
        throw new Error('fsi_world_baseline requires a dataUrl');
      }

      const payload = settings.payload || {};
      const topologyUrl = payload.topologyUrl || '/libraries/highcharts/mapdata/world.topo.json';

      const wrapper = el.closest('.advanced-chart-wrapper');
      const controlsEl = wrapper ? wrapper.querySelector('.js-advanced-chart-controls') : null;
      const statusEl = wrapper ? wrapper.querySelector('.js-advanced-chart-status') : null;

      const [topology, csvText] = await Promise.all([
        loadJson(topologyUrl),
        loadText(settings.dataUrl)
      ]);

      const data = parseCSV(csvText);

      const chart = Highcharts.mapChart(el.id, {
        chart: {
          map: topology
        },
        title: {
          text: payload.title || null
        },
        credits: { enabled: false },
        exporting: { enabled: false },
        mapNavigation: {
          enabled: true,
          buttonOptions: { verticalAlign: 'bottom' }
        },
        legend: {
          layout: 'vertical',
          align: 'left',
          verticalAlign: 'bottom',
          floating: true,
          backgroundColor: 'rgba(255,255,255,0.9)'
        },
        colorAxis: {
          min: 1,
          max: 10,
          stops: [
            [0.0, '#440154'],
            [0.25, '#3b528b'],
            [0.5, '#21918c'],
            [0.75, '#5ec962'],
            [1.0, '#fde725']
          ],
          nullColor: '#efefef'
        },
        tooltip: {
          useHTML: true,
          headerFormat: '',
          pointFormatter: function () {
            const v = typeof this.value === 'number' ? this.value.toFixed(2) : 'No data';
            return `<b>${this.name || 'Unknown'}</b><br/>
              FSI (baseline): <b>${v}</b><br/>
              Income: ${this.income || 'N/A'}<br/>
              Region: ${this.region || 'N/A'}`;
          }
        },
        series: [{
          name: payload.seriesName || 'Food Security Index (baseline)',
          mapData: topology,
          data,
          joinBy: ['iso-a3', 'iso-a3'],
          allAreas: true,
          nullColor: '#efefef',
          borderColor: '#cfcfcf',
          borderWidth: 0.6,
          states: {
            hover: {
              color: '#a4edba'
            }
          },
          dataLabels: {
            enabled: false,
            allowOverlap: false,
            formatter: function () {
              return typeof this.point.value === 'number' ? this.point.name : null;
            },
            style: {
              fontSize: '9px',
              textOutline: '1px contrast'
            }
          }
        }]
      });

      if (controlsEl) {
        controlsEl.innerHTML = `
          <label class="inline-flex items-center gap-2 text-gray-700 mb-2">
            <input type="checkbox" class="js-advanced-chart-labels-toggle border border-gray-300 rounded shadow-sm text-iiedpink-800 focus:border-iiedpink-300 focus:ring focus:ring-offset-0 focus:ring-iiedpink-200 focus:ring-opacity-50" aria-label="Show country labels" />
            Show country labels
          </label>
        `;

        const checkbox = controlsEl.querySelector('.js-advanced-chart-labels-toggle');

        if (checkbox) {
          checkbox.addEventListener('change', function () {
            chart.series[0].update({
              dataLabels: {
                enabled: this.checked
              }
            }, false);

            chart.redraw();
          });
        }
      }

      if (statusEl) {
        const colored = data.filter(d => d.value !== null).length;
        statusEl.textContent = `Loaded ${data.length} records; colored ${colored} countries. Countries without data are grey.`;
      }
    }
  });
})();
