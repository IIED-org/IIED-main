/**
 * @file
 * Facets views AJAX handling.
 */

(function ($, Drupal) {
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

      // Loop through all facets.
      $.each(settings.facets_views_ajax, function (facetId, facetSettings) {
        // Get the View for the current facet.
        var view, current_dom_id, view_path;
        if (settings.views && settings.views.ajaxViews) {
          $.each(settings.views.ajaxViews, function (domId, viewSettings) {
            // Check if we have facet for this view.
            if (facetSettings.view_id == viewSettings.view_name && facetSettings.current_display_id == viewSettings.view_display_id) {
              view = $('.js-view-dom-id-' + viewSettings.view_dom_id);
              current_dom_id = viewSettings.view_dom_id;
              view_path = facetSettings.ajax_path;
            }
          });
        }

        if (!view || view.length != 1) {
          return;
        }

        // Update view on range slider stop event.
        if (typeof settings.facets !== "undefined" && settings.facets.sliders && settings.facets.sliders[facetId]) {
          settings.facets.sliders[facetId].change = function (values, handle, unencoded, tap, positions, noUiSlider) {
            const sliderSettings = settings.facets.sliders[facetId];
            var href = sliderSettings.url.replace('__range_slider_min__', Math.round(unencoded[0])).replace('__range_slider_max__', Math.round(unencoded[1]));

            // Update facet query params on the request.
            var currentHref = window.location.href;
            var currentQueryParams = Drupal.Views.parseQueryString(currentHref);
            var newQueryParams = Drupal.Views.parseQueryString(href);

            var queryParams = {};
            var facetPositions = [];
            var fCount = 0;
            var value = '';
            var facetKey = '';
            for (var paramName in currentQueryParams) {
              if (paramName.substr(0, 1) === 'f') {
                value = currentQueryParams[paramName];
                // Store the facet position so we can override it later.
                facetKey = value.substr(0, value.indexOf(':'));
                facetPositions[facetKey] = fCount;
                queryParams['f[' + fCount + ']'] = value;
                fCount++;
              }
              else {
                queryParams[paramName] = currentQueryParams[paramName];
              }
            }

            var paramKey = '';
            for (let paramName in newQueryParams) {
              if (paramName.substr(0, 1) === 'f') {
                value = newQueryParams[paramName];
                // Replace.
                facetKey = value.substr(0, value.indexOf(':'));
                if (typeof facetPositions[facetKey] !== 'undefined') {
                  paramKey = 'f[' + facetPositions[facetKey] + ']';
                }
                else {
                  paramKey = 'f[' + fCount + ']';
                  fCount++;
                }
                queryParams[paramKey] = newQueryParams[paramName];
              }
              else {
                queryParams[paramName] = newQueryParams[paramName];
              }
            }

            href = '/' + Drupal.Views.getPath(href) + '?' + $.param(queryParams);

            updateFacetsView(href, current_dom_id, view_path);
          };
        }
        else if (facetId == 'facets_summary_ajax_summary' || facetId == 'facets_summary_ajax_summary_count') {
          if (updateFacetsSummaryBlock()) {
            $('[data-drupal-facets-summary-id=' + facetSettings.facets_summary_id + ']').children('ul').children('li').once().click(function (e) {
              e.preventDefault();
              var facetLink = $(this).find('a');
              updateFacetsView(facetLink.attr('href'), current_dom_id, view_path);
            });
          }
        }
        // Update view on facet item click.
        else {
          $('[data-drupal-facet-id |= ' + facetId + ']').each(function (index, facet_item) {
            if ($(facet_item).hasClass('js-facets-widget')) {
              $(facet_item).unbind('facets_filter.facets');
              $(facet_item).on('facets_filter.facets', function (event, url) {
                $('.js-facets-widget').trigger('facets_filtering');

                updateFacetsView(url, current_dom_id, view_path);
              });
            }
          });

        }
      });
    }
  };

  // Helper function to update views output & Ajax facets.
  var updateFacetsView = function (href, current_dom_id, view_path) {
    // Refresh view.
    var views_parameters = Drupal.Views.parseQueryString(href);
    var views_arguments = Drupal.Views.parseViewArgs(href, 'search');
    var views_settings = $.extend(
        {},
        Drupal.views.instances['views_dom_id:' + current_dom_id].settings,
        views_arguments,
        views_parameters
    );

    // Update View.
    var views_ajax_settings = Drupal.views.instances['views_dom_id:' + current_dom_id].element_settings;
    views_ajax_settings.submit = views_settings;
    views_ajax_settings.url = view_path + '?q=' + href;

    Drupal.ajax(views_ajax_settings).execute();

    // Update url.
    window.historyInitiated = true;
    window.history.pushState(null, document.title, href);

    // ToDo: Update views+facets with ajax on history back.
    // For now we will reload the full page.
    window.addEventListener("popstate", function (e) {
      if (window.historyInitiated) {
        window.location.reload();
      }
    });

    // Refresh facets blocks.
    updateFacetsBlocks(href);
  }

  // Helper function, updates facet blocks.
  var updateFacetsBlocks = function (href) {
    var settings = drupalSettings;
    var facets_blocks = facetsBlocks();

    // Remove All Range Input Form Facet Blocks from being updated.
    if (settings.facets && settings.facets.rangeInput) {
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
      facet_settings.submit.update_summary_block = true;
      facet_settings.submit.facet_summary_plugin_ids = {};
      let summary_selector = '[data-drupal-facets-summary-id=' + settings.facets_views_ajax.facets_summary_ajax_summary.facets_summary_id + ']';
      if (settings.facets_views_ajax.facets_summary_ajax_summary_count !== undefined) {
        summary_selector += ', [data-drupal-facets-summary-id=' + settings.facets_views_ajax.facets_summary_ajax_summary_count.facets_summary_id + ']';
      }
      $(summary_selector).each(function (index, summaryWrapper) {
        let summaryPluginId = $(summaryWrapper).attr('data-drupal-facets-summary-plugin-id');
        let summaryPluginIdWrapper = $(summaryWrapper).attr('id');
        facet_settings.submit.facet_summary_plugin_ids[summaryPluginIdWrapper] = summaryPluginId;
      });
    }

    Drupal.ajax(facet_settings).execute();
  };

  // Helper function to determine if we should update the summary block.
  // Returns true or false.
  var updateFacetsSummaryBlock = function () {
    var settings = drupalSettings;
    var update_summary = false;

    if (settings.facets_views_ajax.facets_summary_ajax_summary || settings.facets_views_ajax.facets_summary_ajax_summary_count) {
      update_summary = true;
    }

    return update_summary;
  };

  // Helper function, return facet blocks.
  var facetsBlocks = function () {
    // Get all ajax facets blocks from the current page.
    var facets_blocks = {};

    $('.block-facets-ajax').each(function (index) {
      var block_id_start = 'js-facet-block-id-';
      var block_id = $.map($(this).attr('class').split(' '), function (v, i) {
        if (v.indexOf(block_id_start) > -1) {
          return v.slice(block_id_start.length, v.length);
        }
      }).join();
      var block_selector = $(this).attr('id');
      facets_blocks[block_selector] = block_id;
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
        updateFacetsBlocks(href);
      }
    }

    // Call the original Drupal method with the right context.
    beforeSend.apply(this, arguments);
  }

  // Helper function to add exposed form data to facets url.
  var addExposedFiltersToFacetsUrl = function (href, view_name, view_display_id) {
    var $exposed_form = $('form#views-exposed-form-' + view_name.replace(/_/g, '-') + '-' + view_display_id.replace(/_/g, '-'));

    var params = Drupal.Views.parseQueryString(href);

    $.each($exposed_form.serializeArray(), function () {
      params[this.name] = this.value;
    });

    return href.split('?')[0] + '?' + $.param(params);
  };

})(jQuery, Drupal);
