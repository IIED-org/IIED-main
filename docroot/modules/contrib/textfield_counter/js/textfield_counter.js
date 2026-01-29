/**
 * @file
 * Adds textfield counter JavaScript.
 */

/* global CKEDITOR */

((Drupal, once) => {
  function addClass(element, className) {
    element.classList.add(className);
  }

  function removeClass(element, className) {
    const classes = className.split(' ');
    classes.forEach((cls) => element.classList.remove(cls));
  }

  function checkClasses(element, remaining) {
    if (remaining <= 5 && remaining >= 0) {
      removeClass(element, 'textcount_over');
      addClass(element, 'textcount_warning');
    } else if (remaining < 0) {
      removeClass(element, 'textcount_warning');
      addClass(element, 'textcount_over');
    } else {
      removeClass(element, 'textcount_warning textcount_over');
    }
  }

  function textWatcher(settings) {
    Object.keys(settings.textfieldCounter).forEach((key) => {
      const fieldSettings = settings.textfieldCounter[key];

      fieldSettings.key.forEach((keyClass, index) => {
        const elements = once(
          'textfield-counter-text-watcher',
          `.${fieldSettings.key[index]}`,
        );

        elements
          .filter((el) => {
            return (
              el.tagName === 'TEXTAREA' ||
              (el.tagName === 'INPUT' && el.type === 'text')
            );
          })
          .forEach((element) => {
            let counter;
            let currentLength;
            let remaining;
            let countHTML;

            const { maxlength } = fieldSettings;
            if (maxlength) {
              countHTML = fieldSettings.countHTMLCharacters;
              if (countHTML) {
                currentLength = element.value.length;
              } else {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = element.value;
                currentLength = tempDiv.textContent
                  .trim()
                  .replace(/(\r?\n|\r)+/g, '\n').length;
              }
              remaining = maxlength - currentLength;

              counter = document.createElement('div');
              counter.className = 'textfield_counter_counter';
              counter.innerHTML = Drupal.t(
                fieldSettings.textCountStatusMessage,
                {
                  '@maxlength': maxlength,
                  '@current_length': currentLength,
                  '@remaining_count': remaining,
                },
              );

              if (fieldSettings.counterPosition === 'before') {
                element.parentNode.insertBefore(counter, element);
              } else {
                element.parentNode.insertBefore(counter, element.nextSibling);
              }

              checkClasses(element.parentNode, remaining);

              element.addEventListener('keyup', () => {
                if (countHTML) {
                  currentLength = element.value.length;
                } else {
                  const tempDiv = document.createElement('div');
                  tempDiv.innerHTML = element.value;
                  currentLength = tempDiv.textContent
                    .trim()
                    .replace(/(\r?\n|\r)+/g, '\n').length;
                }

                remaining = maxlength - currentLength;
                const remainingEl = counter.querySelector('.remaining_count');
                const currentEl = counter.querySelector('.current_count');

                if (remainingEl) remainingEl.textContent = remaining;
                if (currentEl) currentEl.textContent = currentLength;

                checkClasses(element.parentNode, remaining);
              });
            }
          });
      });
    });
  }

  function formSubmitListener(context, settings) {
    const forms = once(
      'textfield-counter-form-submit-listener',
      'form',
      context,
    );

    Array.from(forms)
      .filter((form) => {
        // Only process forms that contain textarea or input[type=text] elements
        return form.querySelector('textarea, input[type=text]') !== null;
      })
      .forEach((form) => {
        form.addEventListener('submit', (e) => {
          let hasScrolled = false;
          const errorElements = form.querySelectorAll('.textcount_over');
          errorElements.forEach((errorElement, elementIndex) => {
            Object.keys(settings.textfieldCounter).forEach((settingsIndex) => {
              const fieldSettings = settings.textfieldCounter[settingsIndex];
              const wrapperElement = errorElement;
              const textfieldElement = wrapperElement.querySelector(
                '.textfield-counter-element',
              );

              if (
                !hasScrolled &&
                fieldSettings.preventSubmit &&
                textfieldElement &&
                textfieldElement.classList.contains(settingsIndex)
              ) {
                e.preventDefault();

                // Smooth scroll to element
                wrapperElement.scrollIntoView({ behavior: 'smooth' });

                hasScrolled = true;
              }
            });
          });
        });
      });
  }

  /**
   * Add event listeners to ckeditors.
   * @param {Object} settings - The Drupal settings object.
   */
  function ckEditorListener(settings) {
    if (window.hasOwnProperty('CKEDITOR')) {
      // Wait until the editor is loaded.
      CKEDITOR.on('instanceReady', () => {
        // Loop through each of the textfield settings.
        Object.keys(settings.textfieldCounter).forEach((fieldDefinitionKey) => {
          const fieldSettings = settings.textfieldCounter[fieldDefinitionKey];

          // Use the fieldDefinitionKey to get the HTML ID, which is used to
          // reference the editor.
          const fieldElement = document.querySelector(
            `.${fieldDefinitionKey}[id]`,
          );
          const fieldID = fieldElement ? fieldElement.id : null;

          if (fieldID && CKEDITOR.instances[fieldID]) {
            // Add keyup listener.
            CKEDITOR.instances[fieldID].on('key', function onKey() {
              // The last key pressed isn't available in editor.getData() when
              // the key is pressed. A workaround is to use setTimeout(), with no
              // time set to it, as this moves it to the end of the process queue,
              // when the last pressed key will be available.
              const editor = this;
              window.setTimeout(() => {
                let currentLength;

                const countHTML = fieldSettings.countHTMLCharacters;
                const { maxlength } = fieldSettings;
                const text = editor.getData().trim();
                if (countHTML) {
                  currentLength = text.length;
                } else {
                  // The following is done to retrieve the current length:
                  // 1) The content is inserted into a DIV as HTML.
                  // 2) textContent is used to retrieve just the text of the element.
                  // 3) The context is trimmed.
                  // 4) Multiple consecutive newlines are replaced with a single
                  // newline, so as to only count a linebreak as a single
                  // character.
                  const tempDiv = document.createElement('div');
                  tempDiv.innerHTML = text;
                  currentLength = tempDiv.textContent
                    .trim()
                    .replace(/(\r?\n|\r)+/g, '\n').length;
                }
                const remaining = maxlength - currentLength;
                const elementkey = '$';
                // The editor.element.$ variable contains a reference to the HTML
                // textfield. This is used to create a reference.
                const textfield = editor.element[elementkey];

                // Set the current count on the counter.
                const counterEl = textfield.parentNode.querySelector(
                  '.textfield_counter_counter',
                );
                if (counterEl) {
                  const currentEl = counterEl.querySelector('.current_count');
                  if (currentEl) currentEl.textContent = currentLength;

                  // Set the remaining count on the counter.
                  const remainingEl =
                    counterEl.querySelector('.remaining_count');
                  if (remainingEl) remainingEl.textContent = remaining;
                }

                // Set the classes on the parent.
                checkClasses(textfield.parentNode, remaining);
              });
            });
          }
        });
      });
    }

    if (window.hasOwnProperty('CKEditor5')) {
      const ckeditor5Init = () => {
        // Loop through each of the textfield settings.
        Object.keys(settings.textfieldCounter).forEach((fieldDefinitionKey) => {
          const fieldSettings = settings.textfieldCounter[fieldDefinitionKey];

          // Use the fieldDefinitionKey to get the HTML ID, which is used to
          // reference the editor.
          const textfield = document.querySelector(
            `.${fieldDefinitionKey}[id]`,
          );

          if (textfield && Drupal.CKEditor5Instances) {
            const editor = Drupal.CKEditor5Instances.get(
              textfield.dataset.ckeditor5Id,
            );

            if (editor) {
              const countOnKey = () => {
                let currentLength;

                const countHTML = fieldSettings.countHTMLCharacters;
                const { maxlength } = fieldSettings;
                const text = editor.getData().trim();
                if (countHTML) {
                  currentLength = text.length;
                } else {
                  // The following is done to retrieve the current length:
                  // 1) The content is inserted into a DIV as HTML.
                  // 2) textContent is used to retrieve just the text of the element.
                  // 3) The context is trimmed.
                  // 4) Multiple consecutive newlines are replaced with a single
                  // newline, so as to only count a linebreak as a single
                  // character.
                  const tempDiv = document.createElement('div');
                  tempDiv.innerHTML = text;
                  currentLength = tempDiv.textContent
                    .trim()
                    .replace(/(\r?\n|\r)+/g, '\n').length;
                }
                const remaining = maxlength - currentLength;

                // Set the current count on the counter.
                const counterEl = textfield.parentNode.querySelector(
                  '.textfield_counter_counter',
                );
                if (counterEl) {
                  const currentEl = counterEl.querySelector('.current_count');
                  if (currentEl) currentEl.textContent = currentLength;

                  // Set the remaining count on the counter.
                  const remainingEl =
                    counterEl.querySelector('.remaining_count');
                  if (remainingEl) remainingEl.textContent = remaining;
                }

                // Set the classes on the parent.
                checkClasses(textfield.parentNode, remaining);
              };

              editor.editing.view.document.on('keydown', countOnKey);
              editor.editing.view.document.on('keyup', countOnKey);
            }
          }
        });
      };

      const ckeditor5Timeout = setTimeout(() => {
        ckeditor5Init();
        clearTimeout(ckeditor5Timeout);
      }, 300);
    }
  }

  Drupal.behaviors.textfieldCounterTextarea = {
    attach(context, settings) {
      textWatcher(settings);
      formSubmitListener(context, settings);
      ckEditorListener(settings);
    },
  };
})(Drupal, once);
