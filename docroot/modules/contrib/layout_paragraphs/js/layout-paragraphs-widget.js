(($, Drupal) => {
  /**
   * Sets the state of layout-paragraphs field to loading and adds loading indicator to element.
   * @param {jQuery} $element The jQuery object to set loading state for.
   */
  function setLoading($element) {
    $element
      .addClass("layout-paragraphs-loading")
      .prepend(
        '<div class="loading"><div class="spinner">Loading...</div></div>'
      )
      .closest(".layout-paragraphs-field")
      .data("isLoading", true);
  }
  /**
   * Sets the state of layout-paragraphs field to loaded and removes loading indicator.
   * @param {jQuery} $layoutParagraphsField The jQuery object to set loading state for.
   */
  function setLoaded($layoutParagraphsField) {
    $layoutParagraphsField
      .data("isLoading", false)
      .find(".layout-paragraphs-loading")
      .removeClass("layout-paragraphs-loading")
      .find(".loading")
      .remove();
    $layoutParagraphsField
      .find(".layout-paragraphs-add-more-menu")
      .addClass("hidden")
      .removeClass("fade-in")
      .appendTo($layoutParagraphsField);
  }
  /**
   * Returns true if the layout-paragraphsField is loading (i.e. waiting for an Ajax response.)
   * @param {jQuery} $layoutParagraphsField The layout-paragraphs jQuery DOM object.
   * @return {bool} True if state is loading.
   */
  function isLoading($layoutParagraphsField) {
    return $layoutParagraphsField.data("isLoading");
  }
  /**
   * Returns the region name closest to $el.
   * @param {jQuery} $el The jQuery element.
   * @return {string} The name of the region.
   */
  function getRegion($el) {
    const regEx = /layout-paragraphs-layout-region--([a-z0-9A-Z_]*)/;
    const $container = $el.is(".layout-paragraphs-layout-region")
      ? $el
      : $el.parents(".layout-paragraphs-layout-region");
    let regionName;
    if ($container.length) {
      const matches = $container[0].className.match(regEx);
      if (matches && matches.length >= 2) {
        [, regionName] = matches;
      }
    } else if ($el.closest(".layout-paragraphs-disabled-items").length > 0) {
      regionName = "_disabled";
    }
    return regionName;
  }
  /**
   * Updates all field weights and region names based on current state of dom.
   * @param {jQuery} $container The jQuery layout-paragraphs Field container.
   */
  function updateFields($container) {
    // Set deltas:
    let delta = 0;
    $container.find(".layout-paragraphs-weight").each((index, item) => {
      if ($(item).hasClass("layout-paragraphs-weight")) {
        delta += 1;
      }
      $(item).val(`${delta}`);
    });
    $container.find("input.layout-paragraphs-region").each((index, item) => {
      $(item).val(getRegion($(item)));
    });
    $container.find(".layout-paragraphs-item").each((index, item) => {
      const $item = $(item);
      const $parentUuidInput = $item.find(".layout-paragraphs-parent-uuid");
      const parentUuid = $item
        .parent()
        .closest(".layout-paragraphs-layout")
        .find(".layout-paragraphs-uuid")
        .val();
      $parentUuidInput.val(parentUuid);
    });
  }
  /**
   * Hides the disabled container when there are no layout-paragraphs items.
   * @param {jQuery} $container The disabled items jQuery container.
   */
  function updateDisabled($container) {
    if (
      $container.find(
        ".layout-paragraphs-disabled-items .layout-paragraphs-item"
      ).length > 0
    ) {
      $container.find(".layout-paragraphs-disabled-items__description").hide();
    } else {
      $container.find(".layout-paragraphs-disabled-items__description").show();
    }
  }
  /**
   * Disables layout controls based on their position.
   * @param {jQuery} $container The jQuery field container object.
   */
  function updateLayoutControls($container) {
    $(".layout-up, .layout-down", $container).prop("disabled", false);
    $(
      ".layout-paragraphs-item:first-child > .layout-controls > .layout-up, .layout-paragraphs-item:last-child > .layout-controls > .layout-down",
      $container
    )
      .blur()
      .prop("disabled", true);
  }
  /**
   * Closes the "add paragraph item" menu.
   * @param {jQuery} $btn The clicked button.
   */
  function closeAddItemMenu($btn) {
    const $widget = $btn.parents(".layout-paragraphs-field");
    const $menu = $widget.find(".layout-paragraphs-add-more-menu");
    $menu.addClass("hidden").removeClass("fade-in");
    $btn.removeClass("active").blur();
    $menu.appendTo($widget);
  }
  /**
   * Responds to click outside of the menu.
   * @param {event} e DOM event (i.e. click)
   */
  function handleClickOutsideMenu(e) {
    if ($(e.target).closest(".layout-paragraphs-add-more-menu").length === 0) {
      const $btn = $(".layout-paragraphs-add-content__toggle.active");
      if ($btn.length) {
        closeAddItemMenu($btn);
        window.removeEventListener("click", handleClickOutsideMenu);
      }
    }
  }
  /**
   * Position the menu correctly.
   * @param {jQuery} $menu The menu jQuery DOM object.
   * @param {bool} keepOrientation If true, the menu will stay above/below no matter what.
   */
  function positionMenu($menu, keepOrientation) {
    const $btn = $menu.data("activeButton");
    // Move the menu to correct spot.
    const btnOffset = $btn.offset();
    const menuOffset = $menu.offset();
    const viewportTop = $(window).scrollTop();
    const viewportBottom = viewportTop + $(window).height();
    const menuWidth = $menu.outerWidth();
    const btnWidth = $btn.outerWidth();
    const btnHeight = $btn.height();
    const menuHeight = $menu.outerHeight();
    // Accounts for rotation by calculating distance between points on 45 degree rotated square.
    const left = Math.floor(
      btnOffset.left + Math.sqrt(btnWidth ** 2 * 2) / 2 - menuWidth / 2
    );

    // Default to positioning the menu beneath the button.
    let orientation = "beneath";
    let top = Math.floor(btnOffset.top + btnHeight + btnWidth / 2);

    // The menu is above the button, keep it that way.
    if (keepOrientation === true && menuOffset.top < btnOffset.top) {
      orientation = "above";
    }
    // The menu would go out of the viewport, so keep at top.
    if (top + menuHeight > viewportBottom) {
      orientation = "above";
    }
    // If the menu would run above the top of the page, put beneath. Accounting for the height of the admin toolbar(79px).
    if (top - 79 < menuHeight) {
      orientation = "beneath";
    }
    $menu
      .removeClass("above")
      .removeClass("beneath")
      .addClass(orientation);
    if (orientation === "above") {
      top = Math.floor(btnOffset.top - 5 - menuHeight);
    }

    $menu.removeClass("hidden").addClass("fade-in");
    $menu.offset({ top, left });
  }
  /**
   * Opens the "add pragraph item" menu.
   * @param {jQuery} $btn The button clicked to open the menu.
   * @param {object} widgetSettings The widget instance settings.
   */
  function openAddItemMenu($btn, widgetSettings) {
    const $widget = $btn.parents(".layout-paragraphs-field");
    const $targetInput = $widget.find(".dom-id");
    const $insertMethodInput = $widget.find(".insert-method");

    const $menu = $widget.find(".layout-paragraphs-add-more-menu");
    const region = $btn.attr("data-region");
    const depth = region ? $btn.parents(".layout-paragraphs-layout").length : 0;

    // Hide layout items if we're already at max depth.
    if (depth > widgetSettings.maxDepth) {
      $menu.find(".layout-paragraph").addClass("hidden");
    } else {
      $menu.find(".layout-paragraph").removeClass("hidden");
    }
    // Hide non-layout items if we're at zero depth and layouts are requried.
    if (widgetSettings.requireLayouts && depth === 0) {
      $menu
        .find(".layout-paragraphs-add-more-menu__item:not(.layout-paragraph)")
        .addClass("hidden");
    } else {
      $menu
        .find(".layout-paragraphs-add-more-menu__item:not(.layout-paragraph)")
        .removeClass("hidden");
    }
    // Hide search if fewer than 7 visible items.
    if (
      $menu.find(".layout-paragraphs-add-more-menu__item:not(.hidden)").length <
      7
    ) {
      $menu.find(".layout-paragraphs-add-more-menu__search").addClass("hidden");
    } else {
      $menu
        .find(".layout-paragraphs-add-more-menu__search")
        .removeClass("hidden");
    }
    $menu.data("activeButton", $btn);
    // Make other buttons inactive.
    $widget
      .find("button.layout-paragraphs-add-content__toggle")
      .removeClass("active");
    // Hide the menu, for transition effect.
    $menu.addClass("hidden").removeClass("fade-in");
    $menu.find('input[type="text"]').val("");
    $menu.find(".layout-paragraphs-add-more-menu__item").attr("style", "");
    $btn.addClass("active");

    // Sets the values in the form items
    // for where a new item should be inserted.
    $targetInput.val($btn.attr("data-target-id"));
    $insertMethodInput.val($btn.attr("data-method"));

    // Move the menu element to directly after the
    // clicked button to ensure correct tab order.
    $menu.insertAfter($btn);

    setTimeout(() => {
      positionMenu($menu);
      if (
        !$menu
          .find(".layout-paragraphs-add-more-menu__search")
          .hasClass("hidden")
      ) {
        $menu
          .find('.layout-paragraphs-add-more-menu__search input[type="text"]')
          .focus();
      } else {
        $menu
          .find("a")
          .first()
          .focus();
      }
    }, 100);
    window.addEventListener("click", handleClickOutsideMenu);
  }
  /**
   * Toggles the add paragraph items menu.
   * @param {jQuery} $btn The jQuery button.
   * @param {Object} widgetSettings The widget settings.
   * @return {boolean} Returns false to prevent button action.
   */
  function toggleAddItemMenu($btn, widgetSettings) {
    if ($btn.hasClass("active")) {
      closeAddItemMenu($btn);
    } else {
      openAddItemMenu($btn, widgetSettings);
    }
    return false;
  }
  /**
   * Returns a toggle button jQuery object.
   * @param {Number} weight The weight of the layout item.
   * @param {String} region The region name where we are adding an item.
   * @param {String} parentUuid The uuid of the container layout item.
   * @param {String} placement A description of where we are placing the new item.
   * @param {jQuery} $target The target item or sibling.
   * @param {String} method The jQuery method to use for adding the new item.
   * @param {Object} widgetSettings The widget's settings.
   * @return {jQuery} The jQuery button.
   */
  function toggleButton(
    weight,
    region,
    parentUuid,
    placement,
    $target,
    method,
    widgetSettings
  ) {
    const labelText = region
      ? Drupal.t("Add item to @region region ", { "@region": region }) +
        placement
      : Drupal.t("Add item ") + placement;
    const $label = $("<span>")
      .text(labelText)
      .addClass("visually-hidden");
    const $btn = $("<button>").addClass(
      "layout-paragraphs-add-content__toggle"
    );
    $btn.append($label);
    return $btn
      .clone()
      .attr({
        "data-weight": weight || -1,
        "data-region": region || "",
        "data-parent-uuid": parentUuid || "",
        "data-method": method,
        "data-target-id": $target.attr("id")
      })
      .data("target", $target)
      .click(e => toggleAddItemMenu($(e.target), widgetSettings));
  }
  /**
   * Removes all toggle buttons.
   * @param {jQuery} $container The jQuery widget object.
   */
  function removeToggleButtons($container) {
    $(".layout-paragraphs-add-content__toggle", $container).remove();
  }
  /**
   * Adds toggle buttons for creating new content.
   * @param {jQuery} $container The jQuery layout-paragraphs Field container.
   * @param {Object} widgetSettings The widget settings object.
   */
  function toggleButtons($container) {
    const widgetSettings = $container.data("widgetSettings");
    $(".layout-paragraphs-add-content__toggle", $container).remove();
    if (!widgetSettings || widgetSettings.isTranslating) {
      return;
    }
    // Add toggle buttons to empty regions.
    $(".layout-paragraphs-layout-region", $container).each((index, region) => {
      if ($(".layout-paragraphs-item", region).length === 0) {
        const $region = $(region);
        const $layoutContainer = $region.closest(".layout-paragraphs-item");
        const weight =
          Number($layoutContainer.find("> .layout-paragraphs-weight").val()) +
          0.5;
        const parentUuid = $layoutContainer
          .find("> .layout-paragraphs-uuid")
          .val();
        $region.append(
          toggleButton(
            weight,
            getRegion($region),
            parentUuid,
            Drupal.t("inside item @weight", { "@weight": Math.floor(weight) }),
            $region,
            "append",
            widgetSettings
          )
        );
      }
    });
    // Add toggle buttons to top and bottom of each paragraph item.
    $(".layout-paragraphs-item", $container).each((index, paragraph) => {
      const $paragraph = $(paragraph);
      const weight = Number(
        $paragraph.find("> .layout-paragraphs-weight").val()
      );
      const region = getRegion($paragraph);
      const parentUuid = $paragraph
        .find("> .layout-paragraphs-parent-uuid")
        .val();
      $paragraph.prepend(
        toggleButton(
          weight - 0.5,
          region,
          parentUuid,
          Drupal.t("before item @weight", { "@weight": weight }),
          $paragraph,
          "before",
          widgetSettings
        )
      );
      $paragraph.append(
        toggleButton(
          weight + 0.5,
          region,
          parentUuid,
          Drupal.t("after item @weight", { "@weight": weight }),
          $paragraph,
          "after",
          widgetSettings
        )
      );
    });
    // Add toggle button if there are no paragraph items.
    if ($(".active-items .layout-paragraphs-item", $container).length === 0) {
      $(".active-items", $container).append(
        toggleButton(
          0,
          "",
          "",
          "",
          $container.find(".active-items"),
          "append",
          widgetSettings
        )
      );
    }
    if ($container.data("focusedElement")) {
      $(`#${$container.data("focusedElement")}`)
        .find("button:first")
        .focus();
    }
  }
  /**
   * Runs all necessary updates to widget.
   * @param {jQuery} $widget The jQuery widget item.
   */
  function updateWidget($widget) {
    toggleButtons($widget);
    updateFields($widget);
    updateDisabled($widget);
    updateLayoutControls($widget);
  }
  /**
   * An array reducer to massage the list of posible move positions when an item is moving down.
   * Layout containers are moved to *after* their contents to correctly order the available DOM positions.
   * @param {Array} results The results to return.
   * @param {Array} item The current item.
   * @return {Object} The massaged results.
   */
  function massagePositions(results, item) {
    const level = $(item).parents(".layout-paragraphs-layout").length;
    if (level < results.level) {
      results.positions.push(results.layouts.pop());
    }
    if ($(item).is(".layout-paragraphs-layout") && item !== results.item) {
      // We need to add layout containers *after* their contents.
      results.layouts.push(item);
    } else {
      // All other items just get added to the list.
      // The item being moved always gets added to the list.
      results.positions.push(item);
    }
    results.level = level;
    return results;
  }
  /**
   * Return the direction to move based on what key was pressed.
   * @param {String} keycode The keycode that was pressed.
   * @return {String} Returns the move direction: up, down, or stop.
   */
  function getMoveDirection(keycode) {
    switch (keycode) {
      case 37:
      case 38:
        return "up";
      case 39:
      case 40:
        return "down";
      default:
        return "stop";
    }
  }
  /**
   * Stops keyboard movement, unbinds the move event and restores UI to main state.
   * @param {jQuery} $widget The widget object.
   * @param {function} func The event handler to unbind.
   */
  function stopMove($widget, func) {
    $(document).unbind("keydown", func);
    $widget
      .removeClass("is-moving")
      .find(".is-moving")
      .removeClass("is-moving");
    $widget.find(".layout-controls, .layout-paragraphs-actions").show();
    updateWidget($widget);
  }
  /**
   * Event handler for keyboard movement.
   * @param {Event} e The keydown event.
   * @return {Boolean} Returns false to prevent further event propogation.
   */
  function move(e) {
    const { $moveItem, $widget, widgetSettings } = e.data;
    const spacer =
      '<div class="layout-paragraphs-item js-layout-paragraphs-temp js-hide"></div>';
    $widget.find(".js-layout-paragraphs-temp").remove();
    $widget.find(".layout-paragraphs-moving-message").remove();
    const dir = getMoveDirection(e.keyCode);
    if (dir === "stop") {
      stopMove($widget, move);
      return false;
    }
    // Add spacers into regions, appending or prepending based on direction we are moving.
    $widget
      .find(
        ".layout-paragraphs-layout-region, .active-items, .layout-paragraphs-disabled-items__items"
      )
      .each((index, item) => {
        $(item)[dir === "up" ? "append" : "prepend"](spacer);
      });
    // Build a list of all possible positions, excluding children of the item being moved.
    const positions = $(".layout-paragraphs-item", $widget)
      .toArray()
      .filter(i => !$.contains($moveItem[0], i));
    // If moving down, the positions need to be reordered
    // to adjust the position of layouts.
    const reorderedPositions =
      dir === "down"
        ? positions.reduce(massagePositions, {
            positions: [],
            layouts: [],
            level: 0,
            item: $moveItem[0]
          }).positions
        : positions;
    // Get the position (index) of the current item we are moving.
    const pos = reorderedPositions.findIndex(el => el === $moveItem[0]);
    // Loop through all possible positons, attempting to move to the next in line.
    // If a move if not valid, we continue looping until we reach a valid move.
    // Usually this results in simply bumping one position forward/backward.
    for (
      let i = pos + (dir === "up" ? -1 : 1);
      reorderedPositions[i] !== undefined;
      i += dir === "up" ? -1 : 1
    ) {
      const $next = $(reorderedPositions[i]);
      const method = dir === "up" ? "before" : "after";
      let valid = true;
      // Check for compliance with widget settings.
      if (
        widgetSettings.requireLayouts &&
        !$moveItem.is(".layout-paragraphs-layout") &&
        $next.closest(".layout-paragraphs-disabled-items__items").length ===
          0 &&
        $next.closest(".layout-paragraphs-layout-region").length === 0
      ) {
        valid = false;
      }
      if (
        $moveItem.is(".layout-paragraphs-layout") &&
        $next.parents(".layout-paragraphs-layout").length >
          widgetSettings.maxDepth
      ) {
        valid = false;
      }
      if (valid) {
        $next[method]($moveItem);
        const rect = $moveItem[0].getBoundingClientRect();
        if (rect.y + rect.height < 0 || rect.y > window.innerHeight) {
          $moveItem[0].scrollIntoView();
        }
        updateFields($widget);
        updateDisabled($widget);
        return false;
      }
    }
  }
  /**
   * Event handler for starting keyboard movement.
   * @param {Event} e The button press event.
   * @return {Boolean} Returns false to prevent further event propogation.
   */
  function startMove(e) {
    const $moveItem = $(e.currentTarget).closest(".layout-paragraphs-item");
    const $widget = $moveItem.closest(".layout-paragraphs-field");
    $(e.currentTarget)
      .parent(".layout-controls")
      .after(
        $(
          `<div class="layout-paragraphs-moving-message">${Drupal.t(
            "Use arrow keys to move, any other key to stop."
          )}</div>`
        )
      );
    removeToggleButtons($widget);
    $widget.find(".layout-controls, .layout-paragraphs-actions").hide();
    $moveItem.addClass("is-moving");
    $widget.addClass("is-moving");
    $(document).bind(
      "keydown",
      { $moveItem, $widget, widgetSettings: $widget.data("widgetSettings") },
      move
    );
    return false;
  }
  /**
   * Moves an layout-paragraphs item up.
   * @param {event} e DOM Event (i.e. click).
   * @return {bool} Returns false if state is still loading.
   */
  function moveUp(e) {
    const $btn = $(e.currentTarget);
    const $item = $btn.parents(".layout-paragraphs-item:first");
    const $container = $item.parent();
    const $widget = $btn.closest(".layout-paragraphs-field");

    if (isLoading($item)) {
      return false;
    }

    // We're first, jump up to next available region.
    if ($item.prev(".layout-paragraphs-item").length === 0) {
      // Previous region, same layout.
      if ($container.prev(".layout-paragraphs-layout-region").length) {
        $container.prev(".layout-paragraphs-layout-region").append($item);
      }
      // Otherwise jump to last region in previous layout.
      else if (
        $container
          .closest(".layout-paragraphs-layout")
          .prev()
          .find(".layout-paragraphs-layout-region:last-child").length
      ) {
        $container
          .closest(".layout-paragraphs-layout")
          .prev()
          .find(
            ".layout-paragraphs-layout-region:last-child .layout-paragraphs-add-content__container"
          )
          .before($item);
      }
    } else {
      $item.after($item.prev());
    }
    updateWidget($widget);
    return false;
  }
  /**
   * Moves an layout-paragraphs item down.
   * @param {event} e DOM Event (i.e. click).
   * @return {bool} Returns false if state is still loading.
   */
  function moveDown(e) {
    const $btn = $(e.currentTarget);
    const $item = $btn.parents(".layout-paragraphs-item:first");
    const $container = $item.parent();
    const $widget = $btn.closest(".layout-paragraphs-field");

    if (isLoading($item)) {
      return false;
    }

    // We're first, jump down to next available region.
    if ($item.next(".layout-paragraphs-item").length === 0) {
      // Next region, same layout.
      if ($container.next(".layout-paragraphs-layout-region").length) {
        $container.next(".layout-paragraphs-layout-region").prepend($item);
      }
      // Otherwise jump to first region in next layout.
      else if (
        $container
          .closest(".layout-paragraphs-layout")
          .next()
          .find(".layout-paragraphs-layout-region:first-child").length
      ) {
        $container
          .closest(".layout-paragraphs-layout")
          .next()
          .find(
            ".layout-paragraphs-layout-region:first-child .layout-paragraphs-add-content__container"
          )
          .before($item);
      }
    } else {
      $item.before($item.next());
    }
    updateWidget($widget);
    return false;
  }
  /**
   * Initiates dragula drag/drop functionality.
   * @param {object} $widget ERL field item to attach drag/drop behavior to.
   * @param {object} widgetSettings The widget instance settings.
   */
  function dragulaBehaviors($widget, widgetSettings) {
    $widget.addClass("dragula-enabled");
    // Turn on drag and drop if dragula function exists.
    if (typeof dragula !== "undefined") {
      const items = $(
        ".active-items, .layout-paragraphs-layout-region, .layout-paragraphs-disabled-items__items",
        $widget
      )
        .not(".dragula-enabled")
        .addClass("dragula-enabled")
        .get();

      // Dragula is already initialized, add any new containers that may have been added.
      if ($widget.data("drake")) {
        Object.values(items).forEach(item => {
          if ($widget.data("drake").containers.indexOf(item) === -1) {
            $widget.data("drake").containers.push(item);
          }
        });
        return;
      }
      const drake = dragula(items, {
        moves(el, container, handle) {
          return handle.className.toString().indexOf("layout-handle") >= 0;
        },
        accepts(el, target, source, sibling) {
          if (widgetSettings.requireLayouts) {
            if (
              !$(el).is(".layout-paragraphs-layout") &&
              !$(target).parents(".layout-paragraphs-layout").length &&
              !$(target).parents(".layout-paragraphs-disabled-items").length
            ) {
              return false;
            }
          }
          if (
            $(el).is(".layout-paragraphs-layout") &&
            $(target).parents(".layout-paragraphs-layout").length >
              widgetSettings.maxDepth
          ) {
            return false;
          }
          if ($(target).parents(".layout-paragraphs-disabled-items").length) {
            if (
              $(sibling).is(".layout-paragraphs-disabled-items__description")
            ) {
              return false;
            }
          }
          return true;
        }
      });
      drake.on("drop", el => {
        updateWidget($(el).closest(".layout-paragraphs-field"));
      });
      drake.on("drag", el => {
        removeToggleButtons($(el).closest(".layout-paragraphs-field"));
      });
      drake.on("dragend", el => {
        $(el)
          .closest(".layout-paragraphs-field")
          .data("focusedElement", null);
        $(el).focus();
        toggleButtons(
          $(el).closest(".layout-paragraphs-field"),
          widgetSettings
        );
      });
      $widget.data("drake", drake);
    }
  }
  /**
   * Ajax Command to set state to loaded.
   * @param {object} ajax The ajax object.
   * @param {object} response The response object.
   */
  Drupal.AjaxCommands.prototype.resetLayoutParagraphsState = (
    ajax,
    response
  ) => {
    setLoaded($(response.data.id));
  };
  /**
   * Ajax Command to insert or update a paragraph element.
   * @param {object} ajax The ajax object.
   * @param {object} response The response object.
   */
  Drupal.AjaxCommands.prototype.layoutParagraphsInsert = (ajax, response) => {
    const { settings, content } = response;
    const $content = $(
      ".layout-paragraphs-item",
      `<div>${content}</div>`
    ).first();
    if ($(`#${settings.element_id}`).length) {
      $(`#${settings.element_id}`).replaceWith($content);
    } else if (settings.target_id && settings.insert_method) {
      $(`#${settings.target_id}`)[settings.insert_method]($content);
    }
    $content
      .closest(".layout-paragraphs-field")
      .data("focusedElement", $content.attr("id"));
  };
  /**
   * The main layout-paragraphs Widget behavior.
   */
  Drupal.behaviors.layoutParagraphsWidget = {
    attach: function attach(context, settings) {
      const widgetInstanceNames = Object.keys(settings.layoutParagraphsWidgets);
      widgetInstanceNames.forEach(widgetInstanceName => {
        const $widget = $(`#${widgetInstanceName}`);
        const widgetSettings =
          settings.layoutParagraphsWidgets[widgetInstanceName];
        Drupal.layoutParagraphsWidget($widget, widgetSettings);
      });
    }
  };
  Drupal.layoutParagraphsWidget = ($widget, widgetSettings) => {
    $widget.data("widgetSettings", widgetSettings);
    /**
     * Hide all "add paragraph item" buttons if we have reached cardinality.
     */
    if (
      widgetSettings.cardinality > -1 &&
      widgetSettings.itemsCount >= widgetSettings.cardinality
    ) {
      $("button.layout-paragraphs-add-content__toggle", $widget).addClass(
        "hidden"
      );
    } else {
      $("button.layout-paragraphs-add-content__toggle", $widget).removeClass(
        "hidden"
      );
    }
    /**
     * Click handler for "add paragraph item" toggle buttons.
     */
    $("button.layout-paragraphs-add-content__toggle", $widget)
      .once("layout-paragraphs-add-content-toggle")
      .click(e => {
        const $btn = $(e.target);
        if ($btn.hasClass("active")) {
          closeAddItemMenu($btn);
        } else {
          openAddItemMenu($btn, widgetSettings);
        }
        return false;
      });
    /**
     * Set state to "loading" on layout-paragraphs field when action buttons are pressed.
     */
    $('.layout-paragraphs-actions input[type="submit"]')
      .once("layout-paragraphs-actions-loaders")
      .each((index, btn) => {
        $(btn).on("mousedown", e => {
          if (isLoading($(btn).closest(".layout-paragraphs-field"))) {
            e.stopImmediatePropagation();
            return false;
          }
          setLoading($(e.currentTarget).closest(".layout-paragraphs-item"));
        });
        // Ensure our listener happens first.
        $._data(btn, "events").mousedown.reverse();
      });
    /**
     * Click handlers for adding new paragraph items.
     */
    $(".layout-paragraphs-add-more-menu__item a", $widget)
      .once("layout-paragraphs-add-more-menu-buttons")
      .click(e => {
        const $btn = $(e.currentTarget);
        const $menu = $btn.closest(".layout-paragraphs-add-more-menu");
        const $select = $widget.find("select.layout-paragraphs-item-type");
        const $submit = $widget.find(
          'input[type="submit"].layout-paragraphs-add-item'
        );
        const type = $btn.attr("data-type");
        if (isLoading($widget)) {
          return false;
        }
        $select.val(type);
        $submit.trigger("mousedown").trigger("click");
        setLoading($menu);
        return false;
      });
    /**
     * Search behavior for search box on "add paragraph item" menu.
     */
    $(".layout-paragraphs-add-more-menu__search", $widget)
      .once("layout-paragraphs-search-input")
      .each((index, searchContainer) => {
        const $searchContainer = $(searchContainer);
        const $searchInput = $searchContainer.find('input[type="text"]');
        const $menu = $searchContainer.closest(
          ".layout-paragraphs-add-more-menu"
        );
        const $searchItems = $menu.find(
          ".layout-paragraphs-add-more-menu__item:not(.hidden)"
        );

        // Search query
        $searchInput.on("keyup", ev => {
          const text = ev.target.value;
          const pattern = new RegExp(text, "i");
          for (let i = 0; i < $searchItems.length; i++) {
            const item = $searchItems[i];
            if (pattern.test(item.innerText)) {
              item.removeAttribute("style");
            } else {
              item.style.display = "none";
            }
          }
          positionMenu($menu, true);
        });
      });
    /**
     * Add drag/drop/move controls.
     */
    $(".layout-paragraphs-item", $widget)
      .once("layout-paragraphs-controls")
      .each((layoutParagraphsItemIndex, layoutParagraphsItem) => {
        $('<div class="layout-controls">')
          .append(
            $('<button class="layout-handle">')
              .click(startMove)
              .text(
                Drupal.t(
                  "Use the arrow keys to move this item, any other key to stop."
                )
              )
          )
          .append(
            $(
              `<button class="layout-up">${Drupal.t("Move up")}</button>`
            ).click(moveUp)
          )
          .append(
            $(
              `<button class="layout-down">${Drupal.t("Move down")}</button>`
            ).click(moveDown)
          )
          .prependTo($(layoutParagraphsItem));
      });
    /**
     * Set state to loading when a radio is clicked.
     */
    $(".layout-select input[type=radio]", $widget).change(e => {
      setLoading($(e.target).closest(".layout-select"));
    });
    /**
     * Only show disabled items if there are items in the field.
     * Runs every time DOM is updated.
     */
    if ($(".layout-paragraphs-item", $widget).length === 0) {
      $(".layout-paragraphs-disabled-items", $widget).hide();
    } else {
      $(".layout-paragraphs-disabled-items", $widget).show();
    }
    /**
     * Update weights, regions, and disabled area on load.
     * Runs every time DOM is updated.
     */
    updateWidget($widget);
    /**
     * Dialog close buttons should trigger the "Cancel" action.
     */
    $(".layout-paragraphs-dialog .ui-dialog-titlebar-close", $widget).mousedown(
      e => {
        $(e.target)
          .closest(".layout-paragraphs-dialog")
          .find(".layout-paragraphs-cancel")
          .trigger("mousedown")
          .trigger("click");
        return false;
      }
    );
    /**
     * Drag and drop with dragula.
     * Runs every time DOM is updated.
     */
    const checkDragulaInterval = setInterval(() => {
      if (typeof dragula !== "undefined") {
        clearInterval(checkDragulaInterval);
        dragulaBehaviors($widget, widgetSettings);
      }
    }, 50);
  };

  // Enables interactions with anything outside of the current CKeditor dialog
  // @see Table Element issue https://www.drupal.org/project/layout_paragraphs/issues/3196477
  let orig_allowInteraction = $.ui.dialog.prototype._allowInteraction;
  $.ui.dialog.prototype._allowInteraction = function(event) {
    if ($(event.target).closest('.cke_dialog').length) {
      return true;
    }
    return orig_allowInteraction.apply(this, arguments);
  };

})(jQuery, Drupal);
