(function () {

  'use strict';

  /**
   * Provides the off-canvas menu.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the bootstrap modifications.
   */
  Drupal.behaviors.responsive_menu_bootstrap = {
    attach: function (context) {
      // Hijack the Bootstrap toggler so it expands the mmenu rather than the
      // bootstrap dropdown menu.
      let parent = document.querySelector('#navbar-main');
      let offCanvas = document.querySelector('#off-canvas');

      if (parent && offCanvas.hasOwnProperty('mmApi') && typeof (Mmenu) !== 'undefined' && !parent.classList.contains('mmenu-bootstrap')) {
        // Add a class to the parent so that this code is only triggered once.
        parent.classList.add('mmenu-bootstrap')

        // Remove bootstrap classes from the off-canvas menu.
        _removeClasses(offCanvas.getElementsByTagName('a'));
        _removeClasses(offCanvas.getElementsByTagName('li'));
        _removeClasses(offCanvas.getElementsByTagName('ul'));

        let toggler = parent.querySelector('.navbar-toggler');

        if (toggler) {
          toggler.removeAttribute('data-target');
          // delete toggler.dataset.target; // IE10 has no dataset :(
          toggler.removeAttribute('aria-controls');

          // Remove all bound events.
          toggler.outerHTML = toggler.outerHTML;
          toggler = parent.querySelector('.navbar-toggler');

          // Get the mmenu API.
          const mmenuApi = offCanvas.mmApi;

          // Open the menu on-click.
          toggler.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            // Check if the menu needs to open or close.
            let opened = offCanvas.classList.contains('mm-menu_opened');
            // Trigger the open or close method.
            mmenuApi[opened ? 'close' : 'open']();
          });
        }

        // Removes bootstrap classes.
        function _removeClasses(els) {
          [].forEach.call(els, function(el) {
            el.classList.remove('nav', 'nav-link', 'nav-item')
          })
        }
      }

    }

  }

})();
