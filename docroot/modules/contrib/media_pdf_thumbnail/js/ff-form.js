(function ($, Drupal, once) {
  Drupal.behaviors.mediaPdfThumbnailFomBehavior = {
    attach: function (context, settings) {
      attributesToggle();
      $('.thumbnail-pdf-link').once().change(function () {
        attributesToggle();
      });
    }
  };

  function attributesToggle() {
    $('.thumbnail-pdf-link').each(function() {
      var bundle = $(this).attr('data-bundle-link');
      var attributes = bundle ? $('div[data-bundle-attributes="' + bundle + '"]') : $('#thumbnail-pdf-link-attributes');
      if ($(this).val() == 'pdf_file') {
        attributes.show();
      }
      else {
        attributes.hide();
      }
    });
  }

})(jQuery, Drupal);
