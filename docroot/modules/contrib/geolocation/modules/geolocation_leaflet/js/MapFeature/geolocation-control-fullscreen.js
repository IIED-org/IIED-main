/**
 * @file
 * Control fullscreen.
 */

(function (Drupal) {

  'use strict';

  /**
   * Fullscreen control.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches common map fullscreen functionality to relevant elements.
   */
  Drupal.behaviors.leafletControlFullscreen = {
    attach: function (context, drupalSettings) {
      Drupal.geolocation.executeFeatureOnAllMaps(
        'leaflet_control_fullscreen',

        /**
         * @param {GeolocationLeafletMap} map - Current map.
         * @param {GeolocationMapFeatureSettings} featureSettings - Settings for current feature.
         */
        function (map, featureSettings) {
          L.control.fullscreen({
            position: featureSettings.position
          }).addTo(map.leafletMap);

          return true;
        },
        drupalSettings
      );
    },
    detach: function (context, drupalSettings) {}
  };

})(Drupal);
