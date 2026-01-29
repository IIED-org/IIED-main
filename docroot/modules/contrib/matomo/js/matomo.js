(($, drupalSettings, _paq) => {
  /**
   * Default event binding.
   *
   * Attach mousedown, keyup, touchstart events to document only and catch
   * clicks on all elements.
   */
  function defaultBind() {
    $(document.body).on('mousedown keyup touchstart', (event) => {
      // Catch the closest surrounding link of a clicked element.
      $(event.target)
        .closest('a,area')
        // eslint-disable-next-line func-names
        .each(function () {
          if (
            drupalSettings.matomo.trackMailto &&
            // eslint-disable-next-line no-jquery/no-is
            $(this).is("a[href^='mailto:'],area[href^='mailto:']")
          ) {
            // Mailto link clicked.
            _paq.push(['trackEvent', 'Mails', 'Click', this.href.substring(7)]);
          }
        });
    });
  }

  // eslint-disable-next-line no-jquery/no-ready
  $(document).ready(() => {
    defaultBind();

    // Colorbox: This event triggers when the transition has completed and the
    // newly loaded content has been revealed.
    if (drupalSettings.matomo.trackColorbox) {
      $(document).on('cbox_complete', () => {
        const href = $.colorbox.element().attr('href');
        if (href) {
          _paq.push(['setCustomUrl', href]);
          if (drupalSettings.matomo.disableCookies) {
            _paq.push(['disableCookies']);
          }
          _paq.push(['trackPageView']);
        }
      });
    }
  });
})(jQuery, drupalSettings, _paq);
