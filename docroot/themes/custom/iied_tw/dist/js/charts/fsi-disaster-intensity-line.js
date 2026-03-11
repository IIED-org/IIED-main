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

    const headers = lines.shift().split(',').map(h => h.trim());

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
        year: obj.year,
        FCAS: toNum(obj.FCAS),
        LDC: toNum(obj.LDC),
        Developing: toNum(obj.Developing),
        Developed: toNum(obj.Developed),
        SIDS: toNum(obj.SIDS)
      };
    });
  }

  window.AdvancedCharts.register('fsi_disaster_intensity_line', {
    render: async function (el, settings) {
      if (!settings.dataUrl) {
        throw new Error('fsi_disaster_intensity_line requires a dataUrl');
      }

      const payload = settings.payload || {};
      const csvText = await loadText(settings.dataUrl);
      const rows = parseCSV(csvText);

      const years = rows.map(row => String(row.year));

      const seriesData = [
        {
          name: 'FCAS',
          color: '#f15f4b',
          data: rows.map(row => row.FCAS)
        },
        {
          name: 'LDC',
          color: '#00B3DF',
          data: rows.map(row => row.LDC)
        },
        {
          name: 'Developing',
          color: '#4763a9',
          data: rows.map(row => row.Developing)
        },
        {
          name: 'Developed',
          color: '#f99d3c',
          data: rows.map(row => row.Developed)
        },
        {
          name: 'SIDS',
          color: '#CE539E',
          data: rows.map(row => row.SIDS)
        }
      ];

      Highcharts.chart(el.id, {
        chart: {
          type: 'spline',
          spacing: [16, 18, 16, 12],
          animation: true
        },
        title: {
          text: payload.title || 'Disaster Intensity by Development Classification (1990–2022)',
          style: {
            fontSize: '16px',
            fontWeight: '600'
          }
        },
        subtitle: {
          text: payload.subtitle || null,
          style: {
            fontSize: '14px'
          }
        },
        xAxis: {
          categories: years,
          title: {
            text: null
          },
          tickInterval: payload.xAxisTickInterval || 2,
          labels: {
            style: {
              color: '#222',
              fontSize: '11px'
            }
          },
          lineColor: '#666',
          tickColor: '#666'
        },
        yAxis: {
          title: {
            text: payload.yAxisTitle || 'Disaster intensity',
            style: {
              color: '#222',
              fontSize: '12px',
              fontWeight: '600'
            }
          },
          min: 0,
          gridLineColor: '#e3e7eb',
          labels: {
            style: {
              color: '#222',
              fontSize: '11px'
            }
          }
        },
        legend: {
          align: 'center',
          verticalAlign: 'bottom',
          itemStyle: {
            color: '#1f1f1f',
            fontSize: '12px',
            fontWeight: '600'
          },
          itemMarginTop: 4,
          itemMarginBottom: 4
        },
        tooltip: {
          shared: true,
          valueDecimals: 2,
          backgroundColor: 'rgba(255,255,255,0.97)',
          borderColor: '#777',
          style: {
            color: '#111',
            fontSize: '12px'
          }
        },
        plotOptions: {
          series: {
            marker: {
              enabled: false
            },
            lineWidth: 3.2,
            linecap: 'round',
            stickyTracking: true,
            states: {
              hover: {
                enabled: true,
                lineWidthPlus: 0,
                halo: {
                  size: 8,
                  opacity: 0.2
                }
              },
              inactive: {
                opacity: 0.22
              }
            },
            events: {
              mouseOver: function () {
                const active = this;
                this.chart.series.forEach(s => {
                  if (s !== active) {
                    if (s.group) {
                      s.group.attr({ opacity: 0.22 });
                    }
                  }
                  else {
                    if (s.group) {
                      s.group.attr({ opacity: 1 });
                    }
                  }
                });
              },
              mouseOut: function () {
                this.chart.series.forEach(s => {
                  if (s.group) {
                    s.group.attr({ opacity: 1 });
                  }
                });
              }
            }
          }
        },
        credits: {
          enabled: false
        },
        accessibility: {
          enabled: true
        },
        series: seriesData,
        responsive: {
          rules: [{
            condition: {
              maxWidth: 760
            },
            chartOptions: {
              xAxis: {
                tickInterval: payload.mobileXAxisTickInterval || 4
              },
              legend: {
                itemStyle: {
                  fontSize: '11px'
                }
              },
              title: {
                style: {
                  fontSize: '14px'
                }
              }
            }
          }]
        }
      });
    }
  });
})();
