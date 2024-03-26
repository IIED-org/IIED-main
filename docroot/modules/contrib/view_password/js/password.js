/**
 * @file
 * Contains \Drupal\view_password\password.js.
 */

(function($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.pwd = {
    attach(context) {
      var span_classes_custom = drupalSettings.view_password.span_classes || '';

      $(once('view_password_button', '.pwd-see [type=password]', context))
        .after(
        `<button type="button" class="shwpd ${span_classes_custom} eye-close" aria-label="${drupalSettings.view_password.showPasswordLabel}"></button>`
      );
      $(once('view_password', '.shwpd', context))
        .on('click', function() {
          // To toggle the images.
          $(this).toggleClass('eye-close eye-open');

          if ($(this).hasClass('eye-open')) {
            $('.eye-open', context)
              .siblings(':password')
              .prop('type', 'text');
            $('button.shwpd').attr('aria-label', drupalSettings.view_password.hidePasswordLabel);
          } else if ($(this).hasClass('eye-close')) {
            $('.eye-close', context)
              .siblings(':text')
              .prop('type', 'password');
            $('button.shwpd').attr('aria-label', drupalSettings.view_password.showPasswordLabel);
          }
        });
    }
  };
})(jQuery, Drupal, drupalSettings, once);
