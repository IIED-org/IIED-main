/**
 * @file
 * Javascript for the node content type editing form.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behaviors for setting summaries on content type form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on content type edit forms.
   */  
  Drupal.behaviors.linkcheckerContentTypes = {
    attach: function (context) {
      var $context = $(context);
      // Provide the vertical tab summaries.
      $context.find('#edit-linkchecker').drupalSetSummary(function (context) {
        var vals = [];
        var $editContext = $(context);
        $editContext.find('input:checked').next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });
        if (!vals.length) {
          return Drupal.t('Disabled');
        }
        return vals.join(', ');
      });
    }
  };

})(jQuery, Drupal);
