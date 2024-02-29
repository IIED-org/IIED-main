/**
 * @file
 * JavaScript behaviors for help text (tooltip).
 * Credit for this code: Webform module (https://www.drupal.org/project/webform)
 */

(function ($, Drupal, once) {

  'use strict';

  Drupal.behaviors.miniOrange2faElementHelpIcon = {
    attach: function (context) {
      if (!window.tippy) {
        return;
      }

      var hideOnEsc = {
        name: 'hideOnEsc',
        defaultValue: true,
        fn: function fn(_ref) {
          var hide = _ref.hide;

          function onKeyDown(event) {
            if (event.keyCode === 27) {
              hide();
            }
          }

          return {
            onShow: function onShow() {
              document.addEventListener('keydown', onKeyDown);
            },
            onHide: function onHide() {
              document.removeEventListener('keydown', onKeyDown);
            }
          };
        }
      };

      $(once('miniorange-2fa-help', '.js-miniorange-2fa-help', context)).each(function () {
        var $link = $(this);

        $link.on('click', function (event) {
          event.preventDefault();
        });

        var options = $.extend({
          content: $link.attr('data-miniorange-2fa-help'),
          delay: 100,
          allowHTML: true,
          interactive: true,
          plugins: [hideOnEsc]
        });

        tippy(this, options);
      });
    }
  };

})(jQuery, Drupal, once);

