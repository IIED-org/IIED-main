(function () {
  window.AdvancedCharts = window.AdvancedCharts || { registry: {}, register: function (key, implementation) { this.registry[key] = implementation; } };

  window.AdvancedChartsHelpers = {
    async loadText(url) {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Failed to load ${url} (${response.status})`);
      }
      return await response.text();
    },

    async loadJson(url) {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Failed to load ${url} (${response.status})`);
      }
      return await response.json();
    },

    parseSimpleCSV(text) {
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
        return obj;
      });
    },

    toNum(value) {
      const n = parseFloat(value);
      return Number.isNaN(n) ? null : n;
    }
  };
})();
