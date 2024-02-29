/*
International Telephone Input
version: v18.1.8
description: A jQuery plugin for entering international telephone numbers
repository: https://github.com/jackocnr/intl-tel-input.git
license: MIT
author: Jack O'Connor (http://jackocnr.com)
*/

/* Do not remove commented code below, these are the configurable options for telephone input
   Detailed uses if this javaScript is explained in the configure_otp_over_sms.inc file for $form['miniorange_phone']
*/
(function($, Drupal, once) {
  Drupal.behaviors.miniOrange_flags = {
    attach: function (context, settings) {
      {
        once('miniOrange_flags', '#query_phone', context).forEach(
          function (elememt) {
            var input = document.querySelector("#query_phone");

            window.intlTelInput(input,  {
              geoIpLookup: function(callback) {
                fetch("https://ipapi.co/json")
                  .then(function(res) { return res.json(); })
                  .then(function(data) { callback(data.country_code); })
                  .catch(function() { callback("us"); });
              },
              customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                return "Enter your phone number";
              },
              initialCountry: "auto",
              preferredCountries: ['us', 'gb', 'au', 'in'],
              separateDialCode: true,
              nationalMode: false,
              utilsScript: "utils.js",
              // hiddenInput: "full",
              // allowDropdown: false,
              // autoInsertDialCode: true,
              // autoPlaceholder: "off",
              // dropdownContainer: document.body,
              // excludeCountries: ["us"],
              // formatOnDisplay: false,
              // localizedCountries: { 'de': 'Deutschland' },
              // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
              // placeholderNumberType: "MOBILE",
              // showFlags: false,
            });

            var iti = window.intlTelInputGlobals.getInstance(input);

            /* Below code is used to get full phone numbner with country code in hidden form - phone_full*/
            document.getElementById("query_phone").addEventListener("blur", function() {
              var selectedDialCode = document.querySelector('.iti__selected-dial-code');
              var phoneFullInput   = document.querySelector("input[name='phone_full']");

              if (phoneFullInput) {
                phoneFullInput.value = iti.getNumber();
              }
            });
          }
        )
      }
    }
  };
}(jQuery, Drupal, once));
