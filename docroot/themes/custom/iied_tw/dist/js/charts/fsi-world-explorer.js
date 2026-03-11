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
        is_fcas: obj.is_fcas === '1',
        is_sids: obj.is_sids === '1',
        is_ldc: obj.is_ldc === '1',
        fsi_base: toNum(obj.fsi_base), fsi_15: toNum(obj.fsi_15), fsi_20: toNum(obj.fsi_20), fsi_40: toNum(obj.fsi_40),
        fa_base: toNum(obj.fa_base), fa_15: toNum(obj.fa_15), fa_20: toNum(obj.fa_20), fa_40: toNum(obj.fa_40),
        fx_base: toNum(obj.fx_base), fx_15: toNum(obj.fx_15), fx_20: toNum(obj.fx_20), fx_40: toNum(obj.fx_40),
        fu_base: toNum(obj.fu_base), fu_15: toNum(obj.fu_15), fu_20: toNum(obj.fu_20), fu_40: toNum(obj.fu_40),
        fs_base: toNum(obj.fs_base), fs_15: toNum(obj.fs_15), fs_20: toNum(obj.fs_20), fs_40: toNum(obj.fs_40)
      };
    });
  }

  window.AdvancedCharts.register('fsi_world_explorer', {
    render: async function (el, settings) {
      if (!settings.dataUrl) {
        throw new Error('fsi_world_explorer requires a dataUrl');
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

      const metricLabels = {
        fsi: 'Overall FSI',
        fa: 'Availability',
        fx: 'Access',
        fu: 'Utilisation',
        fs: 'Sustainability'
      };

      const scenarioLabels = {
        base: 'Baseline',
        '15': '1.5°C',
        '20': '2.0°C',
        '40': '4.0°C'
      };

      const groupLabels = {
        fcas: 'FCAS',
        ldc: 'LDC',
        sids: 'SIDS'
      };

      const state = {
        metric: payload.defaultMetric || 'fsi',
        scenario: payload.defaultScenario || 'base',
        groups: new Set(payload.defaultGroups || [])
      };

      let lastData = [];

      function getField(metric, scenario) {
        return `${metric}_${scenario}`;
      }

      function buildData() {
        const field = getField(state.metric, state.scenario);
        const baseField = getField(state.metric, 'base');
        const dimming = state.groups.size > 0;

        lastData = rows.map(r => {
          const inGroup = !dimming
            || (state.groups.has('fcas') && r.is_fcas)
            || (state.groups.has('ldc') && r.is_ldc)
            || (state.groups.has('sids') && r.is_sids);

          return {
            'iso-a3': r.iso3,
            name: r.country,
            value: r[field],
            base_value: r[baseField],
            income: r.income,
            region: r.region,
            is_fcas: r.is_fcas,
            is_ldc: r.is_ldc,
            is_sids: r.is_sids,
            _opacity: dimming && !inGroup ? 0.15 : 1
          };
        });

        return lastData;
      }

      function updateStatus() {
        if (!statusEl) {
          return;
        }

        const field = getField(state.metric, state.scenario);
        const colored = rows.filter(r => r[field] !== null).length;
        const mLabel = metricLabels[state.metric];
        const sLabel = scenarioLabels[state.scenario];
        const gText = state.groups.size > 0
          ? ` · Highlighting: ${[...state.groups].map(g => groupLabels[g]).join(', ')}`
          : '';

        statusEl.textContent = `${mLabel} – ${sLabel} · ${colored} countries with data${gText}`;
      }

      if (controlsEl) {
        controlsEl.innerHTML = `
            <div class="flex flex-col gap-4 mb-4">
                <div class="flex flex-col lg:flex-row lg:items-center gap-2 flex-wrap">
                <div class="text-sm text-gray-600 xl:w-24 shrink-0">Metric:</div>
                <div class="inline-flex rounded-md shadow-sm overflow-hidden mr-4" role="group" aria-label="Metric selector">
                    <button type="button" data-metric="fsi" class="js-metric-btn inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200 rounded-l-md">Overall FSI</button>
                    <button type="button" data-metric="fa" class="js-metric-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200">Availability</button>
                    <button type="button" data-metric="fx" class="js-metric-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200">Access</button>
                    <button type="button" data-metric="fu" class="js-metric-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200">Utilisation</button>
                    <button type="button" data-metric="fs" class="js-metric-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200 rounded-r-md">Sustainability</button>
                </div>

                <div class="text-sm text-gray-600 xl:w-24 shrink-0 xl:ml-4">Scenario:</div>
                <div class="inline-flex rounded-md shadow-sm overflow-hidden" role="group" aria-label="Scenario selector">
                    <button type="button" data-scenario="base" class="js-scenario-btn inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200 rounded-l-md">Baseline</button>
                    <button type="button" data-scenario="15" class="js-scenario-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200">1.5°C</button>
                    <button type="button" data-scenario="20" class="js-scenario-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200">2.0°C</button>
                    <button type="button" data-scenario="40" class="js-scenario-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200 rounded-r-md">4.0°C</button>
                </div>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-center gap-2 flex-wrap">
                <div class="text-sm text-gray-600 xl:w-24 shrink-0">Highlight groups:</div>
                <div class="inline-flex rounded-md shadow-sm overflow-hidden" role="group" aria-label="Highlight groups selector">
                    <button type="button" data-group="fcas" class="js-group-btn inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200 rounded-l-md">FCAS</button>
                    <button type="button" data-group="ldc" class="js-group-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200">LDC</button>
                    <button type="button" data-group="sids" class="js-group-btn -ml-px inline-flex items-center px-4 py-2 text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-iiedpink-200 rounded-r-md">SIDS</button>
                </div>
                </div>
            </div>
            `;

      }

      function setSingleSelectState(selector, activeValue) {
        if (!controlsEl) {
          return;
        }

        controlsEl.querySelectorAll(selector).forEach((btn) => {
          const key = btn.dataset.metric || btn.dataset.scenario;
          const isActive = key === activeValue;

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

      function setMultiSelectState() {
        if (!controlsEl) {
          return;
        }

        controlsEl.querySelectorAll('.js-group-btn').forEach((btn) => {
          const group = btn.dataset.group;
          const isActive = state.groups.has(group);

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
          min: 0,
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
            const isBase = state.scenario === 'base';
            const mLabel = metricLabels[state.metric];
            const sLabel = scenarioLabels[state.scenario];
            const v = typeof this.value === 'number' ? this.value.toFixed(2) : 'No data';

            const badgeHtml = [
              this.is_fcas ? '<span style="display:inline-block;font-size:10px;font-weight:600;padding:1px 5px;border-radius:3px;background:#5a4a8a;color:#fff;margin-left:3px;">FCAS</span>' : '',
              this.is_ldc ? '<span style="display:inline-block;font-size:10px;font-weight:600;padding:1px 5px;border-radius:3px;background:#5a4a8a;color:#fff;margin-left:3px;">LDC</span>' : '',
              this.is_sids ? '<span style="display:inline-block;font-size:10px;font-weight:600;padding:1px 5px;border-radius:3px;background:#5a4a8a;color:#fff;margin-left:3px;">SIDS</span>' : ''
            ].join('');

            const header = `<b>${this.name}</b>${badgeHtml}<br/>`;
            const meta = `Income: ${this.income}<br/>Region: ${this.region}`;

            if (isBase || typeof this.value !== 'number') {
              return `${header}${mLabel} (${sLabel}): <b>${v}</b><br/>${meta}`;
            }

            const base = typeof this.base_value === 'number' ? this.base_value.toFixed(2) : '—';
            const diff = typeof this.base_value === 'number' ? this.value - this.base_value : null;
            const delta = diff !== null ? (diff >= 0 ? '+' : '') + diff.toFixed(2) : '—';

            return `${header}
              ${mLabel} (${sLabel}): <b>${v}</b><br/>
              Change vs baseline: <b>${delta}</b><br/>
              Baseline: ${base}<br/>
              ${meta}`;
          }
        },
        series: [{
          name: payload.seriesName || 'Food Security Index',
          mapData: topology,
          data: buildData(),
          joinBy: ['iso-a3', 'iso-a3'],
          allAreas: true,
          borderColor: '#cfcfcf',
          borderWidth: 0.5,
          states: {
            hover: {
              enabled: true,
              brightness: 0.1,
              color: null
            }
          },
          dataLabels: {
            enabled: false
          }
        }]
      });

      function applyOpacity() {
        chart.series[0].points.forEach((p, i) => {
          if (!p.graphic) {
            return;
          }

          const dim = (lastData[i]?._opacity ?? 1) < 1;
          p.graphic.element.classList.toggle('hc-dimmed', dim);
        });
      }

      function updateMap() {
        chart.series[0].setData(buildData(), true, { duration: 0 });
        requestAnimationFrame(applyOpacity);
        updateStatus();
      }

      if (controlsEl) {
        controlsEl.querySelectorAll('.js-metric-btn').forEach((btn) => {
          btn.addEventListener('click', function () {
            state.metric = this.dataset.metric;
            setSingleSelectState('.js-metric-btn', state.metric);
            updateMap();
          });
        });

        controlsEl.querySelectorAll('.js-scenario-btn').forEach((btn) => {
          btn.addEventListener('click', function () {
            state.scenario = this.dataset.scenario;
            setSingleSelectState('.js-scenario-btn', state.scenario);
            updateMap();
          });
        });

        controlsEl.querySelectorAll('.js-group-btn').forEach((btn) => {
          btn.addEventListener('click', function () {
            const group = this.dataset.group;

            if (state.groups.has(group)) {
              state.groups.delete(group);
            }
            else {
              state.groups.add(group);
            }

            setMultiSelectState();
            updateMap();
          });
        });
      }

      setSingleSelectState('.js-metric-btn', state.metric);
      setSingleSelectState('.js-scenario-btn', state.scenario);
      setMultiSelectState();
      updateStatus();
      requestAnimationFrame(applyOpacity);
    }
  });
})();
