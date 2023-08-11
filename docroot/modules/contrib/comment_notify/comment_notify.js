(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.commentNotify = {
    attach: function (context) {

      $('.comment-notify', context)
        .bind('change', function() {
          var checkbox = $(this);
          var form = checkbox.closest('.comment-notify-form');

          if (form.length > 0) {
            var radios = $('.comment-notify-type', form);

            if (radios.length > 0) {
              var radiosHolder = radios.parent().parent();

              if (checkbox.is(':checked')) {
                radiosHolder.show();
              }
              else {
                radiosHolder.hide();
              }
            }
          }

        })
        .trigger('change');
    }
  };
})(jQuery, Drupal);
