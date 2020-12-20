(function ($) {

  'use strict';

  /**
   * Provides additional but optional functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for superfish.
   */
  Drupal.behaviors.responsive_menu_optional = {
    attach: function (context, settings) {

      $(context).find('body').once('responsive-menu-optional').each(function () {

        // Apply the superfish library to the menu.
        if ($.fn.superfish && drupalSettings.responsive_menu.superfish.active) {
          // Get the superfish settings.
          var superfishDelay = drupalSettings.responsive_menu.superfish.delay,
            superfishSpeed = drupalSettings.responsive_menu.superfish.speed,
            superfishSpeedOut = drupalSettings.responsive_menu.superfish.speedOut;
          // Attach superfish to the responsive menu.
          $('#horizontal-menu').superfish({
            delay: parseInt(superfishDelay, 10),
            speed: parseInt(superfishSpeed, 10),
            speedOut: parseInt(superfishSpeedOut, 10)
          }).addClass('sf-menu');
        }

      });
    }
  };

})(jQuery);
