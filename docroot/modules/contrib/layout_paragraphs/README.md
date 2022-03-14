Layout Paragraphs
============================

Layout Paragraphs provides an intuitive drag-and-drop experience for building flexible layouts with [Paragraphs](https://www.drupal.org/project/paragraphs). The module was designed from the ground up with paragraphs in mind, and works seamlessly with existing paragraph reference fields.

### Key Features
- Intuitive drag-and-drop interface.
- Works with existing paragraph reference fields.
- Flexible configuration – site admins choose which paragraphs to use as “layout sections,” and which layouts should be available for each.
- Compatible with Drupal 9.

### How it Works
- Provides a new Field Widget and Field Formatter for paragraph reference fields.
- Leverages Drupal’s Layout API for building layouts.
- Uses the paragraphs behaviors API for storing layout data.

### Getting Started
- Make sure the [Paragraphs module](https://www.drupal.org/project/paragraphs) is installed.
- Download/Require
(`composer require drupal/layout_paragraphs`)
and install Layout Paragraphs.
- Create a new paragraph type (admin > structure > paragraph types) to use for layout sections. Your new paragraph type can have whatever fields you wish, although no fields are required for the module to work.
- Enable the “Layout Paragraphs” paragraph behavior for your layout section paragraph type, and select one or more layouts you wish to make available.
- Make sure your new layout section paragraph type is selected under “Reference Type” on the content type’s reference field edit screen by clicking “edit” for the respective field on the “Manage fields” tab.
- Choose “Layout Paragraphs” as the field widget type for the desired paragraph reference field under “Manage form display”.
- Choose “Layout Paragraphs” as the field formatter for the desired paragraph reference field under “Manage display”.
- That’s it. Start creating (or editing) content to see the module in action.

### Maintainers
- Creator: [Justin Toupin (justin2pin)](https://www.drupal.org/u/justin2pin)
- [Italo Mairo (itamair)](https://www.drupal.org/u/itamair)
