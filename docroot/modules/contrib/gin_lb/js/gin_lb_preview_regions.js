/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.glb_preview_regions = {
    attach: (context) => {
      once('glb-preview-region', 'body').forEach(()=>{
        const toolbarPreviewRegion = document.getElementById('glb-toolbar-preview-regions');
        const toolbarPreviewContent = document.getElementById('glb-toolbar-preview-content');
        const formPreviewContent = document.getElementById('layout-builder-content-preview');
        const body = document.getElementsByTagName('body')[0];
        toolbarPreviewContent.checked = formPreviewContent.checked;;
        toolbarPreviewRegion.checked = body.classList.contains('glb-preview-regions--enable');

        toolbarPreviewRegion.addEventListener('change',()=>{
          if(toolbarPreviewRegion.checked){
            document.querySelector('.layout__region-info').parentNode.classList.add('layout-builder__region');
            document.querySelector('body').classList.add('glb-preview-regions--enable');
          } else {
            body.classList.remove('glb-preview-regions--enable')
          }
        })
        toolbarPreviewContent.addEventListener('change',()=>{
          if (formPreviewContent.checked !== toolbarPreviewContent.checked) {
            formPreviewContent.click();
          }
        })
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
