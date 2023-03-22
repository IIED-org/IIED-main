# CKEditor Anchor Link

This plugin module adds the better link dialog and anchor related features
to CKEditor in Drupal:

- Dialog to insert links and anchors with some properties.
- Context menu option to edit or remove links and anchors.
- Ability to insert a link with the URL using multiple protocols, including an
  external file if a file manager is integrated.

Most text formats limit HTML tags. If this is the case, it will
 be necessary to whitelist the "name" attribute on the "a" element.

E.g. `<a name href hreflang>`


### Requirements
* CKEditor
* [CKEditor FakeObjects](https://www.drupal.org/project/fakeobjects) module.
* [CKEditor Anchor link](https://ckeditor.com/cke4/addon/link) plugin library.
   Place it in **/libraries/link** directory.
* CKEditor [Fake Objects](https://ckeditor.com/cke4/addon/fakeobjects)
  plugin library. Place it in **/libraries/fakeobjects** directory.
 which managed by the CKEditor fakeobjects module.
