<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests adding a new layout section to layout paragraphs.
 *
 * @group layout_paragraphs
 */
class CustomHostEntityTest extends BuilderTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_paragraphs',
    'paragraphs',
    'node',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
    'layout_paragraphs_custom_host_entity_test',
  ];

  /**
   * The URL to use to add content.
   *
   * @var string
   */
  protected $contentAddUrl = 'admin/content/lp-host-entity/add';

  /**
   * The URL to use to edit content.
   *
   * @var string
   */
  protected $contentEditUrl = 'admin/content/lp-host-entity/1/edit';

  /**
   * List of admin permissions.
   *
   * @var array
   */
  protected $adminPermissions = [
    'administer site configuration',
    'administer lp host entity',
    'administer paragraphs types',
  ];

  /**
   * List of content creation related permissions.
   *
   * @var array
   */
  protected $contentPermissions = [
    'administer lp host entity',
  ];

  /**
   * {@inheritDoc}
   *
   * Adds fields to the custom host entity rather than a content type.
   */
  protected function addLayoutParagraphedContentType($type_name, $paragraphs_field_name) {

    $entity_type = 'lp_host_entity';
    $field_storage = FieldStorageConfig::loadByName($entity_type, $paragraphs_field_name);
    if (!$field_storage) {
      // Add a paragraphs field.
      $field_storage = FieldStorageConfig::create([
        'field_name' => $paragraphs_field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference_revisions',
        'cardinality' => '-1',
        'settings' => [
          'target_type' => 'paragraph',
        ],
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity_type,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL, 'negate' => TRUE],
      ],
    ]);
    $field->save();

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay($entity_type, $entity_type);
    $form_display = $form_display->setComponent($paragraphs_field_name, ['type' => 'layout_paragraphs']);
    $form_display->save();

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay($entity_type, $entity_type);
    $view_display->setComponent($paragraphs_field_name, ['type' => 'layout_paragraphs']);
    $view_display->save();
  }

}
