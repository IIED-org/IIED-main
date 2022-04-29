/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.ginLbToastify = {
    attach: (context) => {
      let offset = $( '.ui-dialog-off-canvas' ).length ? $( '.ui-dialog-off-canvas').width() : 0;
      $('.glb-messages--warning', context).once('.glb-messages--warning').each(function(){
        Toastify({
          text: $(this).html(),
          escapeMarkup: false,
          gravity: "bottom",
          duration: 6000,
          position: "right",
          offset: {
            x: 0,
          },
          className:"glb-messages glb-messages--warning",
          backgroundColor:"var(--colorGinWarningBackground)"
        }).showToast();
        $(this).hide();
      });
      $('.glb-messages--error', context).once('.glb-messages--error').each(function(){
        Toastify({
          text: $(this).html(),
          escapeMarkup: false,
          gravity: "bottom",
          duration: 6000,
          position: "right",
          offset: {
            x: offset,
          },
          className:"glb-messages glb-messages--error",
          backgroundColor:"var(--colorGinErrorBackground)"
        }).showToast();
        $(this).hide();
      });
      $('.glb-messages--status', context).once('.glb-messages--status').each(function(){
        if ($(this).parents('.glb-toolbar').length >= 1) {
          return;
        }
        Toastify({
          text: $(this).html(),
          escapeMarkup: false,
          gravity: "bottom",
          duration: 6000,
          position: "right",
          offset: {
            x: offset,
          },
          className:"glb-messages glb-messages--status",
          backgroundColor:"var(--colorGinStatusBackground)"
        }).showToast();
        $(this).hide();
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
