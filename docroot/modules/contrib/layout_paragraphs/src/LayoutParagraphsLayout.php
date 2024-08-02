<?php

namespace Drupal\layout_paragraphs;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a domain object for a complete Layout Paragraphs Layout.
 *
 * A Layout Paragraphs Layout represents a collection of
 * Layout Paragraphs Sections and Layout Paragraphs Components
 * associated with a paragraphs reference field.
 * This class provides public methods for manipulating a layout -
 * i.e. adding, removing, and reording paragraph layout sections
 * and paragraph layout components.
 *
 * See also:
 * - Drupal\layout_paragraphs\LayoutParagraphsComponent
 * - Drupal\layout_paragraphs\LayoutParagraphsSection
 */
class LayoutParagraphsLayout implements ThirdPartySettingsInterface {

  use DependencySerializationTrait;

  /**
   * The paragraph reference field the layout is attached to.
   *
   * @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  protected $paragraphsReferenceField;

  /**
   * Third party settings.
   *
   * An array of key/value pairs keyed by provider.
   *
   * @var array[]
   */
  protected $thirdPartySettings = [];


  /**
   * Settings.
   *
   * An array of key/value pairs.
   *
   * @var array[]
   */
  protected $settings;

  /**
   * The layout ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The layout's parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $paragraphs_reference_field
   *   The paragraph reference field this layout is attached to.
   * @param array[] $settings
   *   An array of settings.
   */
  public function __construct(
    EntityReferenceFieldItemListInterface $paragraphs_reference_field,
    array $settings = []
  ) {
    $this->paragraphsReferenceField = $paragraphs_reference_field;
    $this->settings = $settings;
  }

  /**
   * Returns a unique id for this layout.
   *
   * @return string
   *   A unique id.
   */
  public function id() {
    if (empty($this->id)) {
      $this->id = bin2hex(random_bytes(16));
    }
    return $this->id;
  }

  /**
   * Returns the layout's parent entity with updated paragraphs reference field.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    $entity = $this->paragraphsReferenceField->getEntity();
    return $entity;
  }

  /**
   * Set the entity that this layout is attached to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to set.
   *
   * @return $this
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Sets the settings array.
   *
   * @param array[] $settings
   *   An associative array of settings.
   *
   * @return $this
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * Returns the settings array.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Returns a single setting from the settings array.
   *
   * @param string $key
   *   The key of the setting to return.
   * @param mixed $default
   *   The default value to return if the setting is empty.
   */
  public function getSetting(string $key, $default = NULL) {
    return $this->settings[$key] ?? $default;
  }

  /**
   * Returns the reference field that this layout is attached to.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   *   The field item list.
   */
  public function &getParagraphsReferenceField() {
    return $this->paragraphsReferenceField;
  }

  /**
   * Set the field item list.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $paragraphs_reference_field
   *   The field item list to set.
   *
   * @return $this
   */
  public function setParagraphsReferenceField(EntityReferenceFieldItemListInterface $paragraphs_reference_field) {
    foreach ($paragraphs_reference_field as $key => $field_item) {
      if ($field_item->entity) {
        $paragraphs_reference_field[$key]->entity->_referringItem = $field_item;
        $paragraphs_reference_field[$key]->entity->_layoutParagraphsLayout = $this;
      }
    }
    $this->paragraphsReferenceField = $paragraphs_reference_field;
    return $this;
  }

  /**
   * Returns the field name.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName() {
    /** @var \Drupal\field\Entity\FieldConfig $definition **/
    $definition = $this->paragraphsReferenceField->getFieldDefinition();
    $field_name = $definition->getName();
    return $field_name;
  }

  /**
   * Wraps the paragraph in the component class.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return LayoutParagraphsComponent|LayoutParagraphsSection
   *   The component.
   */
  public function getComponent(ParagraphInterface $paragraph) {
    return new LayoutParagraphsComponent($paragraph);
  }

  /**
   * Returns the component with matching uuid.
   *
   * @param string $uuid
   *   The uuid to search for.
   *
   * @return LayoutParagraphsComponent
   *   The component.
   */
  public function getComponentByUuid($uuid) {
    foreach ($this->getEntities() as $entity) {
      if ($entity->uuid() == $uuid) {
        return $this->getComponent($entity);
      }
    }
  }

  /**
   * Returns a Layout Paragraphs Layout Section for the given paragraph.
   *
   * If the provided paragraph is not a layout section, returns false.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsSection|false
   *   The layout section or false.
   */
  public function getLayoutSection(ParagraphInterface $paragraph) {
    if (!LayoutParagraphsSection::isLayoutComponent($paragraph)) {
      return FALSE;
    }
    $uuid = $paragraph->uuid();
    $components = array_filter(
      $this->getComponents(),
      function ($component) use ($uuid) {
        return $component->getParentUuid() == $uuid;
      }
    );
    return new LayoutParagraphsSection($paragraph, $components);
  }

  /**
   * Returns a list of root level components for this collection.
   *
   * @return array
   *   An array of root level layout paragraph components.
   */
  public function getRootComponents() {
    return array_filter($this->getComponents(), function ($component) {
      return $component->isRoot();
    });
  }

  /**
   * Returns a list of all components for this collection.
   *
   * @return array
   *   An array of layout paragraph components.
   */
  public function getComponents() {
    return array_map(function ($paragraph) {
      return $this->getComponent($paragraph);
    }, $this->getEntities());
  }

  /**
   * Returns a list of all paragraph entities associated with this collection.
   *
   * @return \Drupal\paragraphs\ParagraphInterface[]
   *   An array of paragraph entities.
   */
  public function getEntities() {
    $items = [];
    foreach ($this->paragraphsReferenceField as $field_item) {
      if ($field_item->entity) {
        $items[] = $field_item->entity;
      }
    }
    return $items;
  }

  /**
   * Determines whether the reference field contains any non-empty items.
   *
   * @return bool
   *   TRUE if the list is empty, FALSE otherwise.
   */
  public function isEmpty() {
    return $this->paragraphsReferenceField->isEmpty();
  }

  /**
   * Sets a layout component.
   *
   * If a component is found with a matching paragraph,
   * the matching component's paragraph is overwritten with the
   * incoming paragraph. Otherwise the paragraph is appended
   * to the field item list.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to set.
   *
   * @return $this
   */
  public function setComponent(ParagraphInterface $paragraph) {
    $delta = $this->getComponentDeltaByUuid($paragraph->uuid());
    if ($delta > -1) {
      $this->paragraphsReferenceField[$delta]->entity = $paragraph;
    }
    else {
      $this->paragraphsReferenceField[] = $paragraph;
    }
    return $this;
  }

  /**
   * Reorder components.
   *
   * Accepts an associative of component uuids, parent uuids, and regions.
   *
   * @param array $ordered_items
   *   The nested array with the new order for items.
   *
   * @return $this
   */
  public function reorderComponents(array $ordered_items) {
    foreach ($ordered_items as $ordered_item) {
      if ($component = $this->getComponentByUuid($ordered_item['uuid'])) {
        $component->setSettings([
          'parent_uuid' => $ordered_item['parentUuid'],
          'region' => $ordered_item['region'],
        ]);
        $reordered_items[] = [
          'entity' => $component->getEntity(),
        ];
      }
    }
    $this->paragraphsReferenceField->setValue($reordered_items);
    return $this;
  }

  /**
   * Duplicates a component.
   *
   * @param string $source_uuid
   *   The source uuid of the component to duplicate.
   * @param string $target_section_uuid
   *   The uuid of the target section component.
   *   If null, the clone will be inserted in the same section, after
   *   the source. If set, the clone will be inserted in the target section,
   *   appended in the same region as the source.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsComponent
   *   The duplicated component.
   */
  public function duplicateComponent(string $source_uuid, string $target_section_uuid = NULL) {
    $source_component = $this->getComponentByUuid($source_uuid);
    $cloned_paragraph = $source_component->getEntity()->createDuplicate();
    if ($target_section_uuid) {
      $this->insertIntoRegion(
        $target_section_uuid,
        $source_component->getRegion(),
        $cloned_paragraph,
      );
    }
    else {
      $this->insertAfterComponent($source_uuid, $cloned_paragraph);
    }
    if ($source_component->isLayout()) {
      /** @var \Drupal\layout_paragraphs\LayoutParagraphsSection $section */
      $section = $this->getLayoutSection($source_component->getEntity());
      foreach ($section->getComponents() as $component) {
        $this->duplicateComponent($component->getEntity()->uuid(), $cloned_paragraph->uuid());
      }
    }
    return $this->getComponent($cloned_paragraph);
  }

  /**
   * Insert a paragraph component before an existing component.
   *
   * @param string $parent_uuid
   *   The parent component's uuid.
   * @param string $region
   *   The region.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph component to add.
   *
   * @return $this
   */
  public function insertIntoRegion(string $parent_uuid, string $region, ParagraphInterface $paragraph) {
    // Create a layout component for the new paragraph.
    $component = $this->getComponent($paragraph);
    // Make sure the parent component exists.
    if ($this->getComponentByUuid($parent_uuid)) {
      // Set the parent and region.
      $component->setSettings([
        'parent_uuid' => $parent_uuid,
        'region' => $region,
      ]);
      // Get the paragraph entity from the component.
      $new_paragraph = $component->getEntity();
      $new_paragraph->setParentEntity($this->getEntity(), $this->getFieldName());
      // Splice the new paragraph into the field item list.
      $item_list = $this->getParagraphsReferenceField();
      $list = $item_list->getValue();
      $list[] = ['entity' => $new_paragraph];
      $item_list->setValue($list);
      $this->setParagraphsReferenceField($item_list);
    }
    else {
      // @todo Throw exception.
    }
    return $this;
  }

  /**
   * Insert a paragraph component before an existing component.
   *
   * @param string $sibling_uuid
   *   The existing sibling paragraph component's uuid.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph component to add.
   *
   * @return $this
   */
  public function insertBeforeComponent(string $sibling_uuid, ParagraphInterface $paragraph) {
    return $this->insertSiblingComponent($sibling_uuid, $paragraph);
  }

  /**
   * Insert a paragraph component after an existing component.
   *
   * @param string $sibling_uuid
   *   The existing sibling paragraph component's uuid.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph component to add.
   *
   * @return $this
   */
  public function insertAfterComponent(string $sibling_uuid, ParagraphInterface $paragraph) {
    return $this->insertSiblingComponent($sibling_uuid, $paragraph, 1);
  }

  /**
   * Insert an new item adjacent to $sibling.
   *
   * @param string $sibling_uuid
   *   The existing sibling paragraph component's uuid.
   * @param \Drupal\paragraphs\ParagraphInterface $new_paragraph
   *   The paragraph component to add.
   * @param int $delta_offset
   *   Where to add the new item in relation to sibling.
   *
   * @return $this
   */
  protected function insertSiblingComponent(
    string $sibling_uuid,
    ParagraphInterface $new_paragraph,
    int $delta_offset = 0) {

    // Create a layout component for the new paragraph.
    $new_component = $this->getComponent($new_paragraph);
    // Find the existing sibling component, and copy the layout settings
    // into the new component to be inserted.
    if ($existing_component = $this->getComponentByUuid($sibling_uuid)) {
      // Copy layout settings into the new component.
      $sibling_settings = $existing_component->getSettings();
      $new_component_settings = [
        'parent_uuid' => $sibling_settings['parent_uuid'] ?? NULL,
        'region' => $sibling_settings['region'] ?? NULL,
      ];
      $new_component->setSettings($new_component_settings);
      // Get the paragraph entity from the component.
      $new_paragraph = $new_component->getEntity();
      $new_paragraph->setParentEntity($this->getEntity(), $this->getFieldName());
      // Splice the new paragraph into the field item list.
      $item_list = $this->getParagraphsReferenceField();
      $list = $item_list->getValue();
      $delta = $this->getComponentDeltaByUuid($sibling_uuid);
      $delta += $delta_offset;
      array_splice($list, $delta, 0, ['entity' => $new_paragraph]);
      $item_list->setValue($list);
      $this->setParagraphsReferenceField($item_list);
    }
    else {
      // @todo Throw exception.
    }
    return $this;
  }

  /**
   * Append a new component.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $new_paragraph
   *   The paragraph component to append.
   *
   * @return $this
   */
  public function appendComponent(ParagraphInterface $new_paragraph) {
    $new_paragraph->setParentEntity($this->getEntity(), $this->getFieldName());
    $this->paragraphsReferenceField->appendItem(['entity' => $new_paragraph]);
    return $this;
  }

  /**
   * Delete a component.
   *
   * @param string $uuid
   *   The uuid of the component to delete.
   * @param bool $recursive
   *   Recursively delete child components.
   *
   * @return $this
   */
  public function deleteComponent(string $uuid, $recursive = FALSE) {
    if ($recursive) {
      $component = $this->getComponentByUuid($uuid);
      if ($component->isLayout()) {
        /** @var \Drupal\layout_paragraphs\LayoutParagraphsSection $section */
        $section = $this->getLayoutSection($component->getEntity());
        foreach ($section->getComponents() as $component) {
          $this->deleteComponent($component->getEntity()->uuid(), TRUE);
        }
      }
    }
    $delta = $this->getComponentDeltaByUuid($uuid);
    if (isset($this->paragraphsReferenceField[$delta])) {
      unset($this->paragraphsReferenceField[$delta]);
    }
    return $this;
  }

  /**
   * Searchs for a component by its uuid and returns its delta.
   *
   * @param string $uuid
   *   The uuid to search for.
   *
   * @return int
   *   The component's delta, or -1 if no match.
   */
  protected function getComponentDeltaByUuid(string $uuid) {
    foreach ($this->paragraphsReferenceField as $key => $item) {
      if (isset($item->entity) && $item->entity->uuid() == $uuid) {
        return $key;
      }
    }
    return -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($provider, $key, $default = NULL) {
    return $this->thirdPartySettings[$provider][$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($provider) {
    return $this->thirdPartySettings[$provider] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($provider, $key, $value) {
    $this->thirdPartySettings[$provider][$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($provider, $key) {
    unset($this->thirdPartySettings[$provider][$key]);
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this provider.
    if (empty($this->thirdPartySettings[$provider])) {
      unset($this->thirdPartySettings[$provider]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return array_keys($this->thirdPartySettings);
  }

}
