/**
 * @file
 * JavaScript integration between Billboard and Drupal.
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.chartsBillboard = {
    attach: function (context, settings) {
      const contents = new Drupal.Charts.Contents();
      once('charts-billboard', '.charts-billboard', context).forEach(function (element) {
        bb.generate(contents.getData(element.id));
        if (element.nextElementSibling && element.nextElementSibling.hasAttribute('data-charts-debug-container')) {
          const id = element.id;
          let config = contents.getData(id);
          element.nextElementSibling.querySelector('code').innerText = JSON.stringify(config, null, ' ');
        }
      });
    }
  };
}(Drupal, once));
