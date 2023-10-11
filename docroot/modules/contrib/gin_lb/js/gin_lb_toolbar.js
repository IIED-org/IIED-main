/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.ginLbToolbar = {
    attach: (context) => {
      once('glb-primary-save', '.js-glb-primary-save').forEach((item)=>{
        item.addEventListener('click', function (event) {
          document.querySelector('#gin_sidebar .form-actions .js-glb-button--primary').click();
        });
      })
    }
  }
})(jQuery, Drupal, drupalSettings);
