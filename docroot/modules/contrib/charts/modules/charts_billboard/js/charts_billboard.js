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
      });
    }
  };
}(Drupal, once));
