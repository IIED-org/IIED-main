jQuery(document).ready( function($){
  $(".mo_copy").click(function () {
    copyToClipboard('#'+$(this).prev().attr("id"));
  });

  $("#miniorange_2fa_save_config_btn").click(function () {
    getCountryCode();
  });

  $(".mo_copy_certificate").click(function () {
    copyCertificateToClipboard();
  });
});
function copyToClipboard(element) {
  jQuery(".mo-2fa-selected-text").removeClass("mo-2fa-selected-text");
  var temp = jQuery("<input>");
  jQuery("body").append(temp);
  jQuery(element).addClass("mo-2fa-selected-text");
  temp.val(jQuery(element).text().trim()).select();
  document.execCommand("copy");
  temp.remove();
}
jQuery(window).click(function(e) {
  if( e.target.className == undefined || e.target.className.indexOf("mo_copy") == -1)
    jQuery(".mo-2fa-selected-text").removeClass("mo-2fa-selected-text");

});

/* This function is used to get the country code on the Login settings tab*/
function getCountryCode() {
  var selectedDialCode = document.querySelector('.iti__selected-dial-code');
  var phoneFullInput = document.querySelector("input[name='auto_fetch_phone_number_country_code']");
  if (phoneFullInput) {
    phoneFullInput.value = selectedDialCode.textContent;
  }
}
