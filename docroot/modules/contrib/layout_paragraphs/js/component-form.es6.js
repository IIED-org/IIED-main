(function ($, Drupal) {
  Drupal.behaviors.layoutParagraphsComponentForm = {
    attach: function attach(context) {
      // The layout selection element uses AJAX to load the layout config form.
      // We need to disable the save button while waiting for the AJAX request,
      // to prevent race UI condition.
      // @see https://www.drupal.org/project/layout_paragraphs/issues/3265669
      $('[name="layout_paragraphs[layout]"]').on('change', (e) => {
        $('.lpb-btn--save').prop('disabled', e.currentTarget.disabled);
      });
      // Re-enable the component form save button when the behavior reattaches,
      // which will happen once the AJAX request completes.
      $('.lpb-btn--save').prop('disabled', false);
    }
  }
})(jQuery, Drupal);
