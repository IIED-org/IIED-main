(($, Drupal, once) => {
  Drupal.behaviors.password_policy_failed_messages_only = {
    attach(context) {
      once(
        'password_policy_failed_messages_only',
        '#password-policy-status table',
        context,
      ).forEach((element) => {
        // Accessibility cleanup and fixes.
        element.setAttribute('role', 'presentation');
        element.deleteTHead();
        $(element).find('tr.password-policy-constraint-passed').remove();
        $(element)
          .find('td:first-child, th:first-child, td:last-child, th:last-child')
          .remove();
        element.setAttribute('aria-live', 'polite');
      });
    },
  };
})(jQuery, Drupal, once);
