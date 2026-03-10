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
        iso3: obj.iso3 || '',
        country: obj.country || '',
        group: obj.group || '',
        climate_risk_base: toNum(obj.climate_risk_base),
        fsi_base: toNum(obj.fsi_base)
      };
    });
  }

  window.AdvancedCharts.register('fsi_climate_risk_scatter', {
    render: async function (el, settings) {
      if (!settings.dataUrl) {
        throw new Error('fsi_climate_risk_scatter requires a dataUrl');
      }

      const payload = settings.payload || {};
      const csvText = await loadText(settings.dataUrl);
      const rows = parseCSV(csvText);

      const palette = {
        FCAS: '#f15f4b',
        LDC: '#00B3DF',
        Developing: '#4763a9',
        Developed: '#f99d3c',
        SIDS: '#CE539E'
      };

      const cats = ['Developed', 'Developing', 'FCAS', 'LDC', 'SIDS'];

      const series = cats.map(cat => ({
        type: 'scatter',
        name: cat,
        color: palette[cat],
        data: rows
          .filter(r => r.group === cat && r.climate_risk_base !== null && r.fsi_base !== null)
          .map(r => ({
            x: r.climate_risk_base,
            y: r.fsi_base,
            name: r.country,
            iso3: r.iso3
          }))
      }));

      const all = rows
        .filter(r => r.climate_risk_base !== null && r.fsi_base !== null)
        .map(r => [r.climate_risk_base, r.fsi_base]);

      const n = all.length;
      const meanX = all.reduce((s, p) => s + p[0], 0) / n;
      const meanY = all.reduce((s, p) => s + p[1], 0) / n;

      let num = 0;
      let den = 0;

      all.forEach(([x, y]) => {
        num += (x - meanX) * (y - meanY);
        den += (x - meanX) * (x - meanX);
      });

      const m = num / den;
      const b = meanY - m * meanX;
      const xMin = Math.min(...all.map(p => p[0]));
      const xMax = Math.max(...all.map(p => p[0]));

      series.push({
        type: 'line',
        name: payload.trendLabel || 'Trend (all countries)',
        data: [[xMin, m * xMin + b], [xMax, m * xMax + b]],
        color: '#333333',
        lineWidth: 2,
        marker: { enabled: false },
        enableMouseTracking: false,
        dashStyle: 'ShortDot'
      });

      Highcharts.chart(el.id, {
        chart: {
          zoomType: 'xy',
          spacing: [16, 16, 16, 16]
        },
        title: {
          text: payload.title || 'Climate Risk vs Food Security (Base)',
          style: {
            fontSize: '16px',
            fontWeight: '600'
          }
        },
        subtitle: {
          text: payload.subtitle || 'Higher climate risk is generally associated with lower food security',
          style: {
            fontSize: '14px'
          }
        },
        xAxis: {
          title: {
            text: payload.xAxisTitle || 'Climate risk index (Base)'
          },
          gridLineColor: '#eef1f4',
          gridLineWidth: 1,
          min: 0.8,
          max: 9.1
        },
        yAxis: {
          title: {
            text: payload.yAxisTitle || 'Food Security Index (Base)'
          },
          gridLineColor: '#eef1f4',
          min: 1,
          max: 9.5
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

            return `<b>${this.name}</b> (${this.iso3})<br/>Climate risk: <b>${this.x.toFixed(2)}</b><br/>Food security: <b>${this.y.toFixed(2)}</b><br/>Group: <b>${this.series.name}</b>`;
          }
        },
        plotOptions: {
          scatter: {
            marker: {
              radius: 4.2,
              symbol: 'circle'
            },
            states: {
              hover: {
                enabled: true,
                halo: {
                  size: 7,
                  opacity: 0.2
                }
              }
            },
            turboThreshold: 0
          },
          series: {
            states: {
              inactive: {
                opacity: 0.25
              }
            }
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
