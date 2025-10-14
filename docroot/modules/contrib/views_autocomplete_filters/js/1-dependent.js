/**
 * @file
 * Autocomplete based on jQuery UI.
 */

(($, Drupal) => {
  let autocomplete;

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    const elementId = this.element.attr('id');

    if (!(elementId in autocomplete.cache)) {
      autocomplete.cache[elementId] = {};
    }

    /**
     * Filter through the suggestions removing all terms already tagged and
     * display the available terms to the user.
     *
     * @param {object} suggestions
     *   Suggestions returned by the server.
     */
    function showSuggestions(suggestions) {
      const tagged = autocomplete.splitValues(request.term);
      const il = tagged.length;
      for (let i = 0; i < il; i++) {
        if (suggestions.includes(tagged[i])) {
          suggestions.splice(suggestions.indexOf(tagged[i]), 1);
        }
      }
      response(suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.
    const term = autocomplete.extractLastTerm(request.term);

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      autocomplete.cache[elementId][term] = data;

      // Send the new string array of terms to the jQuery UI list.
      showSuggestions(data);
    }

    // Check if the term is already cached.
    if (autocomplete.cache[elementId].hasOwnProperty(term)) {
      showSuggestions(autocomplete.cache[elementId][term]);
    } else {
      const dataString = [];
      dataString.success = sourceCallbackHandler;
      dataString.data = {};
      dataString.data.q = term;

      if (Drupal.isDependent(this.element)) {
        const a = Drupal.serializeOuterForm(this.element);
        $.each(a, (_key, value) => {
          // We should have an array of values for element with multi values.
          if (value.name.indexOf('[]') > -1) {
            if (!dataString.data[value.name]) {
              dataString.data[value.name] = [];
              dataString.data[value.name].push(value.value);
            }
          } else {
            dataString.data[value.name] = value.value;
          }
        });
      }
      const options = $.extend(dataString, autocomplete.ajax);
      $.ajax(this.element.attr('data-autocomplete-path'), options);
    }
  }

  /**
   * Attaches the autocomplete behavior to all required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the autocomplete behaviors.
   */
  Drupal.behaviors.autocomplete = {
    attach(context) {
      // Act on textfields with the "form-autocomplete" class.
      const $autocomplete = $(
        once('vaf-autocomplete', 'input.form-autocomplete', context),
      );
      if ($autocomplete.length) {
        // Allow options to be overridden per instance.
        const blacklist = $autocomplete.attr(
          'data-autocomplete-first-character-blacklist',
        );
        $.extend(autocomplete.options, {
          firstCharacterBlacklist: blacklist || '',
        });
        // Use jQuery UI Autocomplete on the textfield.
        $autocomplete.autocomplete(autocomplete.options).each(function item() {
          $(this).data('ui-autocomplete')._renderItem =
            autocomplete.options.renderItem;
        });
      }
    },
    detach(context, _settings, trigger) {
      if (trigger === 'unload') {
        const autocompleteForm = $(context).find('input.form-autocomplete');
        if (autocompleteForm.hasClass('ui-autocomplete')) {
          autocompleteForm.autocomplete('destroy');
        }
        once.remove('vaf-autocomplete', 'input.form-autocomplete', context);
      }
    },
  };

  /**
   * Autocomplete object implementation.
   *
   * @namespace Drupal.autocomplete
   */
  autocomplete = {
    cache: {},
    // Exposes options to allow overriding by contrib.
    splitValues: Drupal.autocomplete.splitValues,
    extractLastTerm: Drupal.autocomplete.extractLastTerm,
    // jQuery UI autocomplete options.

    /**
     * JQuery UI option object.
     *
     * @name Drupal.autocomplete.options
     */
    options: {
      source: sourceData,
      focus: Drupal.autocomplete.options.focusHandler,
      search: Drupal.autocomplete.options.search,
      select: Drupal.autocomplete.options.select,
      renderItem: Drupal.autocomplete.options.renderItem,
      minLength: 1,
      // Custom options, used by Drupal.autocomplete.
      firstCharacterBlacklist: '',
    },
    ajax: {
      dataType: 'json',
    },
  };

  Drupal.autocomplete = autocomplete;

  /**
   * Function which checks if autocomplete depends on other filter fields.
   *
   * @param {object} element
   *   Element to check against.
   * @return {bool}
   *   True if the element is dependent.
   */
  Drupal.isDependent = (element) => {
    return $(element).hasClass('views-ac-dependent-filter');
  };

  /**
   * Returns serialized input values from form except autocomplete input.
   *
   * @param {object} element
   *   Element to operate on.
   * @return {Array}
   *   The serialized array of values.
   */
  Drupal.serializeOuterForm = (element) => {
    return $(element)
      .parents('form:first')
      .find('select[name], textarea[name], input[name][type!=submit]')
      .not(this.input)
      .serializeArray();
  };
})(jQuery, Drupal, once);
