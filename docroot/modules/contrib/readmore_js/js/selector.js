/**
 * @file
 * Enables syntax highlighting via HighlightJS on the HTML code tag.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.readmore_js = {
    attach: function (context, settings) {

      $('html', context).once('readmore_js').each(function () {
        // Iterate all fields
        $.each( settings.readmore_js, function(index,value){
            // Instantiate new Readmore.
            new Readmore('.' + value.selector, {
              lessLink: Link(value.close_link, value.close_link_classes),
              moreLink: Link(value.more_link, value.more_link_classes),
              speed: value.speed,
              collapsedHeight: value.collapsed_height,
              heightMargin: value.height_margin,
              blockCSS: 'display: inline-block; width: auto;',
              afterToggle: function(trigger, element, expanded) {
                if(!expanded) { // The "Close" link was clicked
                 window.scrollTo({ top: element.offsetTop, behavior: 'smooth' });
                }
              }
            });
        });

        // Creates link markup
        function Link(text, classes) {
          return "<a href='#'" + "class='" + classes + "'>" + text + "</a>";
        }
      });
    }
  };

})(jQuery, Drupal);
