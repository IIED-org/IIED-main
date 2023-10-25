(($, Drupal, once) => {
  // Updates the "Close" button label when a layout is changed.
  Drupal.behaviors.layoutParagraphsBuilderForm = {
    attach: function attach(context) {
      // Prevent nested frontend editors from being activated at the same time.
      $('.lpb-enable__wrapper').removeClass('hidden');
      $('[data-lpb-form-id]').each((i, e) => {
        const p = $(e).parents('[data-lpb-id]').toArray().pop();
        const parent = p || e;
        $('.lpb-enable__wrapper', parent).addClass('hidden');
      });

      // Update the "Close" button to say "Cancel" when any changes are made.
      const events = [
        'lpb-component:insert.lpb',
        'lpb-component:update.lpb',
        'lpb-component:move.lpb',
        'lpb-component:drop.lpb',
      ].join(' ');
      $(once('lpb-builder-form', '[data-lpb-id]', context))
        .on(events, (e) => {
          $(e.currentTarget)
            .closest('[data-lpb-form-id]')
            .find('[data-drupal-selector="edit-close"]')
            .val(Drupal.t('Cancel'));
        });
    },
  };
})(jQuery, Drupal, once);
