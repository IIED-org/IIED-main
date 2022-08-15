/**
 * @file
 * JavaScript's integration between C3 and Drupal.
 */
(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.chartsC3 = {
    attach: function (context, settings) {
      const contents = new Drupal.Charts.Contents();
      once('charts-c3', '.charts-c3', context).forEach(function (element) {
        c3.generate(contents.getData(element.id));
      });
    }
  };
}(Drupal, once));
