# Data Layer

## Introduction

This module provides support for adding a JavaScript <code>dataLayer</code> variable to the page. The <code>dataLayer</code> API is most commonly used as a standard for [integrating with Google Tag Manager](https://developers.google.com/tag-platform/tag-manager/web/datalayer). However it's also a good standard for any situation where the server needs to expose backend metadata to the front-end as part of the page source and is generic enough that services other than GTM can make use of it.

This module was initially created to work with <a href="https://www.drupal.org/project/semi_anonymous">Semi Anonymous</a>, but is also useful for anyone working with Google Tag Manager.

The module provides built in support for Drupal core's content entities, users, taxonomy, and fields. And can be configured to include only the data required for your use-case. The module can also be extended through the use of hooks to support virtually any data stored by Drupal.

### Meta data output

Example of `window.dataLayer` output created by this module:

```json
{
  "drupalLanguage": "en",
  "userStatus": "anonymous",
  "userUid": "555",
  "entityId" : "123",
  "entityTitle" : "My Cool Page",
  "entityType" : "node",
  "entityBundle" : "article",
  "entityUid" : "555",
  "entityLangcode" : "en",
  "entityTaxonomy" : {
    "special_category" : {
      "25" : "Term Name",
      "26" : "Another Term"
    },
    "my_type" : {
      "13" : "Some Tag",
      "14" : "Another Tag"
    }
  }
}
```

## Requirements

This module does not require any other modules. However, if you wish to perform client-side manipulation of the `dataLayer` variable you should install the recommended helper library. More below.

## Installation

The module can be installed via Composer and enabled as per normal Drupal module installation.

## Configuration

Once enabled there are two things that can be configured. Start by navigating to /admin/config/search/datalayer here you can configure:

- Global settings for the module
- What entity properties to include in the `dataLayer` output
- Out URL path components in the `dataLayer`
- Configure what vocabularies should be included when output entity data
- Configure what data about the current user should be added to the `dataLayer` and on what pages
- Change the keys used in the `dataLayer` for deafult entity properties output by the module

If the _Include enabled field values_ global option is enabled then you can also expose the data of fields added to entities via the UI. Do to so, edit the field settings for specific field(s) you want to expose and look for the "expose to dataLayer" checkbox.

## Troubleshooting

Post problems or feature requests to the [Drupal project issue queue](https://drupal.org/project/issues/datalayer).

## Extending the dataLayer module

User configurable fields should all have an "expose to dataLayer" configuration option added. But entity properties and baseifelds which might not be configurable via the UI require additional ingration.

### Suggest additional entity properties

Suggest additional entity properties to the Data Layer module by implementing `hook_datalayer_meta()`, or `hook_datalayer_ENTITY_TYPE_meta()`.

Example:

```php
function my_module_datalayer_meta() {
  return [
    // Adds $entity->myProperty as possible data.
    'myProperty',
  ];
}
```

If an administrator configures the module to enable `my_property` than the `dataLayer` output will include:

```json
{
  "entityMyProperty":"asdf-123"
}
```

### Suggest current user properties

Suggest additional properties for the current user to the Data Layer module by using the `hook_datalayer_current_user_meta()` function.

Example:

```php
function my_module_datalayer_current_user_meta() {
  return array(
    'timezone',
  );
}
```
If user output is enabled, it will be added to the page as:
```json
{
  "userTimezone": "whatever the value is"
}
```

### Add data layer values

Add data layer properties and values on the fly within your code using the `datalayer_add()` function.

NOTE: In that case of matching keys, any added property/value pairs can overwrite those already available via normal entity output. You _should_ be using the `datalayer_alter()` function if that's the intent, as added properties are available there.

Example:

```php
function _my_module_myevent_func($argument = FALSE) {
  if ($argument) {
    datalayer_add([
      'drupalMyProperty' => $argument,
      'userAnotherProperty' => _my_module_other_funct($argument),
    ]);
  }
}
```

## Alter output

### Data Layer Output Keys configuration

The Data Layer output keys can be customized in the Data Layer configuration page. For example, the default key `entityTitle` could be customized set to `entityLabel` to maintain compatibility with the 7.x-1.x version of this module.

### Field data output configuration

Entity field data can be exposed in the datalayer on a per-bundle basis. To enable the feature, check the "Include enabled field values" option available in the Data Layer configuration page.

When enabled individual fields can be exposed by editing the desired field(s) of any given content type and checking the "Expose in dataLayer" option.

Sample field output:

```json
{
  "field_foobar": {
    "value": "baz",
    "format": "basic_html"
  }
}
```

### Current user meta data output configuration

Meta data for the current user can be added to the datalayer. This feature can be enabled by configuring matching paths for pages current user meta data should be included on. Wildcards '*' can be used.

Sample user output:
```json
{
  "userRoles": [
    "authenticated"
  ],
  "userAccess": "1517172411",
  "userCreated": "1516478100"
}
```

### Alter available properties

You can alter what entity properties are available within the admin UI (add candidates) via the `hook_datalayer_meta_alter()` function. You may want to take advantage of the entity agnostic menu object loader function found within the module. For example you might want to hide author information in some special cases...

```php
function my_module_datalayer_meta_alter(&$properties) {
  // Override module norm in all cases.
  unset($properties['uid']);

  // Specific situation alteration...
  $type = false;
  if ($obj = _datalayer_menu_get_any_object($type)) {
    if ($type === 'node' && in_array(['my_bundle', 'my_nodetype']), $obj->type)) {
      // Remove author names on some content type.
      if ($key = array_search('name', $properties)) {
        unset($properties[$key]);
      }
    }
    elseif ($type === 'my_entity_type') {
      // Remove some property on some entity type.
      if ($key = array_search('my_property', $properties)) {
        unset($properties[$key]);
      }
    }
  }
}
```

### Alter available current user meta data properties

Example implementation of `hook_datalayer_current_user_meta_alter()`:

```php
function my_module_datalayer_current_user_meta_alter(&$properties) {
  // Override module norm in all cases.
  unset($properties['roles']);
}
```

### Alter output values

Directly alter output bound data with implementations of `hook_datalayer_alter()`. Use this to alter values found in normal entity output or added by `datalayer_add()` within the same or other modules, to support good architecture.

```php
function my_module_datalayer_alter(&$data_layer) {
  // Make the title lowercase on some node type.
  if (isset($data_layer['entityBundle']) && $data_layer['entityBundle'] == 'mytype') {
    $data_layer['entityLabel'] = strtolower($data_layer['entityLabel']);
  }
}
```

### Alter field values

Alter output for fields that are included in the datalayer with `hook_datalayer_field_alter()`. Use this to add or remove or alter attributes of the value returned by a field item.

```php
function my_module_datalayer_field_alter(array &$value, \Drupal\Core\Field\FieldItemInterface $field_item, $field_type) {
  // Remove the filter format for a text with summary field.
  if ($field_type == 'text_with_summary') {
    unset($value['format']);
  }
}
```

## Use the data layer client-side

There are lots of great client-side uses for your pages' data. The `dataLayer` object is used as a warehouse for Google Analytics and GTM, and is therefore an array of objects. To safely access properties you should use the <a href="#data-layer-helper">data-layer-helper</a> library, a dependency of this module.

You might act on this info like this...

```javascript
var myHelper = new DataLayerHelper(dataLayer),
    myVocab = myHelper.get('entityTaxonomy.my_category'),
    specialTagTid = 25;

// Check for some term tag bring present.
if (typeof myVocab !== 'undefined' && myVocab.hasOwnProperty(specialTagTid)) {
  doMyThing(myHelper.get('entityUid'), myHelper.get('drupalLanguage'), myHelper.get('entityLabel'));
}
```

## Language utilities

If your project is multilingual this module provides several useful client-side tools...

```
Drupal.settings.dataLayer.languages;
```

Returns objects of your active langauges with full-details such as: prefix, native text, enabled, domain, name, etc.

```
Drupal.behaviors.dataLayer.langPrefixes();
```

Returns an array of your active language prefix codes, excluding any that are left empty.

## Dynamic additions

You can add new data to the data layer dynamically via JavaScript. This is how GA does it, you should follow those patterns.

```javascript
// Inform of link clicks.
$(".my-links").click(function() {
  dataLayer.push({ 'eventLinkClick': $(this).text() });
});

// Inform of Views filter changes.
$(".views-widget select.form-select").change(function() {
  dataLayer.push({
    'eventFilterSet': $(this).closest('.view').attr('id') + ':' +
      $(this).attr('name') + ':' + $(this).find("option:selected").text();
  });
});
```

## Google

Learn more about using the `dataLayer` with Google services in the [Tag Manager - Dev Guide](https://developers.google.com/tag-manager/devguide).

### Data Layer Helper

To employ more complex interactions with the data you may want load the [data-layer-helper](https://github.com/google/data-layer-helper) library. It provides the ability to "process messages passed onto a dataLayer queue," meaning listen to data provided to the data layer dynamically.

To use, add the compiled source to the standard Drupal location of `libraries/data-layer-helper/dist/data-layer-helper.js` and check the box on the admin page to include it.

## Changelog

### Meta data output changes since 7.x-1.x

* `entityLabel` is now `entityTitle`
* `entityLanguage` is now `entityLangcode`
* `entityTnid` has been removed
*
