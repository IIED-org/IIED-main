/**
 * @file
 * Javascript file for Miniorange Session Manager - autologout.
 */

(function ($, Drupal, Cookies) {

  Drupal.behaviors.sessionManager = {
    attach: function (context, settings) {
      if (context !== document) {
        return;
      }

      let idleCheckInterval;
      let clonedSettings = $.extend(true, {}, settings.session_management);
      let logoutDialog;
      let logoutConfirmationTimer;
      let dialogCloseChecker;

      // Remove the 'logout_from_all_tabs' flag from local storage if present.
      localStorage.removeItem('Drupal.session.logoutFromAllTabs');

      // Bind events to reset idle time.
      $('body').on('mousemove keydown scroll mouseup', resetIdleTime);

      // Check if the logout dialog is already active.
      let isDialogActive = localStorage.getItem('Drupal.session.logoutDialogActive');
      if (isDialogActive === 'true') {
        logoutDialog = createLogoutDialog();
      }
      else {
        resetIdleTime();
        // Check for idle time status every 2 seconds.
        idleCheckInterval = setInterval(checkIdleTime, 2000);
      }

      function checkIdleTime() {
        let lastActiveTime = localStorage.getItem('Drupal.session.lastActiveTime');
        let isIdle = lastActiveTime && (Date.now() - lastActiveTime) >= clonedSettings.autologout_timeout * 1000;

        if (isIdle) {
          clearInterval(idleCheckInterval);
          logoutDialog = createLogoutDialog();
        }
      }

      function resetIdleTime() {
        let isDialogActive = localStorage.getItem('Drupal.session.logoutDialogActive');
        if (isDialogActive !== 'true') {
          localStorage.setItem('Drupal.session.lastActiveTime', Date.now());
        }
      }

      function createLogoutDialog() {
        // Set the variable in local storage indicating dialog popup.
        localStorage.setItem('Drupal.session.logoutDialogActive', 'true');

        let buttons = {};
        buttons[Drupal.t(clonedSettings.mo_modal_yes_button_text)] = function () {
          $(this).dialog('destroy');
          continueSession();
        };
        buttons[Drupal.t(clonedSettings.mo_modal_no_button_text)] = function () {
          $(this).dialog('destroy');
          logout();
        };

        // Add timer to check popup close conditions.
        dialogCloseChecker = setInterval(checkDialogCloseConditions, 500);

        // Add the timer for user response.
        logoutConfirmationTimer = setTimeout(confirmLogout, clonedSettings.autologout_response_time * 1000);

        return $('<div id="autoLogoutConfirm">' + clonedSettings.mo_modal_message + '</div>').dialog({
          modal: true,
          closeOnEscape: false,
          width: clonedSettings.mo_modal_width,
          title: clonedSettings.mo_modal_title,
          buttons: buttons,
          close: logout
        });
      }

      function logout() {
        // Clear local storage items.
        localStorage.removeItem('Drupal.session.lastActiveTime');
        localStorage.removeItem('Drupal.session.logoutDialogActive');
        localStorage.setItem('Drupal.session.logoutFromAllTabs', 'true');

        // Set a short-lived cookie to trigger logout across tabs.
        let expiryTime = new Date();
        expiryTime.setSeconds(expiryTime.getSeconds() + 5);
        document.cookie = 'sessionLogout=true; expires=' + expiryTime.toUTCString() + '; path=/';

        $.ajax({
          url: Drupal.url('session_management/logout'),
          type: 'GET',
          success: function () {
            window.location.href = Drupal.url('user/login');
          },
          error: function (XMLHttpRequest, textStatus) {
            if (XMLHttpRequest.status === 403 || XMLHttpRequest.status === 404) {
              window.location.href = Drupal.url('user/login');
            }
          }
        });
      }

      function continueSession() {
        clearInterval(dialogCloseChecker);
        clearTimeout(logoutConfirmationTimer);

        localStorage.removeItem('Drupal.session.logoutDialogActive');
        localStorage.setItem('Drupal.session.lastActiveTime', Date.now());

        // Continue checking for idle time.
        idleCheckInterval = setInterval(checkIdleTime, 2000);
      }

      function confirmLogout() {
        $(logoutDialog).dialog('destroy');

        let isDialogActive = localStorage.getItem('Drupal.session.logoutDialogActive');
        if (isDialogActive === 'true') {
          logout();
        }
      }

      function checkDialogCloseConditions() {
        let isDialogActive = localStorage.getItem('Drupal.session.logoutDialogActive');
        let logoutFromAllTabs = localStorage.getItem('Drupal.session.logoutFromAllTabs');

        if (logoutFromAllTabs === 'true') {
          logout();
        }

        if (isDialogActive !== 'true') {
          clearTimeout(logoutConfirmationTimer);
          clearInterval(dialogCloseChecker);
          $(logoutDialog).dialog('destroy');
          idleCheckInterval = setInterval(checkIdleTime, 2000);
        }
      }
    }
  };

})(jQuery, Drupal, window.Cookies);
