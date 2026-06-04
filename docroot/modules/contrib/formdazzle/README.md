# Formdazzle!

**Drupal form theming with less pain**

Theming drupal forms can be difficult and time-consuming. This module provides a
set of utilities that make form theming easier.

Currently, this module provides theme suggestions for forms that are much more
useful than those provided by Drupal core.

- Theme suggestions for all form elements (including buttons)
- Theme suggestions for all form element labels
- All theme suggestions include the form ID and the form element name;
  e.g. `[element-type]--[form-id]--[form-element-name].html.twig`
- Twig debugging comments have been added to all forms for the hidden
  `[form-id].html.twig` template.

While Drupal core only provided these two theme suggestions:

1. `input.html.twig`
2. `input--textfield.html.twig`

Formdazzle adds the following two theme suggestions to the list:

3. `input--textfield--webform-contact.html.twig`
4. `input--textfield--webform-contact--first-name.html.twig`

## Similar modules

### [Themable forms](https://www.drupal.org/project/themable_forms)

Differences (see https://www.drupal.org/project/formdazzle/issues/3278319)
1. Themable Forms (started in September 2016 by lauriii) is older than
   formdazzle (started September 2019 by JohnAlbin).
2. Themable forms has very few commits and amounts to two template suggestion
   hooks inside the *.module file.
3. Formdazzle! on the other, hand has many commits and takes a different
   approach. It uses the *.module file to register a preprocess_form_element()
   function, but all the module's business logic is housed in a PHP Class
   called Dazzler.php. The class has unit tests whereas Themable Forms has no
   tests (due to its simplistic nature).
