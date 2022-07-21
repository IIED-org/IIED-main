(function ($, Drupal) {
  // Tabs JS

  // toggle the visibility of tabs
  function showTab(e, target = $(this)) {
    e.preventDefault();
    // Find the target panel.
    var targetContent = $(target.attr('href'));
    // Hide all panels and update aria attributes.
    targetContent.siblings().hide().removeClass('active').attr('aria-hidden', true).attr('aria-expanded', false);
    // Show the target panel and update aria attributes.
    targetContent.show().addClass('active').attr('aria-hidden', false).attr('aria-expanded', true);
    // Deselect all tabs.
    target.parent().siblings().find('a').removeClass('is-active').attr("aria-selected", false).attr('tabindex', "-1");
    // Select the active tab.
    target.addClass('is-active').attr("aria-selected", true).attr('tabindex', "0").focus();
  }

  // Attach roles and other accessibility-related attributes.
  $('.layout-tabs').attr('role', 'tablist');
  $('.layout-tabs > li.tabs__tab').attr('role', 'presentation');
  $('.layout-tabs > li.tabs__tab > a.nav-link').attr('role', 'tab').each(function() {
    var thisId = $(this).attr('id');
    $(this).attr('aria-controls', thisId.replace('tab', 'panel'));
    var isActive = $(this).hasClass('is-active');
    if (isActive) {
      $(this).attr('aria-selected', 'true');
    }
    else {
      $(this).attr('aria-selected', 'false');
      $(this).attr('tabindex', '-1');
    }
    $(this).addClass('tabs__link');
  });
  $('.tab-content .tab-pane').attr('role', 'tabpanel').each(function() {
    var thisId = $(this).attr('id');
    $(this).attr('aria-labelledby', thisId.replace('panel', 'tab'));
    var isActive = $(this).hasClass('active');
    if (isActive) {
      $(this).attr('aria-hidden', 'false');
      $(this).attr('aria-expanded', 'true');
    }
    else {
      $(this).attr('aria-hidden', 'true');
      $(this).attr('aria-expanded', 'false');
    }
  });

  // event handlers to toggle accordions
  $('.layout-tabs a').on('click', showTab);
  $('.layout-tabs a').on('keyup', function(e) {
    if (e.which == 37) {
      // show previous
      if ($(this).parent().is(':first-child')) {
        // select the last
        showTab(e, $(this).parent().siblings(':last-child').children('a'));
      }
      else {
        // select the previous
        showTab(e, $(this).parent().prev().children('a'));
      }
    }
    else if (e.which == 39) {
      // show next
      if ($(this).parent().is(':last-child')) {
        // select the first
        showTab(e, $(this).parent().siblings(':first-child').children('a'));
      }
      else {
        // select the previous
        showTab(e, $(this).parent().next().children('a'));
      }
    }
  });
  $('.tab-content .tab-pane').hide();
  $('.tab-content .tab-pane.active').show();

})(jQuery, Drupal);
