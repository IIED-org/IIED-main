(($, Drupal) => {
  Drupal.behaviors.layoutParagraphsPermissionsDisableReorder = {
    attach() {
      Drupal.registerLpbMoveError((settings, el, target) => {
        if ($(target).closest('js-lpb-reordering-disabled')) {
          return 'Reordering is disabled for the current user.';
        }
      });
    },
  };
})(jQuery, Drupal);
