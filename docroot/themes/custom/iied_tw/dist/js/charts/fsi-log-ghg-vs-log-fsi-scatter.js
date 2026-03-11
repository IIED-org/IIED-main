(function () {
  async function loadText(url) {
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(`Failed to load ${url} (${response.status})`);
    }

    return await response.text();
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
        country: obj.country || '',
        grouping: obj.grouping || '',
        log_fsi: toNum(obj.log_fsi),
        log_ghg: toNum(obj.log_ghg)
      };
    });
  }

  function normalizeCategory(value) {
    const s = (value || '').trim();

    if (s.includes('Developed')) return 'Developed';
    if (s.includes('Developing')) return 'Developing';
    if (s.includes('FCAS')) return 'FCAS';
    if (s.includes('LDC')) return 'LDC';
    if (s.includes('SIDS')) return 'SIDS';

    return null;
  }

  window.AdvancedCharts.register('fsi_log_ghg_vs_log_fsi_scatter', {
    render: async function (el, settings) {
      if (!settings.dataUrl) {
        throw new Error('fsi_log_ghg_vs_log_fsi_scatter requires a dataUrl');
      }

      const payload = settings.payload || {};
      const csvText = await loadText(settings.dataUrl);
      const rows = parseCSV(csvText);

      const colors = {
        Developed: '#f99d3c',
        Developing: '#4763a9',
        FCAS: '#f15f4b',
        LDC: '#00B3DF',
        SIDS: '#CE539E'
      };

      const grouped = {
        Developed: [],
        Developing: [],
        FCAS: [],
        LDC: [],
        SIDS: []
      };

      const allPoints = [];

      rows.forEach((row) => {
        if (row.log_fsi === null || row.log_ghg === null) {
          return;
        }

        const cats = row.grouping
          .split('-')
          .map(normalizeCategory)
          .filter(Boolean);

        const unique = [...new Set(cats)];

        const point = {
          x: row.log_ghg,
          y: row.log_fsi,
          name: row.country,
          cats: unique.join(', ')
        };

        allPoints.push([row.log_ghg, row.log_fsi]);

        unique.forEach((cat) => {
          if (grouped[cat]) {
            grouped[cat].push(point);
          }
        });
      });

      const series = Object.keys(grouped).map((key) => ({
        type: 'scatter',
        name: key,
        color: colors[key],
        data: grouped[key]
      }));

      const n = allPoints.length;
      const mx = allPoints.reduce((sum, point) => sum + point[0], 0) / n;
      const my = allPoints.reduce((sum, point) => sum + point[1], 0) / n;

      let num = 0;
      let den = 0;

      allPoints.forEach(([x, y]) => {
        num += (x - mx) * (y - my);
        den += (x - mx) * (x - mx);
      });

      const m = num / den;
      const b = my - m * mx;
      const minX = Math.min(...allPoints.map(point => point[0]));
      const maxX = Math.max(...allPoints.map(point => point[0]));

      series.push({
        type: 'line',
        name: payload.trendLabel || 'Overall trend',
        color: '#333333',
        data: [[minX, m * minX + b], [maxX, m * maxX + b]],
        marker: { enabled: false },
        lineWidth: 2,
        dashStyle: 'ShortDot',
        enableMouseTracking: false
      });

      Highcharts.chart(el.id, {
        chart: {
          zoomType: 'xy',
          spacing: [16, 16, 18, 16]
        },
        title: {
          text: payload.title || 'Correlation between FSI and Greenhouse Gas Emissions by Country Grouping',
          style: {
            fontSize: '16px',
            fontWeight: '600'
          }
        },
        subtitle: {
          text: payload.subtitle || 'X: Log GHG emission · Y: Log FSI. Association shown; no causal interpretation.',
          style: {
            fontSize: '14px'
          }
        },
        xAxis: {
          title: {
            text: payload.xAxisTitle || 'Log GHG emission'
          },
          gridLineWidth: 1,
          gridLineColor: '#eef1f4'
        },
        yAxis: {
          title: {
            text: payload.yAxisTitle || 'Log FSI'
          },
          gridLineWidth: 1,
          gridLineColor: '#eef1f4'
        },
        legend: {
          align: 'center',
          verticalAlign: 'bottom'
        },
        tooltip: {
          useHTML: true,
          pointFormatter: function () {
            if (this.series.type === 'line') {
              return '';
            }

            return `<b>${this.name}</b><br/>Log GHG: <b>${this.x.toFixed(3)}</b><br/>Log FSI: <b>${this.y.toFixed(3)}</b><br/>Group(s): <b>${this.cats}</b>`;
          }
        },
        plotOptions: {
          series: {
            states: {
              inactive: {
                opacity: 0.25
              }
            }
          },
          scatter: {
            marker: {
              radius: 4.2,
              symbol: 'circle'
            },
            states: {
              hover: {
                halo: {
                  size: 8,
                  opacity: 0.2
                }
              }
            },
            turboThreshold: 0
          }
        },
        accessibility: {
          enabled: true
        },
        credits: {
          enabled: false
        },
        series
      });
    }
  });
})();
