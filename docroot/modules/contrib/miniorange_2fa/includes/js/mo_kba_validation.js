(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.customKBAValidation = {
    attach: function (context, settings) {
      $(once('custom-kba-validation', '.custom-kba-validation', context)).on('input blur', function() {
        var param = $(this).attr('id');
        validateInput.call(this, param);
      });
    }
  };

  Drupal.behaviors.customKBAUniqueAnswer = {
    attach: function (context, settings) {
      $(once('custom-kba-unique-answer', '.custom-kba-validation', context)).on('blur', function () {
        var textfield1        = $('#kba-answer-1').val();
        var textfield2        = $('#kba-answer-2').val();
        var textfield3        = $('#kba-answer-3').val();
        var kba_answer_length = drupalSettings.miniorange_2fa.kba_answer_length;
        var inputValue        = $(this).val().trim();
        var pattern   = /^[\w\s]+$/;
        var isValid  = pattern.test(inputValue);
        var message    = 'Answers must be unique.';

        if(isValid && inputValue.length >= kba_answer_length) {
          if (textfield1 === textfield2 && textfield1 === textfield3 && textfield2 === textfield3) {
            $('#kba-answer-2')[0].setCustomValidity(Drupal.t(message));
          } else if (textfield1 === textfield2) {
            $('#kba-answer-2')[0].setCustomValidity(Drupal.t(message));
          } else if (textfield2 === textfield3) {
            $('#kba-answer-3')[0].setCustomValidity(Drupal.t(message));
          } else if (textfield1 === textfield3) {
            $('#kba-answer-3')[0].setCustomValidity(Drupal.t(message));
          } else {
            $('#kba-answer-1')[0].setCustomValidity('')
            $('#kba-answer-2')[0].setCustomValidity('')
            $('#kba-answer-3')[0].setCustomValidity('')
          }
        }
      });
    }};

  function validateInput(textfield){
      var kba_answer_length = drupalSettings.miniorange_2fa.kba_answer_length;
      var answer_textfield  = $('#' + textfield)[0];
      var inputValue        = $('#' + textfield).val().trim();
      var pattern   = /^[\w\s]+$/

      if(inputValue.length >= 1){
        if (inputValue.length < kba_answer_length) {
          if(!pattern.test(inputValue)) {
            answer_textfield.setCustomValidity(Drupal.t('Only alphanumeric characters are allowed.'));
          } else {
            answer_textfield.setCustomValidity(Drupal.t('Answers must contain at least @length characters.', {'@length' : kba_answer_length}));
          }
        } else if (!pattern.test(inputValue)) {
          answer_textfield.setCustomValidity(Drupal.t('Only alphanumeric characters are allowed.'));
        } else {
          answer_textfield.setCustomValidity('');
        }
      }
  }
})(jQuery, Drupal, drupalSettings, once);
