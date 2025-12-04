/**
 * @file
 * Javascript for the node content type editing form.
 */

(function ($, Drupal) {
  /**
   * Behaviors for setting summaries on content type form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on content type edit forms.
   */
  Drupal.behaviors.linkcheckerContentTypes = {
    attach(context) {
      const $context = $(context);
      // Provide the vertical tab summaries.
      $context
        .find('#edit-linkchecker')
        .drupalSetSummary(function (editContext) {
          const values = [];
          const $editContext = $(editContext);
          $editContext
            .find('input:checked')
            .next('label')
            .each(function () {
              values.push(Drupal.checkPlain($(this).textContent));
            });
          if (!values.length) {
            return Drupal.t('Disabled');
          }
          return values.join(', ');
        });
    },
  };
})(jQuery, Drupal);
