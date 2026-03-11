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

      function toNum(v) {
        const n = parseFloat(v);
        return Number.isNaN(n) ? null : n;
      }

      return {
        iso3: obj.iso3 ? obj.iso3.toUpperCase() : '',
        country: obj.country,
        region: obj.region,
        income: obj.income,
        fsi_base: toNum(obj.fsi_base),
        fsi_15: toNum(obj.fsi_15),
        fsi_20: toNum(obj.fsi_20),
        fsi_40: toNum(obj.fsi_40)
      };
    });
  }

  window.AdvancedCharts.register('fsi_world_scenarios', {
    render: async function (el, settings) {
      if (!settings.dataUrl) {
        throw new Error('fsi_world_scenarios requires a dataUrl');
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

      const rows = parseCSV(csvText);

      const scenarios = {
        fsi_base: { label: 'Baseline', field: 'fsi_base' },
        fsi_15: { label: '1.5°C', field: 'fsi_15' },
        fsi_20: { label: '2.0°C', field: 'fsi_20' },
        fsi_40: { label: '4.0°C', field: 'fsi_40' }
      };

      let activeScenario = payload.defaultScenario || 'fsi_15';

      function buildData(scenarioKey) {
        return rows.map(r => ({
          'iso-a3': r.iso3,
          name: r.country,
          value: r[scenarioKey],
          fsi_base: r.fsi_base,
          fsi_15: r.fsi_15,
          fsi_20: r.fsi_20,
          fsi_40: r.fsi_40,
          income: r.income,
          region: r.region
        }));
      }

      function updateStatus() {
        if (!statusEl) {
          return;
        }

        const colored = buildData(activeScenario).filter(d => d.value !== null).length;
        statusEl.textContent = `Showing: ${scenarios[activeScenario].label} – ${colored} countries with data.`;
      }

      if (controlsEl) {
        controlsEl.innerHTML = `
          <div class="inline-flex rounded-md shadow-sm mb-3" role="group" aria-label="Scenario selector">
            <button type="button" data-scenario="fsi_base" class="js-scenario-btn relative inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-l-md">
              Base
            </button>
            <button type="button" data-scenario="fsi_15" class="js-scenario-btn relative -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
              1.5°C
            </button>
            <button type="button" data-scenario="fsi_20" class="js-scenario-btn relative -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
              2.0°C
            </button>
            <button type="button" data-scenario="fsi_40" class="js-scenario-btn relative -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-r-md">
              4.0°C
            </button>
          </div>
        `;
      }

    //   function setActiveButton() {
    //     if (!controlsEl) {
    //       return;
    //     }

    //     controlsEl.querySelectorAll('.js-scenario-btn').forEach((btn) => {
    //       const isActive = btn.dataset.scenario === activeScenario;

    //       btn.classList.toggle('bg-iiedpink-800', isActive);
    //       btn.classList.toggle('text-white', isActive);
    //       btn.classList.toggle('border-iiedpink-800', isActive);

    //       btn.classList.toggle('bg-white', !isActive);
    //       btn.classList.toggle('text-gray-700', !isActive);
    //       btn.classList.toggle('border-gray-300', !isActive);
    //     });
    //   }

        function setActiveButton() {
            if (!controlsEl) {
                return;
            }

            controlsEl.querySelectorAll('.js-scenario-btn').forEach((btn) => {
                const isActive = btn.dataset.scenario === activeScenario;

                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                if (isActive) {
                btn.classList.add('bg-iiedpink-800', 'text-white', 'border-iiedpink-800');
                btn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50');
                }
                else {
                btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50');
                btn.classList.remove('bg-iiedpink-800', 'text-white', 'border-iiedpink-800');
                }
            });
        }


      const chart = Highcharts.mapChart(el.id, {
        chart: {
          map: topology,
          events: {
            render: function () {
              this.series[0].points
                .filter(p => /somaliland/i.test(p.name))
                .forEach(p => {
                  if (p.graphic) {
                    p.graphic.attr({ fill: '#efefef' });
                  }
                });
            }
          }
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
            const isBase = activeScenario === 'fsi_base';
            const scenarioLabel = scenarios[activeScenario].label;
            const v = typeof this.value === 'number' ? this.value.toFixed(2) : 'No data';

            if (isBase || typeof this.value !== 'number') {
              return `<b>${this.name}</b><br/>
                FSI (${scenarioLabel}): <b>${v}</b><br/>
                Income: ${this.income}<br/>
                Region: ${this.region}`;
            }

            const base = typeof this.fsi_base === 'number' ? this.fsi_base.toFixed(2) : '—';
            const diff = typeof this.fsi_base === 'number' ? this.value - this.fsi_base : null;
            const deltaStr = diff !== null ? (diff >= 0 ? '+' : '') + diff.toFixed(2) : '—';

            return `<b>${this.name}</b><br/>
              FSI (${scenarioLabel}): <b>${v}</b><br/>
              Change vs baseline: <b>${deltaStr}</b><br/>
              Baseline: ${base}<br/>
              Income: ${this.income}<br/>
              Region: ${this.region}`;
          }
        },
        series: [{
          name: payload.seriesName || 'Food Security Index',
          mapData: topology,
          data: buildData(activeScenario),
          joinBy: ['iso-a3', 'iso-a3'],
          allAreas: true,
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
        controlsEl.querySelectorAll('.js-scenario-btn').forEach((btn) => {
          btn.addEventListener('click', function () {
            activeScenario = this.dataset.scenario;
            setActiveButton();
            chart.series[0].setData(buildData(activeScenario), true);
            updateStatus();
          });
        });
      }

      setActiveButton();
      updateStatus();
    }
  });
})();
