# Computed field

## Description

This module provides five field types, whose values are computed by PHP code that you provide via [hook implementations](https://www.drupal.org/docs/creating-custom-modules/understanding-hooks).
 
 * **Computed decimal**. A numeric field type with precision and scale, and optional prefix and suffix.
 * **Computed float**. Another numeric field type with optional prefix and suffix.
 * **Computed integer**. This is a numeric field type for numbers without decimals, with optional prefix and suffix.
 * **Computed string**. This is a character field type with a given maximum of characters.
 * **Computed string** (long). This is like above, but with unlimited length.

Besides the data types, this module also provides some field formatters for each type, like the core field types:

 * **Unformatted** displays the value as is.
 * **Default** allows adding a prefix, suffix, and thousands separator (for numeric field types only).

All of these formatters allow a cache duration setting. By default, cache settings are left untouched (default) which is in most cases correct. But if the PHP code consists of volatile elements, like time/date dependent values, you should set the cache duration accordingly.

## Usage

1. Download and install the module as usual. No additional configuration is needed.
1. Add the computed field(s) to your bundles.
1. Go to the tab "Manage form display" and at least save the form. This is **necessary**, even if you don't change anything else!
1. If there is currently no content of that bundle, you can safely move the computed fields to the *Disabled* area. But if there is content, don't do that otherwise the computed values for existing content would not be created. Normally the fields are not displayed in the form unless you have defined the computed field as *multiple*. Then an (almost) empty table with drag options appears. This seems to be "normal" Drupal behaviour. You can solve this only by moving the computed field to the *Disabled* area with the implications above.
1. Go to the tab "Manage display" and drag the field into the correct order. Then you can select and configure the formatter. **Don't forget to hit the *Save* button to store your changes** otherwise they are lost.
1. It's always a good idea to **rebuild the cache** if you are playing around with the computed fields.

## Frequently Asked Questions (FAQ)

### Where did the configuration field for PHP code go?

The option to enter PHP code via the Web UI was removed in Drupal 9+ for security reasons. See [Stop allowing PHP from being entered on the Web UI](https://www.drupal.org/project/computed_field/issues/3143854) for details.

## Additional modules

### Computed field example formatter

This module provides an example for creating your own PHP formatter for a computed field. To do so see below.

## Examples

### See the difference between cache *default* and cache *off* or *duration*

To see the effects of caching you can follow this little example:

1. Add two computed integers to the bundle.
1. For both fields, create a hook function with PHP code like `return time();`.
1. Go to *Manage form display* and hit *Save*.
1. Go to *Manage display* and set caching in one of the field to *off* or a certain duration. Leave the other field as is! Hit *Save* again.
1. Add content or view existing content for that bundle.
1. Refresh the screen.

Now you can see that one field keeps its value while the other field counts the time (in intervals you have set with the cache duration).

*This does not work if you have developer settings with caching turned off!*

### Create your own PHP formatter

To create you own PHP formatter, clone the provided `computed_field_example_formatter` as follows:

1. Create a new module folder *modules/my_module* or (better) *modules/custom/my_module*.
1. Copy the contents of the *computed_field_example_formatter* folder to *my_module*.
1. Rename *computed_field_example_formatter.info.yml* file to *my_module_formatter.info.yml*. Modify name and description within the file as needed.
1. Rename *ComputePhpFormatterExample.php* file (in *src/Plugin/field/FieldFormatter*) to *myModuleFormatter.php*.
1. In this file, change all occurrences of *ComputedPhpFormatterExample* to *MyModuleFormatter*
1. In the annotations section *@FieldFormatter* change *id* and *label* to your needs.
1. Modify the body of the method *formatItem* as needed.
1. Install your module, or rebuild the cache to let drupal read in the annotations, if your module is already installed.

## More information

If you'd like more information, see the [Drupal.org documentation](https://www.drupal.org/node/126522).
