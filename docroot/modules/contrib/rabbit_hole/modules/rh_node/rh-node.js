/**
 * @file
 */

(function ($, Drupal) {
  Drupal.behaviors.rhNode = {
    attach(context, settings) {
      // Display the action in the vertical tab summary.
      $(context)
        .find('.rabbit-hole-settings-form')
        .drupalSetSummary(function (context) {
          const $action = $(
            '.rabbit-hole-action-setting input:checked',
            context,
          );
          return Drupal.checkPlain($action.next('label').textContent());
        });
    },
  };
})(jQuery, Drupal);
