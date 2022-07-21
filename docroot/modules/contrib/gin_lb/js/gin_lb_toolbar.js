/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

(($, Drupal, drupalSettings) => {

  Drupal.behaviors.ginToolbar = {
    attach: () => {
      $('.glb-configure').add('.glb-toolbar__close').once('gin-toolbar-configure').each((item, elm)=>{
        $(elm).click(()=>{
          $('.glb-toolbar').toggleClass('glb-toolbar--extended glb-toolbar--small');
          calculateWidth();
        })
      });
      const glbToolbar = $('.glb-toolbar');
      $('body').once('gin-toolbar-event').each(()=>{
        const events = ['dialogopen', 'dialogresizestop','dialogresize'];
        events.forEach((eventName)=>{
          $('body').on( eventName, ( event, ui ) => {
            calculateWidth();
          });
        });
        $('body').on( 'dialogclose', ( event, ui ) => {
          const modal = $(event.target);
          if (modal.attr('id') === 'drupal-off-canvas') {
            glbToolbar.css('width', '100%');
          }
        });
      });

      function calculateWidth () {
        if (glbToolbar.hasClass('glb-toolbar--small')) {
          glbToolbar.css('width', '100%');
        } else {
          glbToolbar.css('width', Drupal.behaviors.offCanvas.width + 'px');
        }
      }
      calculateWidth();
    }
  };
  Drupal.toolbar.ToolbarVisualView.prototype.updateToolbarHeight = function () {
    const glbToolbar = $('.glb-toolbar');
    const toolbarTabOuterHeight = $('#toolbar-bar').outerHeight() || 0;
    const toolbarTrayHorizontalOuterHeight = $('.is-active.toolbar-tray-horizontal').outerHeight() || 0;
    const toolbarHorizontalAdminOuterHeight = $('.gin--horizontal-toolbar #toolbar-administration').outerHeight() || 0;
    const glbToolbarHeight = glbToolbar.outerHeight();

    this.model.set(
        'height',
        toolbarTabOuterHeight +
        toolbarTrayHorizontalOuterHeight +
        glbToolbarHeight +
        toolbarHorizontalAdminOuterHeight
    );

    const body = $('body')[0];
    body.style.setProperty('padding-top', this.model.get('height') + 'px', 'important');
    glbToolbar.css('top', toolbarTabOuterHeight + toolbarTrayHorizontalOuterHeight + toolbarHorizontalAdminOuterHeight);
    glbToolbar.addClass('glb-toolbar--processed');
    this.triggerDisplace();
  }
})(jQuery, Drupal, drupalSettings);

