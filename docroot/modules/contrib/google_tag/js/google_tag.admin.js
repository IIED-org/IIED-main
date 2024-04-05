/**
 * @file
 * Behaviors and utility functions for administrative pages.
 */

(function ($) {

  /**
   * Provides summary information for the vertical tabs.
   */
  Drupal.behaviors.gtmInsertionSettings = {
    attach(context) {
      // @todo Magic to use 'data-drupal-selector' vs. 'details#edit-path'?
      let element;
      let plural;
      let adjective;
      let selectors;

      // Pass context parameters to outer function.
      function toggleValuesSummary(element, plural, adjective) {
        // Return a callback function as expected by drupalSetSummary().
        return function (details) {
          let str = '';
          const toggle = $('input[type="radio"]:checked', details).val();

          console.log('inside toggleValuesSummary');
          console.log('plural=' + plural);

          const values = element === 'checkbox' ?
            $('input[type="checkbox"]:checked + label', details).length :
            $('textarea', details).val();

          if (toggle === 'exclude listed') {
            str = !values ? 'All !plural' : 'All !plural except !adjective !plural';
          }
          else {
            str = !values ? 'No !plural' : 'Only !adjective !plural';
          }

          const args = {'!plural': plural, '!adjective': adjective};
          return Drupal.t(Drupal.formatString(str, args));
        }
      }

      element = 'checkbox';
      adjective = 'selected';
      selectors = ['role', 'gtag-domain', 'gtag-language'];

      selectors.forEach((selector) => {
        plural = selector.replace('gtag-', '') + 's';
        $('[data-drupal-selector="edit-' + selector + '"]', context).drupalSetSummary(toggleValuesSummary(element, plural, adjective));
      });

      element = 'textarea';
      adjective = 'listed';
      selectors = ['path', 'status'];

      selectors.forEach((selector) => {
        plural = selector.replace('gtag-', '').replace('status', 'statuse') + 's';
        $('[data-drupal-selector="edit-' + selector + '"]', context).drupalSetSummary(toggleValuesSummary(element, plural, adjective));
      });
    }
  };

})(jQuery);
