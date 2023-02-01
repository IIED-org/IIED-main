/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.gin_lb_lb = {
    attach: (context) => {
      once('gin-lb-lb', '.layout-builder-block', context).forEach((elm)=>{
        var $div = $(elm);
        const activeClass = 'gin-lb--disable-section-focus';
        const observer = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
              if ($(mutation.target).hasClass('focus')) {
                $(mutation.target).parents('.layout-builder__section').addClass(activeClass);
              } else {
                $(mutation.target).parents('.layout-builder__section').removeClass(activeClass);
              }
            }
          });
        });
        observer.observe($div[0], {
          attributes: true
        });
      })
    }
  };

})(jQuery, Drupal, drupalSettings);
