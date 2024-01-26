/**
 * @file
 * Javascript functionality for Display Suite's administration UI.
 */

(($, Drupal) => {
  Drupal.behaviors.DSSummaries = {
    attach(context) {
      // eslint-disable-next-line no-shadow
      $('#edit-fs1', context).drupalSetSummary((context) => {
        const fieldtemplates = $('#edit-fs1-field-template', context);

        if (fieldtemplates.is(':checked')) {
          const fieldTemplate = $(
            '#edit-fs1-ft-default option:selected',
          ).text();
          return `${Drupal.t('Enabled')}: ${Drupal.t(fieldTemplate)}`;
        }

        return Drupal.t('Disabled');
      });

      // eslint-disable-next-line no-shadow
      $('#edit-fs4', context).drupalSetSummary((context) => {
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
