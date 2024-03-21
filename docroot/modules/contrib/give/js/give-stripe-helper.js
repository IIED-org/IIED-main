/**
 * @file
 *   Javascript for the Give module for interacting with Stripe.js.
 */
(function (Drupal, settings, $) {
  loadStripe();

  function loadStripe() {
    if (!settings.give.stripe_publishable_key) {
      // "card" is the value of the constant PaymentMethod::GIVE_VIA_STRIPE.
      $('#edit-method-card').attr('disabled', true);
      $('label[for="edit-method-card"]').append('<div class="form--inline-feedback form--inline-feedback--error visible">This form cannot take credit/debit card payments until the Stripe publishable key is set by the site administrator.</div>');
      logProblem('Misconfiguration', 'Administrator must set a Stripe publishable key to use Stripe.');
      return;
    }
    if (typeof(Stripe) === 'undefined') {
      // "card" is the value of the constant PaymentMethod::GIVE_VIA_STRIPE.
      $('#edit-method-card').attr('disabled', true);
      $('label[for="edit-method-card"]').append('<div class="error messages--error form--inline-feedback form--inline-feedback--error visible">Your browser appears to be blocking Stripe.com, which must be enabled for us to process debit or credit card donations.  Please check any tracker blockers such as Privacy Badger or uBlock Origin and be sure to allow js.stripe.com.  Then reload this page and allow all additional stripe.com domains which request connection.</div>');
      logProblem('Stripe blocked', 'Stripe did not load; showed user error message with mention of tracker blockers.');
      return;
    }
    try {
      var stripe = Stripe(settings.give.stripe_publishable_key);
    }
    catch (e) {
      $('#edit-method-card').attr('disabled', true);
      $('label[for="edit-method-card"]').append('<div class="error messages--error form--inline-feedback form--inline-feedback--error visible">' + e.message + '</div>');
      logProblem('Stripe error:', e.message);
      return;
    }
    var elements = stripe.elements();
    var card = elements.create('card', {
      hidePostalCode: true,
      style: {
        base: {
          iconColor: 'red',
          color: 'green',
          lineHeight: '2em',
          fontWeight: 400,
          fontFamily: '"Helvetica Neue", "Helvetica", sans-serif',
          fontSize: '15px',
          '::placeholder': {
            color: '#ccc',
          }
        },
      }
    });
    card.mount('#stripe-card-element');
    card.on('change', function(event) {
      handleResponse(event);
    });

    // Wrap in a try / catch because there's some fear older browsers could break on this.
    try {
      document.addEventListener('invalid', function(e) {
        var message = e.target.validationMessage;
        var name = e.target.name;
        logProblem('Form validation', 'Donor told "' + message + '" for input ' + name);
      }, true);
    }
    catch (e) {
      var message = 'Undefined exception.';
      if (typeof e != "undefined") {
        if (typeof e == "string") {
          message = e;
        }
        else if (typeof e.message == "string") {
          message = e.message;
        }
      }
      logProblem('Problem logging form validation', 'Browser (likely IE11) lacks event listening: ' + message);
    }

    document.querySelector('.give-donation-form').addEventListener('submit', function(e) {
      // Only try to process the card if card method ('card') is selected.
      if ($('input[name=method]:checked').val() == 'card') {
        e.preventDefault();
        var form = document.querySelector('.give-donation-form');
        var extraDetails = {
          name: form.querySelector('input[name=donor_name_for_stripe]').value
        };
        var address_zip = form.querySelector('input[name=address_zip]');
        if (address_zip) {
          extraDetails.address_zip = address_zip.value;
        }
        createToken(stripe, card, extraDetails, 3);
      }
    });
  }

  /**
   * Try to create the token "n" times before to fail.
   */
  function createToken(stripe, card, extraDetails, n) {
    return stripe.createToken(card, extraDetails)
      .then(handleResponse)
      .catch(function (error) {
        if (n === 1) {
          // Execute handleResponse with an error.
          var result = {
            token: null,
            error: {
              message: 'There was a problem contacting the payment gateway please try again later.'
            }
          };
          handleResponse(result);
          logProblem('Stripe is not available');
          throw error;
        }
        createToken(stripe, card, extraDetails, n - 1);
      });
  };

  function handleResponse(result) {
    var successElement = document.querySelector('#stripe-card-success');
    var errorElement = document.querySelector('#stripe-card-errors');
    successElement.classList.remove('visible');
    errorElement.classList.remove('visible');

    if (result.token) {
      // Prevent duplicate submissions by double-clickers.
      document.getElementById('edit-submit').disabled = true;
      // Insert the token ID into the form so it gets submitted to the server.
      var form = document.querySelector('.give-donation-form');
      form.querySelector('input[name=stripe_token]').value = result.token.id;
      // Submit the form, completing the action the user expected after pressing submit.
      form.submit();
    } else if (result.error) {
      errorElement.textContent = result.error.message;
      errorElement.classList.add('visible');
      logProblem('Stripe returned error', result.error.message);
    }
  }

  function logProblem(type, detail) {
    $.ajax({
      type: 'POST',
      cache: false,
      url: drupalSettings.give.problem_log.url,
      data: {
        'donation_uuid': drupalSettings.give.problem_log.donation_uuid,
        'type': type,
        'detail': detail,
        'user_agent': navigator.userAgent
      }
    });
  }

})(Drupal, drupalSettings, jQuery);
