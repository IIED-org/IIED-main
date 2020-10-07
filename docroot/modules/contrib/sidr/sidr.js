/**
 * @file
 * Sidr behaviors.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Sidr namespace.
   *
   * @type {Object}
   */
  Drupal.Sidr = Drupal.Sidr || {};

  /**
   * Returns the ID of the open Sidr panel, if any.
   *
   * @return {String|false}
   */
  Drupal.Sidr.getOpenSidrId = function () {
    return jQuery.sidr('status').opened;
  };

  /**
   * Closes all or a specific Sidr.
   *
   * @param {String} sidrId
   *   Sidr ID.
   */
  Drupal.Sidr.closeSidr = function (sidrId) {
    sidrId = 'undefined' === typeof sidrId
      ? this.getOpenSidrId() : sidrId;
    if (sidrId) {
      $.sidr('close', sidrId);
    }
  };

  /**
   * Saves the element as the last used Sidr trigger.
   *
   * Focus can then be restored to this trigger when the user closes a Sidr
   * with their keyboard.
   *
   * @param {Object} element
   *   Sidr trigger DOM element.
   */
  Drupal.Sidr.registerLastUsedTrigger = function (element) {
    $(document.body).data('sidr.lastTrigger', element);
  }

  /**
   * Restores focus to the last used Sidr trigger.
   */
  Drupal.Sidr.focusLastUsedTrigger = function (delay) {
    var lastTrigger = $(document.body).data('sidr.lastTrigger');
    if (lastTrigger) {
      $(lastTrigger).focus();
    }
  }

  /**
   * Attaches a listener to close open Sidr when it loses focus.
   *
   * TODO: Remove this if and when it is added to Sidr.
   *   https://github.com/artberri/sidr/issues/338
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sidr_close_on_blur = {
    attach: function (context, drupalSettings) {
      if (drupalSettings.sidr.closeOnBlur !== true) {
        return;
      }

      $(document.body)
        .once('sidr-close-on-blur')
        // Keyup is required here to track Tab or Shift+Tab press.
        .bind('keyup.sidr click.sidr', function (e) {
          // If no Sidr is currently open, do nothing.
          if (!Drupal.Sidr.getOpenSidrId()) {
            return;
          }

          // If the escape key has been pressed, it will be handled
          // by Drupal.behaviors.sidr_close_on_escape, so do nothing.
          if (e.type === 'keyup' && e.code === 'Escape') {
            return;
          }

          // Determine if the Sidr is going out of focus.
          var isBlur = true;

          // If the event is coming from within a Sidr.
          if ($(e.target).closest('.sidr').length !== 0) {
            isBlur = false;
          }

          // If the event is coming from within a trigger.
          if ($(e.target).closest('.js-sidr-trigger').length !== 0) {
            isBlur = false;
          }

          // Close the Sidr if it is not in focus.
          if (isBlur) {
            Drupal.Sidr.closeSidr();

            if (e.type === 'keyup') {
              Drupal.Sidr.focusLastUsedTrigger();
            }
          }
        });
    }
  }

  /**
   * Attaches an event handler to close open Sidr when ESCAPE is pressed.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sidr_close_on_escape = {
    attach: function (context, drupalSettings) {
      if (drupalSettings.sidr.closeOnEscape !== true) {
        return;
      }

      $(document.body)
        .once('sidr-close-on-escape')
        .bind('keyup.sidr', function (e) {
          if (!Drupal.Sidr.getOpenSidrId()) {
            return;
          }

          // Close the Sidr if 'Escape' was pressed.
          if (e.code === 'Escape') {
            Drupal.Sidr.closeSidr();

            // This handles "escape" and "shift + tab" press.
            Drupal.Sidr.focusLastUsedTrigger();
          }
        });
    }
  }

  /**
   * Sidr triggers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sidr_trigger = {
    attach: function (context, drupalSettings) {
      // Initialize all sidr triggers.
      $(context)
        .find('.js-sidr-trigger')
        .once('sidr-trigger')
        .each(function () {
          var $trigger = $(this);

          // Prepare options.
          var options = $trigger.attr('data-sidr-options') || '{}';
          options = $.parseJSON(options);

          // Determine target.
          var $target = $(options.source);
          if ($target.length === 0) {
            Drupal.throwError('Target element not found: ' + options.source);
            return;
          }

          options.onOpen = function () {
            var sidr = this;

            // Unhide the Sidr for screen-readers.
            sidr.item.attr('aria-hidden', 'false');

            // Mark all triggers as active.
            $('[aria-controls="' + sidr.name + '"]')
              .addClass('is-active')
              .attr('aria-expanded', true);
          };

          options.onOpenEnd = function () {
            var sidr = this;

            // Focus the first focusable element in the Sidr when opened.
            //
            // TODO: Remove this when it is added to Sidr.
            // https://github.com/artberri/sidr/issues/289
            var $target = sidr.item
              .find(':input, a')
              .filter(':visible').first();
            $target.focus();
          };

          options.onClose = function () {
            var sidr = this;

            // Mark all triggers as inactive.
            $('[aria-controls="' + sidr.name + '"]')
              .removeClass('is-active')
              .attr('aria-expanded', false);
          };

          options.onCloseEnd = function () {
            var sidr = this;

            // Hide the Sidr for screen-readers.
            sidr.item.attr('aria-hidden', 'true');
          };

          // Bind Sidr plugin.
          $trigger.sidr(options);
          var sidrId = $trigger.data('sidr');
          var $sidr = $('#' + sidrId);

          // Set initial 'aria' attributes for the trigger.
          $trigger
            .attr('aria-controls', sidrId)
            .attr('aria-expanded', false);

          // Hide Sidr for screen-readers.
          $sidr.attr('aria-hidden', 'true');

          // Populate the Sidr with original DOM elements instead of copying
          // their inner HTML. This removes duplicate IDs and preserves event
          // handlers attached to the source elements.
          if (options.nocopy) {
            var $inner = $('<div class="sidr-inner"></div>').append($target);
            $sidr.html($inner);
          }

          // Attach behaviors to Sidr contents.
          Drupal.attachBehaviors($sidr[0], drupalSettings);

          // Remember the last used trigger. When "escape" is pressed to to
          // an open Sidr, we will bring back the focus on this trigger.
          $trigger.click(function () {
            Drupal.Sidr.registerLastUsedTrigger(this);
          });
        });
    }
  };

  /**
   * Behaviors for links inside Sidr panels.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sidr_link = {
    attach: function (context) {
      // Detect Sidr panels, if any.
      var $context = $(context);
      var $sidr = $context.hasClass('sidr')
        ? $context : $context.find('.sidr');

      // If no Sidr panel, do nothing.
      if ($sidr.length == 0) {
        return;
      }

      // When a link inside a Sidr is clicked, close the Sidr panel, unless the
      // link has a "js-sidr-no-close" class. This can be useful for links and
      // buttons which depend on JavaScript to alter the page contents. For
      // example, an accordion mechanism in expandable menus won't be useful
      // unless the Sidr panel would remain visible after the click.
      $sidr
        .find('a')
        .once('sidr-link')
        .bind('click.sidr', function () {
          if (
            !$(this).hasClass('js-sidr-no-close') &&
            // Sidr can rename this class with a 'sidr-class-' prefix.
            !$(this).hasClass('sidr-class-js-sidr-no-close')
          ) {
            Drupal.Sidr.closeSidr();
          }
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
