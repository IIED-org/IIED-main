/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
"use strict";

(function ($, Drupal) {
  Drupal.behaviors.layoutParagraphsComponentForm = {
    attach: function attach(context) {
      $('[name="layout_paragraphs[layout]"]').on('change', function (e) {
        $('.lpb-btn--save').prop('disabled', e.currentTarget.disabled);
      });
      $('.lpb-btn--save').prop('disabled', false);
    }
  };
})(jQuery, Drupal);