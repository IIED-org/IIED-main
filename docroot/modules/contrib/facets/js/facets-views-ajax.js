/**
 * @file
 * Facets views AJAX handling.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Keep the original beforeSend method to use it later.
   */
  var beforeSend = Drupal.Ajax.prototype.beforeSend;

  /**
   * Trigger views AJAX refresh on click.
   */
  Drupal.behaviors.facetsViewsAjax = {
    attach: function (context, settings) {
      // Exit early if there are no views on the page.
      if (!('instances' in Drupal.views)) {
        return;
      }

      // Loop through all facets.
      $.each(settings.facets_views_ajax, function (facetId, facetSettings) {
        // Get the View for this facet.
        var facetViewsInstance = findAjaxViewsInstance(facetSettings.view_id, facetSettings.current_display_id);
        if (!facetViewsInstance) {
          return true;
        }

        // Update view on summary block click.
        if (updateFacetsSummaryBlock() && (facetId === 'facets_summary_ajax')) {
          $('[data-drupal-facets-summary-id=' + facetSettings.facets_summary_id + ']').children('ul').children('li')
            .data('facetsViewsInstance', facetViewsInstance)
            .data('facetSettings', facetSettings)
            .once('facetsViewsAjax').click(function (e) {
              e.preventDefault();
              var facetLink = $(this).find('a');
              var viewsInstance = $(this).data('facetsViewsInstance');
              var facetSettings = $(this).data('facetSettings');
              if (facetLink.length > 0 &&
                  typeof viewsInstance !== 'undefined' && viewsInstance !== null &&
                  typeof facetSettings !== 'undefined' && facetSettings !== null) {
                $(this).trigger('facets_filtering');
                updateFacetsView(facetLink.attr('href'), viewsInstance, facetSettings);
              }
            });
        }
        // Update view on facet item click.
        else {
          $('[data-drupal-facet-id=' + facetId + ']').filter('.js-facets-widget')
            .data('facetsViewsInstance', facetViewsInstance)
            .data('facetSettings', facetSettings)
            .once('facetsViewsAjax')
            .off('facets_filter.facets')
            .on('facets_filter.facets', function (event, url) {
              var viewsInstance = $(this).data('facetsViewsInstance');
              var facetSettings = $(this).data('facetSettings');
              if (typeof viewsInstance !== 'undefined' && viewsInstance !== null &&
                  typeof facetSettings !== 'undefined' && facetSettings !== null) {
                $(this).trigger('facets_filtering');
                updateFacetsView(url, viewsInstance, facetSettings);
              }
            });
        }
        // Update view on slider trigger.
        if ("facets" in settings && "sliders" in settings.facets && settings.facets.sliders[facetId] !== 'undefined') {
          $('[data-drupal-facet-id=' + facetId + ']')
            .data('facetsViewsInstance', facetViewsInstance)
            .data('facetSettings', facetSettings);

            settings.facets.sliders[facetId].stop = function (e, ui) {
              var facet = $(ui.handle).parents('[data-drupal-facet-id=' + facetId + ']');
              var href = settings.facets.sliders[facetId].url.replace('__range_slider_min__', ui.values[0]).replace('__range_slider_max__', ui.values[1]);
              var viewsInstance = $(facet).data('facetsViewsInstance');
              var facetSettings = $(facet).data('facetSettings');
              if (typeof viewsInstance !== 'undefined' && viewsInstance !== null &&
                    typeof facetSettings !== 'undefined' && facetSettings !== null) {
                  updateFacetsView(href, viewsInstance, facetSettings);
                }
            };
        }
      });
    }
  };

// Find the AJAX views instance for the view.
  var findAjaxViewsInstance = function(view_id, view_display_id) {
    var targetViewsInstance = null;
    $.each(Drupal.views.instances, function (viewsInstanceKey, viewsInstance) {
      // Check if we have facet for this view.
      if (('settings' in viewsInstance) &&
          view_id == viewsInstance.settings.view_name &&
          view_display_id == viewsInstance.settings.view_display_id &&
          ('$view' in viewsInstance) && (typeof viewsInstance.$view !== 'undefined') &&
          viewsInstance.$view !== null && viewsInstance.$view.length > 0) {
        // Set facet views instance.
        targetViewsInstance = viewsInstance;
        return false;
      }
    });

    return targetViewsInstance;
  };

  // Get the dom id of the element if it is an AJAX view.
  var findAjaxViewsInstanceByElement = function ($element) {
    var targetViewInstance = null;
    if ($element.is('.view')) {
      $.each(Drupal.views.instances, function (instanceKey, viewsInstance) {
        if (('$view' in viewsInstance) &&
            viewsInstance.$view.length > 0 &&
            ('settings' in viewsInstance) &&
            ('view_dom_id' in viewsInstance.settings) && viewsInstance.settings.view_dom_id &&
            $element.is('.js-view-dom-id-' + viewsInstance.settings.view_dom_id)) {
          // Set the target instance.
          targetViewInstance = viewsInstance;
          return false;
        }
      });
    }

    return targetViewInstance;
  };

  // Ensure the view instance exists.
  var ensureAjaxViewsInstance = function (viewsInstance) {
    // Return the instance if it has enough info.
    if (('settings' in viewsInstance) &&
        ('view_dom_id' in viewsInstance.settings) &&
        viewsInstance.settings.view_dom_id &&
        ('$view' in viewsInstance) &&
        viewsInstance.$view.length > 0 ) {
      return viewsInstance;
    }

    // Attempt to re-find the instance.
    if ('view_name' in viewsInstance.settings &&
        'view_display_id' in viewsInstance.settings) {
      return findAjaxViewsInstance(viewsInstance.settings.view_name, viewsInstance.settings.view_display_id);
    }

    return null;
  };

  // Helper function to update views output & Ajax facets.
  var updateFacetsView = function (href, viewsInstance, facetSettings) {
    if (viewsInstance === null) {
      return;
    }

    // Ensure the current instance and find the new one if it has been lost.
    viewsInstance = ensureAjaxViewsInstance(viewsInstance);
    if (viewsInstance === null) {
      return;
    }

    // Support nested empty view withing 2 levels.
    var viewsParentInstance = null;
    var parentLevel = 0;
    var viewParent = viewsInstance.$view;
    while (parentLevel < 2) {
      parentLevel++;
      viewParent = viewParent.parent();
      viewsParentInstance = findAjaxViewsInstanceByElement(viewParent);
      if (viewsParentInstance !== null) {
        break;
      }
    }

    if (viewsParentInstance !== null) {
      updateFacetsViewRunner(href, viewsParentInstance, facetSettings);
    }
    else {
      updateFacetsViewRunner(href, viewsInstance, facetSettings);
    }
  };

  // Helper function to update views output & Ajax facets.
  var updateFacetsViewRunner = function (href, viewsInstance, facetSettings) {
    var views_parameters = Drupal.Views.parseQueryString(href);
    var views_arguments = Drupal.Views.parseViewArgs(href, 'search');
    var views_settings = $.extend(
        {},
        viewsInstance.settings,
        views_arguments,
        views_parameters
    );

    // Update View.
    var views_ajax_settings = viewsInstance.element_settings;
    views_ajax_settings.submit = views_settings;
    views_ajax_settings.url = facetSettings.ajax_path + '?q=' + href;
    Drupal.ajax(views_ajax_settings).execute();

    // Update url.
    window.historyInitiated = true;
    window.history.pushState(null, document.title, href);

    // ToDo: Update views+facets with ajax on history back.
    // For now we will reload the full page.
    window.addEventListener("popstate", function () {
      if (window.historyInitiated) {
        window.location.reload();
      }
    });

    // Refresh facets blocks.
    updateFacetsBlocks(href);
  };

  // Helper function, updates facet blocks.
  var updateFacetsBlocks = function (href) {
    var settings = drupalSettings;
    var facets_blocks = facetsBlocks();

    // Remove All Range Input Form Facet Blocks from being updated.
    if(settings.facets && settings.facets.rangeInput) {
      $.each(settings.facets.rangeInput, function (index, value) {
        delete facets_blocks[value.facetId];
      });
    }

    // Update facet blocks.
    var facet_settings = {
      url: Drupal.url('facets-block-ajax'),
      submit: {
        facet_link: href,
        facets_blocks: facets_blocks
      }
    };

    // Update facets summary block.
    if (updateFacetsSummaryBlock()) {
      var facet_summary_wrapper_id = $('[data-drupal-facets-summary-id=' + settings.facets_views_ajax.facets_summary_ajax.facets_summary_id + ']').attr('id');
      var facet_summary_block_id = '';
      if (facet_summary_wrapper_id.indexOf('--') !== -1) {
        facet_summary_block_id = facet_summary_wrapper_id.substring(0, facet_summary_wrapper_id.indexOf('--')).replace('block-', '');
      }
      else {
        facet_summary_block_id = facet_summary_wrapper_id.replace('block-', '');
      }
      facet_settings.submit.update_summary_block = true;
      facet_settings.submit.facet_summary_block_id = facet_summary_block_id;
      facet_settings.submit.facet_summary_wrapper_id = settings.facets_views_ajax.facets_summary_ajax.facets_summary_id;
    }

    Drupal.ajax(facet_settings).execute();
  };

  // Helper function to determine if we should update the summary block.
  // Returns true or false.
  var updateFacetsSummaryBlock = function () {
    var settings = drupalSettings;
    var update_summary = false;

    if (settings.facets_views_ajax.facets_summary_ajax) {
      update_summary = true;
    }

    return update_summary;
  };

  // Helper function, return facet blocks.
  var facetsBlocks = function () {
    // Get all ajax facets blocks from the current page.
    var facets_blocks = {};

    $('.block-facets-ajax').each(function () {
      var block_id_start = 'js-facet-block-id-';
      var block_id = $.map($(this).attr('class').split(' '), function (v) {
        if (v.indexOf(block_id_start) > -1) {
          return v.slice(block_id_start.length, v.length);
        }
      }).join();
      var block_selector = '#' + $(this).attr('id');
      facets_blocks[block_id] = block_selector;
    });

    return facets_blocks;
  };

  /**
   * Overrides beforeSend to trigger facetblocks update on exposed filter change.
   *
   * @param {XMLHttpRequest} xmlhttprequest
   *   Native Ajax object.
   * @param {object} options
   *   jQuery.ajax options.
   */
  Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {

    // Update facet blocks as well.
    // Get view from options.
    if (typeof options.extraData !== 'undefined' && typeof options.extraData.view_name !== 'undefined') {
      var href = window.location.href;
      var settings = drupalSettings;

      // TODO: Maybe we should limit facet block reloads by view?
      var reload = false;
      $.each(settings.facets_views_ajax, function (facetId, facetSettings) {
        if (facetSettings.view_id == options.extraData.view_name && facetSettings.current_display_id == options.extraData.view_display_id) {
          reload = true;
        }
      });

      if (reload) {
        href = addExposedFiltersToFacetsUrl(href, options.extraData.view_name, options.extraData.view_display_id);
        options.url = addFacetsToExposedFilterRequest(options.url, href);
        updateFacetsBlocks(href);
      }
    }

    // Call the original Drupal method with the right context.
    beforeSend.apply(this, arguments);
  }

  // Helper function to add exposed form data to facets url
  var addExposedFiltersToFacetsUrl = function (href, view_name, view_display_id) {
    var $exposed_form = $('form#views-exposed-form-' + view_name.replace(/_/g, '-') + '-' + view_display_id.replace(/_/g, '-'));

    var params = Drupal.Views.parseQueryString(href);

    $.each($exposed_form.serializeArray(), function () {
      params[this.name] = this.value;
    });

    return href.split('?')[0] + '?' + $.param(params);
  };

  // Helper function to add facets to exposed form ajax request
  var addFacetsToExposedFilterRequest = function(url, facet_href) {
    var urlParams = Drupal.Views.parseQueryString(url);

      if (!urlParams.q) {
        urlParams.q = facet_href;
      }
    return url.split('?')[0] + '?' + $.param(urlParams);
  };


})(jQuery, Drupal, window.drupalSettings);
