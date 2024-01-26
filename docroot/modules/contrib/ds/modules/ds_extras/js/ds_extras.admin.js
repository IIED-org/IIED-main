/**
 * @file
 * Javascript functionality for the Display Suite Extras administration UI.
 */

(($, Drupal) => {
  Drupal.behaviors.DSExtrasSummaries = {
    attach(context) {
      // eslint-disable-next-line no-shadow
      $('#edit-fs2', context).drupalSetSummary((context) => {
        const extraFields = $('#edit-fs2-fields-extra', context);

        if (extraFields.is(':checked')) {
          return Drupal.t('Enabled');
        }

        return Drupal.t('Disabled');
      });

      // eslint-disable-next-line no-shadow
      $('#edit-fs3', context).drupalSetSummary((context) => {
        const values = [];

        $('input:checked', context)
          .parent()
          .toArray()
          .forEach((element) => {
            values.push(
              Drupal.checkPlain($.trim($('.option', element).text())),
            );
          });

        if (values.length > 0) {
          return values.join(', ');
        }
        return Drupal.t('Disabled');
      });
    },
  };
})(jQuery, Drupal);
