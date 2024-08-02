(($, Drupal, debounce, dragula, once) => {
  const idAttr = 'data-lpb-id';

  /**
   * Attaches UI elements to $container.
   * @param {jQuery} $container
   *   The container.
   * @param {Object} settings
   *   The settings object.
   */
  function attachUiElements($container, settings) {
    const id = $container.attr('data-lpb-ui-id');
    const lpbBuilderSettings = settings.lpBuilder || {};
    const uiElements = lpbBuilderSettings.uiElements || {};
    const containerUiElements = uiElements[id] || [];
    Object.values(containerUiElements).forEach((uiElement) => {
      const { element, method } = uiElement;
      $container[method]($(element).addClass('js-lpb-ui'));
    });
  }

  /**
   * Repositions open dialogs when their height changes to exceed viewport.
   *
   * The height of an open dialog will change based on its contents and can
   * cause a dialog to grow taller than the current window viewport, making
   * it impossible to reach parts of the content (for example, submit buttons).
   * Repositioning the dialog fixes the issue.
   * @see https://www.drupal.org/project/layout_paragraphs/issues/3252978
   * @see https://stackoverflow.com/questions/5456298/refresh-jquery-ui-dialog-position
   *
   * @param {Number} intervalId
   *   The interval id.
   */
  function repositionDialog(intervalId) {
    const $dialogs = $('.lpb-dialog');
    if ($dialogs.length === 0) {
      clearInterval(intervalId);
      return;
    }
    $dialogs.each((i, dialog) => {
      const bounding = dialog.getBoundingClientRect();
      const viewPortHeight =
        window.innerHeight || document.documentElement.clientHeight;
      if (bounding.bottom > viewPortHeight) {
        const $dialog = $('.ui-dialog-content', dialog);
        const height = viewPortHeight - 200;
        $dialog.dialog('option', 'height', height);
        $dialog.css('overscroll-behavior', 'contain');

        if ($dialog.data('lpOriginalHeight') !== height) {
          $dialog.data('lpOriginalHeight', height);
          const bounding = dialog.getBoundingClientRect();
          const viewPortHeight = window.innerHeight || document.documentElement.clientHeight;
          if (bounding.bottom > viewPortHeight) {
            const pos = $dialog.dialog('option', 'position');
            $dialog.dialog('option', 'position', pos);
          }
        }
      }
    });
  }

  /**
   * Makes an ajax request to reorder all items in the layout.
   * This function is debounced below and not called directly.
   * @param {jQuery} $element The builder element.
   */
  function doReorderComponents($element) {
    const id = $element.attr(idAttr);
    const order = $('.js-lpb-component', $element)
      .get()
      .map((item) => {
        const $item = $(item);
        return {
          uuid: $item.attr('data-uuid'),
          parentUuid:
            $item.parents('.js-lpb-component').first().attr('data-uuid') ||
            null,
          region:
            $item.parents('.js-lpb-region').first().attr('data-region') || null,
        };
      });
    Drupal.ajax({
      url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}layout-paragraphs-builder/${id}/reorder`,
      submit: {
        components: JSON.stringify(order),
      },
      error: () => {
        // Fail silently to prevent console errors.
      },
    }).execute();
  }

  const reorderComponents = debounce(doReorderComponents);

  /**
   * Returns a list of errors for the attempted move, or an empty array if there are no errors.
   * @param {Element} settings The builder settings.
   * @param {Element} el The element being moved.
   * @param {Element} target The destination
   * @param {Element} source The source
   * @param {Element} sibling The next sibling element
   * @return {Array} An array of errors.
   */
  function moveErrors(settings, el, target, source, sibling) {
    return Drupal._lpbMoveErrors
      .map((validator) =>
        validator.apply(null, [settings, el, target, source, sibling]),
      )
      .filter((errors) => errors !== false && errors !== undefined);
  }

  /**
   * Updates move buttons to reflect current state.
   * @param {jQuery} $element The builder element.
   */
  function updateMoveButtons($element) {
    const lpbBuilderElements = Array.from($element[0].querySelectorAll('.js-lpb-component-list, .js-lpb-region'));
    const lpbBuilderComponent = lpbBuilderElements.filter(el => el.querySelector('.js-lpb-component'));

    $element[0].querySelectorAll('.lpb-up, .lpb-down').forEach((el) => {
      // Set the tabindex of the up and down arrows to 0.
      el.setAttribute('tabindex', '0');
    });

    lpbBuilderComponent.forEach((el) => {
      const components = Array.from(el.children).filter(n => n.classList.contains('js-lpb-component'));

      // Set the tabindex of the first component's up arrow to -1.
      components[0].querySelector('.lpb-up')?.setAttribute('tabindex', '-1');

      // Set the tabindex of the last component's down arrow to -1.
      components[components.length - 1].querySelector('.lpb-down')?.setAttribute('tabindex', '-1');
    });
  }

  /**
   * Hides the add content button in regions that contain components.
   * @param {jQuery} $element The builder element.
   */
  function hideEmptyRegionButtons($element) {
    $element.find('.js-lpb-region').each((i, e) => {
      const $e = $(e);
      if ($e.find('.js-lpb-component').length === 0) {
        $e.find('.lpb-btn--add.center').css('display', 'block');
      } else {
        $e.find('.lpb-btn--add.center').css('display', 'none');
      }
    });
  }

  /**
   * Updates the UI based on currently state.
   * @param {jQuery} $element The builder element.
   */
  function updateUi($element) {
    reorderComponents($element);
    updateMoveButtons($element);
    hideEmptyRegionButtons($element);
  }

  /**
   * Moves a component up or down within a simple list of components.
   * @param {jQuery} $moveItem The item to move.
   * @param {int} direction 1 (down) or -1 (up).
   * @return {void}
   */
  function move($moveItem, direction) {
    const $sibling =
      direction === 1
        ? $moveItem.nextAll('.js-lpb-component').first()
        : $moveItem.prevAll('.js-lpb-component').first();

    const method = direction === 1 ? 'after' : 'before';
    const { scrollY } = window;
    const destScroll = scrollY + $sibling.outerHeight() * direction;

    if ($sibling.length === 0) {
      return false;
    }

    // Determine if the move should be animated horizontally or vertically.
    const animateProp = $sibling[0].getBoundingClientRect().top == $moveItem[0].getBoundingClientRect().top
      ? 'translateX'
      : 'translateY';
    // Determine the dimension property to use for the animation.
    const dimmensionProp = animateProp === 'translateX' ? 'offsetWidth' : 'offsetHeight';
    // Determine the distance to move the sibling and the item.
    const siblingDest = $moveItem[0][dimmensionProp] * direction * -1;
    const itemDest = $sibling[0][dimmensionProp] * direction;
    const distance = Math.abs(Math.max(siblingDest, itemDest));
    const duration  = distance * .25;
    const siblingKeyframes = [
      { transform: `${animateProp}(0)` },
      { transform: `${animateProp}(${siblingDest}px)` },
    ];
    const itemKeyframes = [
      { transform: `${animateProp}(0)` },
      { transform: `${animateProp}(${itemDest}px)` },
    ];
    const timing = {
      duration,
      iterations: 1
    }
    const anim1 = $moveItem[0].animate(itemKeyframes, timing);
    anim1.onfinish = () => {
      $moveItem.css({ transform: 'none' });
      $sibling.css({ transform: 'none' });
      $sibling[method]($moveItem);
      $moveItem
        .closest(`[${idAttr}]`)
        .trigger('lpb-component:move', [$moveItem.attr('data-uuid')]);
    };
    $sibling[0].animate(siblingKeyframes, timing);
    if (animateProp === 'translateY') {
      window.scrollTo({
        top: destScroll,
        behavior: 'smooth',
      });
    }
  }

  /**
   * Moves the focused component up or down the DOM to the next valid position
   * when an arrow key is pressed. Unlike move(), nav()can fully navigate
   * components to any valid position in an entire layout.
   * @param {jQuery} $item The jQuery item to move.
   * @param {int} dir The direction to move (1 == down, -1 == up).
   * @param {Object} settings The builder ui settings.
   */
  function nav($item, dir, settings) {
    const $element = $item.closest(`[${idAttr}]`);
    $item.addClass('lpb-active-item');
    // Add shims as target elements.
    if (dir === -1) {
      $(
        '.js-lpb-region .lpb-btn--add.center, .lpb-layout:not(.lpb-active-item)',
        $element,
      ).before('<div class="lpb-shim"></div>');
    } else if (dir === 1) {
      $('.js-lpb-region', $element).prepend('<div class="lpb-shim"></div>');
      $('.lpb-layout:not(.lpb-active-item)', $element).after(
        '<div class="lpb-shim"></div>',
      );
    }
    // Build a list of possible targets, or move destinations.
    const targets = $('.js-lpb-component, .lpb-shim', $element)
      .toArray()
      // Remove child components from possible targets.
      .filter((i) => !$.contains($item[0], i))
      // Remove layout elements that are not self from possible targets.
      .filter(
        (i) => i.className.indexOf('lpb-layout') === -1 || i === $item[0],
      );
    const currentElement = $item[0];
    let pos = targets.indexOf(currentElement);
    // Check to see if the next position is allowed by calling the 'accepts' callback.
    while (
      targets[pos + dir] !== undefined &&
      moveErrors(
        settings,
        $item[0],
        targets[pos + dir].parentNode,
        null,
        $item.next().length ? $item.next()[0] : null,
      ).length > 0
    ) {
      pos += dir;
    }
    if (targets[pos + dir] !== undefined) {
      // Move after or before the target based on direction.
      $(targets[pos + dir])[dir === 1 ? 'after' : 'before']($item);
    }
    // Remove the shims and save the order.
    $('.lpb-shim', $element).remove();
    $item.removeClass('lpb-active-item').focus();
    $item
      .closest(`[${idAttr}]`)
      .trigger('lpb-component:move', [$item.attr('data-uuid')]);
  }

  function startNav($item) {
    const $msg = $(
      `<div id="lpb-navigating-msg" class="lpb-tooltiptext lpb-tooltiptext--visible js-lpb-tooltiptext">${Drupal.t(
        'Use arrow keys to move. Press Return or Tab when finished.',
      )}</div>`,
    );
    $item
      .closest('.lp-builder')
      .addClass('is-navigating')
      .find('.is-navigating')
      .removeClass('is-navigating');
    $item
      .attr('aria-describedby', 'lpb-navigating-msg')
      .addClass('is-navigating')
      .prepend($msg);
    $item.before('<div class="lpb-navigating-placeholder"></div>');
  }

  function stopNav($item) {
    $item
      .removeClass('is-navigating')
      .attr('aria-describedby', '')
      .find('.js-lpb-tooltiptext')
      .remove();
    $item
      .closest(`[${idAttr}]`)
      .removeClass('is-navigating')
      .find('.lpb-navigating-placeholder')
      .remove();
  }

  function cancelNav($item) {
    const $builder = $item.closest(`[${idAttr}]`);
    $builder.find('.lpb-navigating-placeholder').replaceWith($item);
    updateUi($builder);
    stopNav($item);
  }

  /**
   * Prevents user from navigating away and accidentally loosing changes.
   * @param {jQuery} $element The jQuery layout paragraphs builder object.
   */
  function preventLostChanges($element) {
    // Add class "is_changed" when the builder is edited.
    const events = [
      'lpb-component:insert.lpb',
      'lpb-component:update.lpb',
      'lpb-component:move.lpb',
      'lpb-component:drop.lpb',
    ].join(' ');
    $element.on(events, (e) => {
      $(e.currentTarget).addClass('is_changed');
    });
    window.addEventListener('beforeunload', (e) => {
      if ($(`.is_changed[${idAttr}]`).length) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
    $('.form-actions')
      .find('input[type="submit"], a')
      .click(() => {
        $element.removeClass('is_changed');
      });
  }

  /**
   * Attaches event listeners/handlers for builder ui.
   * @param {jQuery} $element The layout paragraphs builder object.
   * @param {Object} settings The builder settings.
   */
  function attachEventListeners($element, settings) {
    preventLostChanges($element);
    $element.on('click.lp-builder', '.lpb-up', (e) => {
      move($(e.target).closest('.js-lpb-component'), -1);
      return false;
    });
    $element.on('click.lp-builder', '.lpb-down', (e) => {
      move($(e.target).closest('.js-lpb-component'), 1);
      return false;
    });
    $element.on('click.lp-builder', '.js-lpb-component', (e) => {
      $(e.currentTarget).focus();
    });
    $element.on('click.lp-builder', '.lpb-drag', (e) => {
      const $btn = $(e.currentTarget);
      startNav($btn.closest('.js-lpb-component'));
    });
    $(document).off('keydown');
    $(document).on('keydown', (e) => {
      const $item = $('.js-lpb-component.is-navigating');
      if ($item.length) {
        switch (e.code) {
          case 'ArrowUp':
          case 'ArrowLeft':
            nav($item, -1, settings);
            break;
          case 'ArrowDown':
          case 'ArrowRight':
            nav($item, 1, settings);
            break;
          case 'Enter':
          case 'Tab':
            stopNav($item);
            break;
          case 'Escape':
            cancelNav($item);
            break;
          default:
            break;
        }
      }
    });
  }

  function initDragAndDrop($element, settings) {
    const containers = once('is-dragula-enabled', '.js-lpb-component-list, .js-lpb-region', $element[0]);
    const drake = dragula(
      containers,
      {
        accepts: (el, target, source, sibling) =>
          moveErrors(settings, el, target, source, sibling).length === 0,
        moves(el, source, handle) {
          const $handle = $(handle);
          if ($handle.closest('.lpb-drag').length) {
            return true;
          }
          if ($handle.closest('.lpb-controls').length) {
            return false;
          }
          return true;
        },
      },
    );
    drake.on('drop', (el) => {
      const $el = $(el);
      if ($el.prev().is('a')) {
        $el.insertBefore($el.prev());
      }
      $element.trigger('lpb-component:drop', [$el.attr('data-uuid')]);
    });
    drake.on('drag', (el) => {
      $element.addClass('is-dragging');
      if (el.className.indexOf('lpb-layout') > -1) {
        $element.addClass('is-dragging-layout');
      } else {
        $element.addClass('is-dragging-item');
      }
      $element.trigger('lpb-component:drag', [$(el).attr('data-uuid')]);
    });
    drake.on('dragend', () => {
      $element
        .removeClass('is-dragging')
        .removeClass('is-dragging-layout')
        .removeClass('is-dragging-item');
    });
    drake.on('over', (el, container) => {
      $(container).addClass('drag-target');
    });
    drake.on('out', (el, container) => {
      $(container).removeClass('drag-target');
    });
    return drake;
  }

  // An array of move error callback functions.
  Drupal._lpbMoveErrors = [];
  /**
   * Registers a move validation function.
   * @param {Function} f The validator function.
   */
  Drupal.registerLpbMoveError = (f) => {
    Drupal._lpbMoveErrors.push(f);
  };
  // Checks nesting depth.
  Drupal.registerLpbMoveError((settings, el, target) => {
    if (
      el.classList.contains('lpb-layout') &&
      $(target).parents('.lpb-layout').length > settings.nesting_depth
    ) {
      return Drupal.t('Exceeds nesting depth of @depth.', {
        '@depth': settings.nesting_depth,
      });
    }
  });
  // If layout is required, prevents component from being placed outside a layout.
  Drupal.registerLpbMoveError((settings, el, target) => {
    if (settings.require_layouts) {
      if (
        el.classList.contains('js-lpb-component') &&
        !el.classList.contains('lpb-layout') &&
        !target.classList.contains('js-lpb-region')
      ) {
        return Drupal.t('Components must be added inside sections.');
      }
    }
  });
  Drupal.AjaxCommands.prototype.LayoutParagraphsEventCommand = (
    ajax,
    response,
  ) => {
    const { layoutId, componentUuid, eventName } = response;
    const $element = $(`[data-lpb-id="${layoutId}"]`);
    $element.trigger(`lpb-${eventName}`, [componentUuid]);
  };

  /*
   * Moves the main form action buttons into the jQuery modal button pane.
   * @param {jQuery} context
   *  The context to search for dialog buttons.
   * @return {void}
   */
  function updateDialogButtons(context) {
    // Determine if this context is from within dialog content.
    const $lpDialog = $(context).closest('.ui-dialog-content');

    if (!$lpDialog) {
      return;
    }

    const buttons = [];
    const $buttons = $lpDialog.find('.layout-paragraphs-component-form > .form-actions input[type=submit], .layout-paragraphs-component-form > .form-actions a.button');

    if ($buttons.length === 0) {
      return;
    }

    $buttons.each((_i, el) => {
      const $originalButton = $(el).css({ display: 'none' });
      buttons.push({
        text: $originalButton.html() || $originalButton.attr('value'),
        class: $originalButton.attr('class'),
        click(e) {
          // If the original button is an anchor tag, triggering the "click"
          // event will not simulate a click. Use the click method instead.
          if ($originalButton.is('a')) {
            $originalButton[0].click();
          } else {
            $originalButton
              .trigger('mousedown')
              .trigger('mouseup')
              .trigger('click');
            e.preventDefault();
          }
        },
      });
    });

    $lpDialog.dialog('option', 'buttons', buttons);
  }

  Drupal.behaviors.layoutParagraphsBuilder = {
    attach: function attach(context, settings) {
      // Add UI elements to the builder, each component, and each region.
      const jsUiElements = once('lpb-ui-elements', '[data-has-js-ui-element]');
      jsUiElements.forEach((el) => {
        attachUiElements($(el), settings);
      });

      // Listen to relevant events and update UI.
      once('lpb-events', '[data-lpb-id]').forEach((el) => {
        $(el).on('lpb-builder:init.lpb lpb-component:insert.lpb lpb-component:update.lpb lpb-component:move.lpb lpb-component:drop.lpb lpb-component:delete.lpb', (e) => {
          const $element = $(e.currentTarget);
          updateUi($element);
        });
      });

      // Initialize the editor drag and drop ui.
      once('lpb-enabled', '[data-lpb-id].has-components').forEach((el) => {
        const $element = $(el);
        const id = $element.attr(idAttr);
        const lpbSettings = settings.lpBuilder[id];
        // Attach event listeners and init dragula just once.
        $element.data('drake', initDragAndDrop($element, lpbSettings));
        attachEventListeners($element, lpbSettings);
        $element.trigger('lpb-builder:init');
      });

      // Add new containers to the dragula instance.
      once('is-dragula-enabled', '.js-lpb-region').forEach((c) => {
        const builderElement = c.closest('[data-lpb-id]');
        const drake = $(builderElement).data('drake');
        drake.containers.push(c);
      });

      // If UI elements have been attached to the DOM, we need to attach behaviors.
      if (jsUiElements.length) {
        Drupal.attachBehaviors(context, settings);
      }

      // Moves dialog buttons into the jQuery modal button pane.
      updateDialogButtons(context);
    },
  };

  // Move the main form action buttons into the jQuery modal button pane.
  // By default, dialog.ajax.js moves all form action buttons into the button
  // pane -- which can have unintended consequences. We suppress that option
  // by setting drupalAutoButtons to false, but then manually move _only_ the
  // main form action buttons into the jQuery button pane.
  // @see https://www.drupal.org/project/layout_paragraphs/issues/3191418
  // @see https://www.drupal.org/project/layout_paragraphs/issues/3216981

  // Repositions open dialogs.
  // @see https://www.drupal.org/project/layout_paragraphs/issues/3252978
  // @see https://stackoverflow.com/questions/5456298/refresh-jquery-ui-dialog-position

  let lpDialogInterval;

  const handleAfterDialogCreate = (event, dialog, $dialog) => {
    const $element = $dialog || jQuery(event.target);
    if ($element.attr('id').startsWith('lpb-dialog-')) {
      updateDialogButtons($element);
      clearInterval(lpDialogInterval);
      lpDialogInterval = setInterval(
        repositionDialog.bind(null, lpDialogInterval),
        500
      );
    }
  };
  if (typeof DrupalDialogEvent === 'undefined') {
    $(window).on('dialog:aftercreate', handleAfterDialogCreate);
  } else {
    window.addEventListener('dialog:aftercreate', handleAfterDialogCreate);
  }

})(jQuery, Drupal, Drupal.debounce, dragula, once);
