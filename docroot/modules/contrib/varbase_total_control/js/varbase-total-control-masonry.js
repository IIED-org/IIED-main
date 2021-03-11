/**
 * @file
 * Behaviors for the Varbase Total Control Dashboard masonry.
 */

(function($, _, Drupal) {
  Drupal.behaviors.varbaseTotalControlMasonry = {
    attach: function() {
      const $options = {};
      $options.gutter = 0;
      $options.isFitWidth = true;
      $options.isResizeBound = true;
      $options.itemSelector = ".masonry-item";
      $options.transitionDuration = "500mx";

      $(".vtc-dashboard-masonry").masonry($options);
    }
  };
})(window.jQuery, window._, window.Drupal);
