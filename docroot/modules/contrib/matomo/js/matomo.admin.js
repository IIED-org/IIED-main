(($, Drupal) => {
  /**
   * Provide the summary information for the tracking settings vertical tabs.
   */
  Drupal.behaviors.trackingSettingsSummary = {
    attach(context) {
      // Make sure this behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('#edit-page-visibility-settings', context).drupalSetSummary(
        (context) => {
          const $radio = $(
            'input[name="matomo_visibility_request_path_mode"]:checked',
            context,
          );
          if ($radio.val() === '0') {
            if (
              !$(
                'textarea[name="matomo_visibility_request_path_pages"]',
                context,
              ).val()
            ) {
              return Drupal.t('Not restricted');
            }

            return Drupal.t('All pages with exceptions');
          }

          return Drupal.t('Restricted to certain pages');
        },
      );

      $('#edit-role-visibility-settings', context).drupalSetSummary(
        (context) => {
          const vals = [];
          // eslint-disable-next-line func-names
          $('input[type="checkbox"]:checked', context).each(function () {
            vals.push($(this).next('label').html().trim());
          });
          if (!vals.length) {
            return Drupal.t('Not restricted');
          }
          if (
            $(
              'input[name="matomo_visibility_user_role_mode"]:checked',
              context,
            ).val() === '1'
          ) {
            return Drupal.t('Excepted: @roles', { '@roles': vals.join(', ') });
          }

          return vals.join(', ');
        },
      );

      $('#edit-user-visibility-settings', context).drupalSetSummary(
        (context) => {
          const $radio = $(
            'input[name="matomo_visibility_user_account_mode"]:checked',
            context,
          );
          if ($radio.val() === '0') {
            return Drupal.t('Not customizable');
          }
          if ($radio.val() === '1') {
            return Drupal.t('On by default with opt out');
          }

          return Drupal.t('Off by default with opt in');
        },
      );

      $('#edit-linktracking', context).drupalSetSummary((context) => {
        const vals = [];
        if ($('input#edit-matomo-trackmailto', context).is(':checked')) {
          vals.push(Drupal.t('Mailto links'));
        }
        if ($('input#edit-matomo-trackfiles', context).is(':checked')) {
          vals.push(Drupal.t('Outbound links'));
          vals.push(Drupal.t('Downloads'));
        }
        if ($('input#edit-matomo-trackcolorbox', context).is(':checked')) {
          vals.push(Drupal.t('Colorbox'));
        }
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', { '@items': vals.join(', ') });
      });

      $('#edit-messagetracking', context).drupalSetSummary((context) => {
        const vals = [];
        // eslint-disable-next-line func-names
        $('input[type="checkbox"]:checked', context).each(function () {
          vals.push($(this).next('label').html().trim());
        });
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', { '@items': vals.join(', ') });
      });

      $('#edit-search', context).drupalSetSummary((context) => {
        const vals = [];
        if ($('input#edit-matomo-site-search', context).is(':checked')) {
          vals.push(Drupal.t('Site search'));
        }
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', { '@items': vals.join(', ') });
      });

      $('#edit-domain-tracking', context).drupalSetSummary((context) => {
        const $radio = $('input[name="matomo_domain_mode"]:checked', context);
        if ($radio.val() === '0') {
          return Drupal.t('A single domain');
        }
        if ($radio.val() === '1') {
          return Drupal.t('One domain with multiple subdomains');
        }
      });

      $('#edit-page-title-hierarchy', context).drupalSetSummary((context) => {
        const vals = [];
        if (
          $('input#edit-matomo-page-title-hierarchy', context).is(':checked')
        ) {
          vals.push(Drupal.t('Show page titles'));
        }
        if (
          $('input#edit-matomo-page-title-hierarchy-exclude-home', context).is(
            ':checked',
          )
        ) {
          vals.push(Drupal.t('Hide home page'));
        }
        if (!vals.length) {
          return Drupal.t('Not tracked');
        }
        return Drupal.t('@items enabled', { '@items': vals.join(', ') });
      });

      $('#edit-status-codes', context).drupalSetSummary((context) => {
        const vals = [];
        if ($('input#edit-status-codes-disabled-404', context).is(':checked')) {
          vals.push(Drupal.t('404'));
        }
        if ($('input#edit-status-codes-disabled-403', context).is(':checked')) {
          vals.push(Drupal.t('403'));
        }
        if (!vals.length) {
          return Drupal.t('Default');
        }
        return Drupal.t('@items disabled', { '@items': vals.join(', ') });
      });

      $('#edit-privacy', context).drupalSetSummary((context) => {
        const vals = [];
        if ($('input#edit-matomo-privacy-donottrack', context).is(':checked')) {
          vals.push(Drupal.t('Universal web tracking opt-out enabled'));
        }
        if (
          $('input#edit-matomo-privacy-disablecookies', context).is(':checked')
        ) {
          vals.push(Drupal.t('Cookies disabled'));
        }
        if (!vals.length) {
          return Drupal.t('No privacy');
        }
        return Drupal.t('@items', { '@items': vals.join(', ') });
      });
    },
  };
})(jQuery, Drupal);
