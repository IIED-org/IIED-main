/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.glb_preview_regions = {
    attach: (context) => {
      if ($('#glb-preview-regions').is(':checked')) {
        $('.layout__region-info').parent().addClass('layout-builder__region');
      };

      $('#glb-preview-regions').once('glb-preview-regions').each(()=>{
        $('#glb-preview-regions', context).change(function (){
          if($(this).is(':checked')){
            $('.layout__region-info').parent().addClass('layout-builder__region');
            $('body').addClass('glb-preview-regions--enable');
          } else {
            $('body').removeClass('glb-preview-regions--enable');
          }
        })
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
