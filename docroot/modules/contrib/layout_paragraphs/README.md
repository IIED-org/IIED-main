Layout Paragraphs
============================

Layout Paragraphs provides an intuitive drag-and-drop experience for building
flexible layouts with [Paragraphs](https://www.drupal.org/project/paragraphs).
The module was designed from the ground up with paragraphs in mind, and works
seamlessly with existing paragraph reference fields.

### Key Features
- Intuitive drag-and-drop interface.
- Works with existing paragraph reference fields.
- Flexible configuration – site admins choose which paragraphs to use as “layout
  sections,” and which layouts should be available for each.
- Compatible with Drupal 9.

### How it Works
- Provides a new Field Widget and Field Formatter for paragraph reference
  fields.
- Leverages Drupal’s Layout API for building layouts.
- Uses the paragraphs behaviors API for storing layout data.

### Installation

**With composer**
- Ensure asset packagist has been set up for your project.
  - Visit
  https://www.drupal.org/docs/develop/using-composer/manage-dependencies#third-party-libraries
  for further information.
- Run `composer require bower-asset/dragula drupal/paragraphs drupal/layout_paragraphs`
- Install Layout Paragraps.

**Without composer**
- Download the [Dragula dist folder](https://github.com/bevacqua/dragula/tree/master/dist)
- Copy the dist folder inside your "libraries" folder, so the path structure is
as follows:
  - /libraries/dragula/dist/dragula.min.js
  - /libraries/dragula/dist/dragula.min.css
- Install the the [Paragraphs module](https://www.drupal.org/project/paragraphs)
and the [Layout Paragraphs module](https://www.drupal.org/project/layout_paragraphs)
as you would normally install a contributed Drupal module.

**Using the Content Delivery Network**
- Install the the [Paragraphs module](https://www.drupal.org/project/paragraphs)
and the [Layout Paragraphs module](https://www.drupal.org/project/layout_paragraphs)
as you would normally install a contributed Drupal module.
- The Dragual library will be automatically loaded via CDN if no local library
exists.

Visit "[Installing Modules](https://www.drupal.org/node/1897420)", if you have
trouble installing the module.

### Getting Started
- Create a new paragraph type (admin > structure > paragraph types) to use for
  layout sections. Your new paragraph type can have whatever fields you wish,
  although no fields are required for the module to work.
- Enable the “Layout Paragraphs” paragraph behavior for your layout section
  paragraph type, and select one or more layouts you wish to make available.
- Make sure your new layout section paragraph type is selected under “Reference
  Type” on the content type’s reference field edit screen by clicking “edit” for
  the respective field on the “Manage fields” tab.
- Choose “Layout Paragraphs” as the field widget type for the desired paragraph
  reference field under “Manage form display”.
- Choose “Layout Paragraphs” as the field formatter for the desired paragraph
  reference field under “Manage display”.
- That’s it. Start creating (or editing) content to see the module in action.

### Layout Paragraphs vs Layout Builder

Layout Paragraphs provides an effortless drag-and-drop editing experience for
writers, editors, and marketers. It has been designed from the ground up to meet
the needs of people who work with content. Unlike Layout Builder in Drupal core,
Layout Paragraphs  is not a site building tool. Rather, Layout Paragraphs is an
authoring tool. (Also note that Layout Paragraphs is compatible with Layout
Builder, meaning both can be installed and used on the same site.)

#### Key Differences between Layout Paragraphs and Layout Builder

- Layout Paragraphs works with Paragraphs, not Blocks.
- Layout Paragraphs is built on Drupal’s field system. Configuring Layout
  Paragraphs is as easy as configuring an entity reference field (aka Paragraphs
  field).
- Layout Paragraphs supports quickly toggling between different layouts within a
  given section, without having to delete the section.
- Layout Paragraphs supports nested layouts.
- Layout Paragraphs provides a “What You See Is What You Get” authoring
  experience, especially when configured to use the “Layout Paragraphs Builder”
  field formatter.
- Because Layout Paragraphs works with entity reference fields, it is extremely
  flexible and offers a broad range of applications.
- Layout Paragraphs is by design much simpler than Layout Builder, focused
  entirely on the content entry – or authoring – experience.
- Layout Paragraphs does not support creating templates, site-wide defaults, or
  default layouts for content types.  For these and other site-building needs,
  Layout Builder is a more appropriate solution.

### Maintainers
- Creator: [Justin Toupin (justin2pin)](https://www.drupal.org/u/justin2pin)
- [Italo Mairo (itamair)](https://www.drupal.org/u/itamair)


### Developer Notes

Maintainers are currently refactoring Layout Paragraphs to better encapsulate
structure and logic and provide simple APIs for interacting with other systems.

**Business logic is captured in three structural classes:**

- \Drupal\layout_paragraphs\LayoutParagraphsLayout
- \Drupal\layout_paragraphs\LayoutParagraphsSection
- \Drupal\layout_paragraphs\LayoutParagraphsComponent

**A Layout Paragraphs Renderer service** (LayoutParagraphsRendererService)
provides a simple method for rendering a Layout Paragraph section.

**The UI is provided through Plugins:**

- A Field Widget Plugin (LayoutParagraphsWidget) that provides the editing
  interface.
- A Field Formatter Plugin (LayoutParagraphsFormatter) that renders the front
  end.
- A Paragraphs Behavior Plugin (LayoutParagraphsBehavior) that (a) renders the
  layout selection form, and (b) renders nested components within their
  respective regions for the front end.

#### More on Structural Classes

The Layout Paragraphs system is comprised of three primary parts:

- A Layout, made up of...
- Layout Sections, which contain...
- Layout Components, which are wrappers/decorators for Drupal Paragraphs.

**A Layout** has a collection of Layout Sections and Layout Components. The main
Layout class (LayoutParagraphsLayout) has methods for manipulating the layout in
various ways, for example:

- Inserting a new component.
- Re-ordering sections and their respective, nested components.

Because a Layout is attached to a Paragraphs reference field, manipulating the
layout (adding components, removing components, reordering components) will
manipulate the reference field and the behavior settings for various paragraphs
"under the hood". You can get the entity containing the field reference by using
the "getEntity()" method on the Layout, or the reference field itself with
"getFieldItemList()" (also on the Layout class).

**A Layout Section** is a component (more below) with an applied Layout. A
Layout Section has a collection of Layout Components, a layout plugin id, and
layout configuration needed for rendering the layout.

**Layout Components** wrap, or decorate, paragraph entities with additional
properties and functionality specific to working with layouts (i.e. the
component's region, the component's parent, etc.). Layout Components are the
simplest element and smallest "building block" within the Layout Paragraphs
system.

#### Example Code: Working with a Layout

```php
// Instantiate a new layout given an exisitng node
// and the paragraph reference field name.
$layout = new LayoutParagraphsLayout($node, $field_name);

// Insert a new component directly after the exisiting paragraph with the given
// uuid. This will add the new component into the same section and region as the
// paragraph with the given uuid.
$layout->insertAfterComponent($existing_paragraph_uuid, $the_new_paragraph);

// Call the save() method on the node to save the changes permanently.
$layout->getEntity()->save();
```

#### Example Code: Rendering a Layout Paragraph

Note that rendering a Layout Section is already handled in the Layout Paragraphs
Paragraph Behavior. Calling this service directly would only be necessary for
cases where the developer wants to explicitly render the regions and components
of a Layout Section, outside of the context of rendering the paragraph to which
the layout is attached. In other words, you shouldn't need to do this in 99% of
cases.

See: \Drupal\layout_paragraphs\Plugin\paragraphs\Behavior\LayoutParagraphsBehavior::view

```php
$layout_service = \Drupal::service('layout_paragraphs');
$layout_service->renderLayoutParagraph($paragraph_entity, $view_mode);
```
