/**
 * @file
 * Contains Message UI JS issues.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.nodeFieldsetSummaries = {
    attach: function (context) {
      $('.message-form-owner', context).drupalSetSummary(
        function (context) {
          var name = $('.form-item-name input', context).val() || drupalSettings.message_ui.anonymous,
          date = $('.form-item-date input', context).val();
          return date ?
          Drupal.t('By @name on @date', { '@name': name, '@date': date }) :
          Drupal.t('By @name', { '@name': name });
        }
      );
    }
  };

})(jQuery, Drupal, drupalSettings);
