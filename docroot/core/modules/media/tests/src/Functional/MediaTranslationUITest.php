<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Media Translation UI.
 *
 * @group media
 */
class MediaTranslationUITest extends ContentTranslationUITestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'session',
    'theme',
    'url.path',
    'url.query_args',
    'user.permissions',
    'user.roles:authenticated',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'media',
    'media_test_source',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = 'media';
    $this->bundle = 'test';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function setupBundle() {
    $this->createMediaType('test', [
      'id' => $this->bundle,
      'queue_thumbnail_downloads' => FALSE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), [
      'administer media',
      'edit any test media',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return ['administer media', 'create test media'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), [
      'access administration pages',
      'administer media types',
      'access media overview',
      'administer languages',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return [
      'name' => [['value' => $this->randomMachineName()]],
      'field_media_test' => [['value' => $this->randomMachineName()]],
    ] + parent::getNewEntityValues($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function testMediaThumbnailFormatter() {
    // Create Media Image type.
    $media_type = $this->createMediaType('image');
    $media_type_id = $media_type->id();
    $media_type->setFieldMap(['name' => 'name']);
    $media_type->save();

    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();

    // Media create with Image.
    $media = Media::create([
      'name' => 'Custom name',
      'bundle' => $media_type_id,
      'field_media_image' => $file->id(),
    ]);
    $media->save();

    // Create page content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Create an Media reference field.
    FieldStorageConfig::create([
      'field_name' => 'media_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'media_reference',
      'label' => 'Media field',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    // Create node with media image.
    $title = $this->randomString();
    $this->drupalCreateNode([
      'type' => 'page',
      'title' => $title,
      'langcode' => 'en',
      'media_reference' => $media->id(),
    ]);
    // Create Translated node.
    $node = $this->drupalGetNodeByTitle($title);
    $translation = $node->addTranslation('fr');
    $translation->title->value = $this->randomString();
    $translation->media_reference->target_id = $media->id();
    $node->save();

    // Set Thumbnail Media view in node view.
    $display_options = [
      'type' => 'media_thumbnail',
      'settings' => ['image_link' => 'content'],
    ];
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'page', 'full')
      ->setComponent('media_reference', $display_options)
      ->save();

    // Node view.
    $fr_node = $node->getTranslation('fr');
    $this->drupalGet($fr_node->toUrl());
    $this->assertSession()->linkByHrefExists('fr/node/' . $fr_node->id());
  }

}
