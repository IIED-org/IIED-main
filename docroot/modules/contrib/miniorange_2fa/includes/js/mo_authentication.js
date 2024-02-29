/**
 * @file JS file to perform authentication and registration for miniOrange
 *       Authentication service.
 */
(function($, Drupal) {
  Drupal.attachBehaviors(document, Drupal.settings);
    var form_names = [
        'mo_auth_miniorange_authenticate',
        'mo_auth_configure_method',
        'mo_auth_inline_registration',
        'mo_auth_configure_admin_2fa',
        'admin_2fa',
    ];

  Drupal.behaviors.miniOrange2fa = {
    attach: function (context, settings) {

      var formIds   = document.getElementsByName("form_id");
      var txId_data = document.getElementsByName("txId");
      var url_data  = document.getElementsByName("url");

      for (var i = 0; i < formIds.length; i++) {
        if ($.inArray(formIds[i].value, form_names) != -1) {
          var str  = formIds[i].value;
          var txId = txId_data[0].value;
          var url  = url_data[0].value;
          str      = str.replace(/_/g, "-");
          getAuthStatus(str, txId, url);
        }
      }
    }

  };


  function getAuthStatus(formId, txId_value, url_value) {
      var txId = txId_value;
      var jsonString = "{\"txId\":\"" + txId + "\"}";
      var url = url_value;

      $.ajax({
      url : url,
      type : "POST",
      dataType : "json",
      data : jsonString,
      contentType : "application/json; charset=utf-8",
      success : function(result) {
        var response = JSON.parse(JSON.stringify(result));

        if (response.status !== 'IN_PROGRESS') {
          try {
            document.getElementById(formId).submit();
          } catch (e) {
            document.getElementById(formId).click(); //submit() is not working in modal form and click() is not working in regular form, so both functions are added.
          }

        } else {
          setTimeout(getAuthStatus, 1000, formId, txId, url);
        }
      }
    });
  }
}(jQuery, Drupal));
