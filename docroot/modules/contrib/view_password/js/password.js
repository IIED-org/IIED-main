/**
 * @file
 * Contains \Drupal\view_password\password.js.
 */

(function($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.pwd = {
    attach(context) {
      var span_classes_custom = drupalSettings.view_password.span_classes || '';

      var icon_exposed_custom = drupalSettings.view_password.icon_exposed || '';
      var icon_hidden_custom = drupalSettings.view_password.icon_hidden || '';

      $(once('view_password_button', '.pwd-see [type=password]', context))
        .after(
          `<button type="button" class="shwpd ${span_classes_custom} eye-close"
            aria-label="${drupalSettings.view_password.showPasswordLabel}"
          >
          </button>`
        );
      if (icon_hidden_custom !== '') {
        $(once('view_password_icon_button', '.eye-close', context))
          .css({'background-image': `url(${icon_hidden_custom})`});
      }

      $(once('view_password', '.shwpd', context))
        .on('click', function() {
          // To toggle the images.
          $(this).toggleClass('eye-close eye-open');
          $(this).removeAttr('style');

          if ($(this).hasClass('eye-open')) {
            $(this)
              .siblings(':password')
              .prop('type', 'text');
            $(this)
              .attr('aria-label', drupalSettings.view_password.hidePasswordLabel);
            if (icon_exposed_custom !== '') {
              $(this).css({'background-image': `url(${icon_exposed_custom})`});
            }

          } else if ($(this).hasClass('eye-close')) {
            $(this)
              .siblings(':text')
              .prop('type', 'password');
            $(this)
              .attr('aria-label', drupalSettings.view_password.showPasswordLabel);
            if (icon_hidden_custom !== '') {
              $(this).css({'background-image': `url(${icon_hidden_custom})`});
            }
          }
        });
    }
  };
})(jQuery, Drupal, drupalSettings, once);
