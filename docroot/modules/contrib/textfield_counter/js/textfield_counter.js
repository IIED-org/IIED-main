/**
 * @file
 * Adds textfield counter JavaScript.
 */

/*global jQuery, Drupal, window, CKEDITOR*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, window) {

  "use strict";

  function addClass(element, className) {
    element.addClass(className);
  }

  function removeClass(element, className) {
    element.removeClass(className);
  }

  function checkClasses(element, remaining) {
    if (remaining <= 5 && remaining >= 0) {
      removeClass(element, "textcount_over");
      addClass(element, "textcount_warning");
    }
    else if (remaining < 0) {
      removeClass(element, "textcount_warning");
      addClass(element, "textcount_over");
    }
    else {
      removeClass(element, "textcount_warning textcount_over");
    }
  }

  function textWatcher(settings) {
    $.each(settings.textfieldCounter, function (key) {
      var fieldSettings = settings.textfieldCounter[key];

      $.each(fieldSettings.key, function (index) {
        $("." + fieldSettings.key[index]).not(".description").once("textfield-counter-text-watcher").once("textfield-counter-counter-watcher").each(function () {
          var counter, maxlength, currentLength, remaining, countHTML;

          maxlength = fieldSettings.maxlength;
          if (maxlength) {
            countHTML = fieldSettings.countHTMLCharacters;
            if (countHTML) {
              currentLength = $(this).val().length;
            }
            else {
              currentLength = $("<div/>").html($(this).val()).text().trim().replace(/(\r?\n|\r)+/g, "\n").length;
            }
            remaining = maxlength - currentLength;
            counter = $("<div/>", {class:"textfield_counter_counter"}).html(Drupal.t(fieldSettings.textCountStatusMessage, {"@maxlength":maxlength , "@current_length":currentLength , "@remaining_count":remaining}));

            if (fieldSettings.counterPosition === "before") {
              counter.insertBefore($(this));
            }
            else {
              counter.insertAfter($(this));
            }

            checkClasses($(this).parent(), remaining);

            $(this).keyup(function () {
              if (countHTML) {
                currentLength = $(this).val().length;
              }
              else {
                currentLength = $("<div/>").html($(this).val()).text().trim().replace(/(\r?\n|\r)+/g, "\n").length;
              }

              remaining = maxlength - currentLength;
              counter.children(".remaining_count:first").text(remaining);
              counter.children(".current_count:first").text(currentLength);
              checkClasses($(this).parent(), remaining);
            });
          }
        });
      });
    });
  }

  function formSubmitListener(context, settings) {
    $(context).find("form").once("textfield-counter-form-submit-listener").each(function () {
      $(this).submit(function (e) {
        var errorElements = $(this).find(".textcount_over");
        errorElements.each(function (elementIndex) {
          $.each(settings.textfieldCounter, function (settingsIndex, fieldSettings) {
            var wrapperElement = $(errorElements[elementIndex]);
            if (fieldSettings.preventSubmit && settingsIndex === wrapperElement.find(".textfield-counter-element:first").data("field-definition-id")) {
              e.preventDefault();
              $("html, body").animate({
                scrollTop:wrapperElement.offset().top
              }, 300);
            }
          });
        });
      });
    });
  }

  /**
   * Add event listeners to ckeditors.
   */
  function ckEditorListener(settings) {
    if (window.hasOwnProperty("CKEDITOR")) {
      // Wait until the editor is loaded.
      CKEDITOR.on('instanceReady', function () {
        // Loop through each of the textfield settings.
        $.each(settings.textfieldCounter ,function (fieldDefinitionKey, fieldSettings) {
          // Use the fieldDefinitionKey to get the HTML ID, which is used to
          // reference the editor.
          var fieldID = $("." + fieldDefinitionKey + ":first").attr("id");
          if (CKEDITOR.instances[fieldID]) {
            // Add keyup listener.
            CKEDITOR.instances[fieldID].on("key", function () {
              // The last key pressed isn't available in editor.getData() when
              // the key is pressed. A workaround is to use setTimeout(), with no
              // time set to it, as this moves it to the end of the process queue,
              // when the last pressed key will be available.
              var editor = this;
              window.setTimeout(function () {
                var countHTML, maxlength, text, currentLength, remaining, textfield;

                countHTML = fieldSettings.countHTMLCharacters;
                maxlength = fieldSettings.maxlength;
                text = $.trim(editor.getData());
                if (countHTML) {
                  currentLength = text.length;
                }
                else {
                  // The following is done to retrieve the current length:
                  // 1) The content is inserted into a DIV as HTML.
                  // 2) $.text() is used to retrieve just the text of the element.
                  // 3) The context is trimmed.
                  // 4) Multiple consecutive newlines are replaced with a single
                  // newline, so as to only count a linebreak as a single
                  // character.
                  currentLength = $("<div/>").html(text).text().trim().replace(/(\r?\n|\r)+/g, "\n").length;
                }
                remaining = maxlength - currentLength;
                var elementkey = "$";
                // The editor.element.$ variable contains a reference to the HTML
                // textfield. This is used to create a  jQuery object.
                textfield = $(editor.element[elementkey]);
                // Set the current count on the counter.
                textfield.siblings(".textfield_counter_counter:first").children(".current_count:first").text(currentLength);
                // Set the remaining count on the counter.
                textfield.siblings(".textfield_counter_counter:first").children(".remaining_count:first").text(remaining);
                // Set the classes on the parent.
                checkClasses(textfield.parent(), remaining);
              });
            });
          }
        });
      });
    }
  }

  Drupal.behaviors.textfieldCounterTextarea = {
    attach:function (context, settings) {
      textWatcher(settings);
      formSubmitListener(context, settings);
      ckEditorListener(settings);
    }
  };

}(jQuery, Drupal, window));
