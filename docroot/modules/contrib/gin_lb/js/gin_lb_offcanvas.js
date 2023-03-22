/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.offCanvas = {
    width: localStorage.getItem('gin_lb_offcanvas_width') != null ? localStorage.getItem('gin_lb_offcanvas_width') : ((drupalSettings.gin_lb || {}).offcanvas_width != null ? drupalSettings.gin_lb.offcanvas_width : 450),
    links: [],
    attach: () => {
      function setOptions(elm) {
        const dataDialog = $(elm).data('dialog-options');
        const dialogOptions = dataDialog != null ? dataDialog : {};
        dialogOptions.width = Drupal.behaviors.offCanvas.width;
        $(elm).attr('data-dialog-options', JSON.stringify(dialogOptions));
        Drupal.ajax.instances.forEach((item)=>{

          if (item != null && item.dialogRenderer === 'off_canvas' ) {
            if (item.options.data.dialogOptions == null) {
              item.options.data.dialogOptions = {};
            }
            if (dialogOptions.width) {
              item.options.data.dialogOptions.width = dialogOptions.width
            }
          }
        })
      }
      once('gin-canvas-event', 'body').forEach(()=>{
        $('body').on( 'dialogresizestop', ( event, ui ) => {
          const sidebar = $('.ui-dialog-off-canvas');
          Drupal.behaviors.offCanvas.width = sidebar.width();
          localStorage.setItem('gin_lb_offcanvas_width', Drupal.behaviors.offCanvas.width);
          Drupal.behaviors.offCanvas.links.forEach((elm)=>{
            setOptions(elm);
          })
        });
      })
      window.setTimeout(() => {
        once('glb-offcanvas-width', 'a[data-dialog-renderer]').forEach((elm)=> {
          Drupal.behaviors.offCanvas.links.push(elm);
          setOptions(elm);
        });
      }, 300)
    }
  };

})(jQuery, Drupal, drupalSettings);
