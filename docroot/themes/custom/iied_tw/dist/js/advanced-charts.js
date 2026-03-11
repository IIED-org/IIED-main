(function (Drupal, once, drupalSettings) {
  window.AdvancedCharts = window.AdvancedCharts || {
    registry: {},

    register: function (key, implementation) {
      this.registry[key] = implementation;
    }
  };

  Drupal.behaviors.advancedCharts = {
    attach(context) {
      const instances = drupalSettings.advancedCharts?.instances || {};

      Object.keys(instances).forEach((id) => {
        once('advancedCharts', '#' + id, context).forEach(async (el) => {
          const settings = instances[id];
          const chartKey = settings.chartKey;
          const implementation = window.AdvancedCharts.registry[chartKey];

          if (!chartKey) {
            console.warn('Advanced chart missing chartKey for element:', id);
            return;
          }

          if (!implementation || typeof implementation.render !== 'function') {
            console.warn('No advanced chart implementation registered for key:', chartKey);
            return;
          }

              try {
            console.log('Rendering advanced chart', chartKey, settings);
            await implementation.render(el, settings);
            console.log('Rendered advanced chart', chartKey);
          }
          catch (error) {
            console.error('Error rendering advanced chart:', chartKey, error);
          }

        });
      });
    }
  };
})(Drupal, once, drupalSettings);
