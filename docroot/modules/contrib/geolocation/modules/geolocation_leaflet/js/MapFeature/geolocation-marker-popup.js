/**
 * @file
 * Marker Popup.
 */

/**
 * @typedef {Object} LeafletMarkerPopupSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @property {Boolean} infoAutoDisplay
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Marker Popup.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches common map marker popup functionality to relevant elements.
   */
  Drupal.behaviors.leafletMarkerPopup = {
    attach: function (context, drupalSettings) {
      Drupal.geolocation.executeFeatureOnAllMaps(
        'leaflet_marker_popup',

        /**
         * @param {GeolocationLeafletMap} map - Current map.
         * @param {LeafletMarkerPopupSettings} featureSettings - Settings for current feature.
         */
        function (map, featureSettings) {
          var geolocationLeafletPopupHandler = function (currentMarker) {
            if (typeof (currentMarker.locationWrapper) === 'undefined') {
              return;
            }

            var content = currentMarker.locationWrapper.find('.location-content');

            if (content.length < 1) {
              return;
            }
            currentMarker.bindPopup(content.html());

            if (featureSettings.infoAutoDisplay) {
              currentMarker.openPopup();
            }
          };

          map.addPopulatedCallback(function (map) {
            $.each(map.mapMarkers, function (index, currentMarker) {
              geolocationLeafletPopupHandler(currentMarker);
            });
          });

          map.addMarkerAddedCallback(function (currentMarker) {
            geolocationLeafletPopupHandler(currentMarker);
          });

          return true;
        },
        drupalSettings
      );
    },
    detach: function (context, drupalSettings) {}
  };
})(jQuery, Drupal);
