(function ($, Drupal, drupalSettings, once) {

  "use strict";

  // Sep up the namespace as a function to store list of arguments in a queue.
  Drupal.eu_cookie_compliance = Drupal.eu_cookie_compliance || function() {
    (Drupal.eu_cookie_compliance.queue = Drupal.eu_cookie_compliance.queue || []).push(arguments)
  };

  // Initialize the object with some data.
  Drupal.eu_cookie_compliance.a = +new Date;

  // A shorter name to use when accessing the namespace.
  const self = Drupal.eu_cookie_compliance;

  Drupal.behaviors.euCookieComplianceGTM = {
    attach: function (context) {

      const prettyPrint = function(e) {
        let textarea = e.currentTarget;
        let ugly = $(textarea).val();
        try {
          let obj = JSON.parse(ugly);
          let pretty = JSON.stringify(obj, undefined, 4);
          $(textarea).val(pretty);
        } catch (e) {
          // Oh well, but whatever...
        }
      }

      $(once('eu_cookie_compliance_gtm_pretty_json_processed', 'textarea.eu_cookie_compliance_gtm_pretty_json')).each(function () {
        $(this).on('blur', prettyPrint);
      });
    }
  };

  /**
   * Replaces tokens in the GTM values.
   * @private
   */
  const _replaceTokens = function(response, value, replacements) {

    var details = drupalSettings.eu_cookie_compliance.cookie_categories_details;
    replacements = replacements || {};

    // Build replacements for all categories' status.
    for (let category in details) {
      let key = '@' + category + '_status';
      replacements[key] = (response.currentCategories.indexOf(category) > -1) ? "1" : "0";
    }

    // Process the replacements.
    for (let key in replacements) {
      value = value.replace(new RegExp(key, 'g'), replacements[key]);
    }

    // Return the result.
    return value;
  }

  /**
   * Push one event with status of all categories
   * and any additional data configured in the backend for categories.
   * @private
   */
  const _pushAllCategoriesToDataLayer = function(response, event) {
    let data = {
      'event': event
    }

    let details = drupalSettings.eu_cookie_compliance.cookie_categories_details;

    for (let category in details) {
      if ('third_party_settings' in details[category]) {
        for (let prop in details[category].third_party_settings.eu_cookie_compliance_gtm.gtm_data) {
          let value = '' + details[category].third_party_settings.eu_cookie_compliance_gtm.gtm_data[prop];
          let status = (response.currentCategories.indexOf(category) > -1) ? "1" : "0";
          data[prop] = _replaceTokens(response, value, {'@status': status});
        }
      }
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(data);
  }

  /**
   * Push individual events for each active category.
   * @private
   */
  const _pushIndividualCategoriesToDataLayer = function(response) {
    window.dataLayer = window.dataLayer || [];

    // Push generic update event.
    window.dataLayer.push({
      'event': 'cookie_consent_update'
    });

    // Push one event for each active category.
    for (let index in response.currentCategories) {
      window.dataLayer.push({
        'event': 'cookie_consent_' + response.currentCategories[index]
      });
    }
  }

  const postPreferencesLoadHandler = function(response) {
    // Push one event with status of all categories
    // and any additional data configured in the backend for categories.
    _pushAllCategoriesToDataLayer(response, 'cookie_consent_post_pref_load');

    // Push individual events for each active category.
    _pushIndividualCategoriesToDataLayer(response);
  };
  Drupal.eu_cookie_compliance('postPreferencesLoad', postPreferencesLoadHandler);

  const postPreferencesSaveHandler = function(response) {
    // Push one event with status of all categories
    // and any additional data configured in the backend for categories.
    _pushAllCategoriesToDataLayer(response, 'cookie_consent_post_pref_save');

    // Push individual events for each active category.
    _pushIndividualCategoriesToDataLayer(response);
  };
  Drupal.eu_cookie_compliance('postPreferencesSave', postPreferencesSaveHandler);

})(jQuery, Drupal, drupalSettings, once);
