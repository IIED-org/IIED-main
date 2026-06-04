<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Service\AdditionalComponentService;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * @coversDefaultClass \Drupal\name\Service\AdditionalComponentService
 *
 * @group name
 */
class AdditionalComponentServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_name_acs',
      'entity_type' => 'node',
      'type' => 'name',
      'settings' => [
        'components' => [
          'title' => TRUE,
          'given' => TRUE,
          'middle' => TRUE,
          'family' => TRUE,
          'generational' => FALSE,
          'credentials' => FALSE,
        ],
      ],
    ]);
    $fieldStorage->save();

    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'page',
      'label' => 'Name',
      'settings' => [
        'components' => [
          'title' => TRUE,
          'given' => TRUE,
          'middle' => TRUE,
          'family' => TRUE,
          'generational' => FALSE,
          'credentials' => FALSE,
        ],
        'labels' => [
          'title' => 'Title',
          'given' => 'Given',
          'middle' => 'Middle',
          'family' => 'Family',
          'generational' => 'Generational',
          'credentials' => 'Credentials',
        ],
      ],
    ]);
    $fieldConfig->save();
  }

  /**
   * @covers ::getAdditionalComponent
   */
  public function testServiceRegistered(): void {
    $service = $this->container->get('name.additional_component');
    $this->assertInstanceOf(AdditionalComponentService::class, $service);
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelf
   */
  public function testSelfReturnsNodeTitle(): void {
    /** @var \Drupal\name\Service\AdditionalComponentService $service */
    $service = $this->container->get('name.additional_component');

    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel page title',
      'field_name_acs' => [
        'given' => 'Test',
        'family' => 'User',
      ],
    ]);
    $node->save();

    $items = $node->get('field_name_acs');
    $this->assertSame('Kernel page title', $service->getAdditionalComponent($items, '_self', ''));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelfProperty
   */
  public function testSelfPropertyReturnsNodeTitleValue(): void {
    /** @var \Drupal\name\Service\AdditionalComponentService $service */
    $service = $this->container->get('name.additional_component');

    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel property title',
      'field_name_acs' => [
        'given' => 'Test',
        'family' => 'User',
      ],
    ]);
    $node->save();

    $items = $node->get('field_name_acs');
    $this->assertSame(
      'Kernel property title',
      $service->getAdditionalComponent($items, '_self_property_title', ''),
    );
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testFieldNotFoundReturnsEmpty(): void {
    /** @var \Drupal\name\Service\AdditionalComponentService $service */
    $service = $this->container->get('name.additional_component');

    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel page title',
      'field_name_acs' => [
        'given' => 'Test',
        'family' => 'User',
      ],
    ]);
    $node->save();

    $items = $node->get('field_name_acs');
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_missing', ''));
  }

}
