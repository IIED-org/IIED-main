<?php

namespace Drupal\Tests\twig_field_value\Kernel;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @coversDefaultClass \Drupal\twig_field_value\Twig\Extension\FieldValueExtension
 * @group twig_field_value
 */
class FieldLabelTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'twig_field_value',
    'twig_field_value_test',
    'user',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_string',
      'type' => 'string',
      'entity_type' => 'entity_test',
      'cardinality' => FieldStorageConfigInterface::CARDINALITY_UNLIMITED,
    ]);
    $fieldStorage->save();
    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $fieldConfig->save();
    $current_user = $this->container->get('current_user');
    $current_user->setAccount($this->createUser(['view test entity']));
  }

  /**
   * Test field label filter.
   */
  public function testFieldLabel() {
    $entity = EntityTest::create([
      'field_string' => [
        'value',
      ],
    ]);
    $entity->save();

    $string_field = function (FieldableEntityInterface $entity) {
      return $entity->get('field_string')->view([
        'type' => 'string_hidden_third_child',
        'settings' => [
          'link' => FALSE,
        ],
      ]);
    };

    $element = $string_field($entity);

    // Check the field values by rendering the formatter without any filter.
    $content = \Drupal::service('renderer')->renderInIsolation($element);
    $this->assertStringContainsString('value', (string) $content);

    // Check output of the field_label filter.
    $element = [
      '#type' => 'inline_template',
      '#template' => '{{ field|field_label }}',
      '#context' => [
        'field' => $string_field($entity),
      ],
    ];
    $content = \Drupal::service('renderer')->renderInIsolation($element);
    $this->assertSame('field_string', (string) $content);
  }

  /**
   * Check if an inaccessible field is _not_ displayed.
   *
   * This test uses a field for which #access is set to false.
   */
  public function testFieldLabelAccess() {
    $entity = EntityTest::create([
      'field_string' => [
        'value',
      ],
    ]);
    $entity->save();

    $string_field = function (FieldableEntityInterface $entity) {
      return $entity->get('field_string')->view([
        'type' => 'string_hidden_field',
        'settings' => [
          'link' => FALSE,
        ],
      ]);
    };

    $element = $string_field($entity);

    // Check the field values by rendering the formatter without any filter.
    $content = \Drupal::service('renderer')->renderInIsolation($element);
    $this->assertStringNotContainsString('value', (string) $content);

    // Check output of the field_label filter.
    $element = [
      '#type' => 'inline_template',
      '#template' => '{{ field|field_label }}',
      '#context' => [
        'field' => $string_field($entity),
      ],
    ];
    $content = \Drupal::service('renderer')->renderInIsolation($element);
    $this->assertSame('', (string) $content);
  }

  /**
   * Checks if cache is propagated for a field with the field_label filter.
   */
  public function testFieldLabelCache() {
    $entity = EntityTest::create([
      'field_string' => [
        'value',
      ],
    ]);
    $entity->save();

    $string_field = function (FieldableEntityInterface $entity) {
      return $entity->get('field_string')->view([
        'type' => 'string_hidden_third_child',
      ]);
    };

    $field = $string_field($entity);

    // Apply cache and attachments to the field.
    $metadata = [
      '#attached' => [
        'library' => ['core/drupal', 'core/once'],
        'html_head_link' => ['test' => 'head'],
      ],
      '#cache' => [
        'tags' => ['test_label_tag', 'test_label_tag_2'],
        'contexts' => ['label_context'],
        'max-age' => 2,
      ],
    ];

    BubbleableMetadata::createFromRenderArray($metadata)->applyTo($field);

    $element = [
      '#type' => 'inline_template',
      '#template' => '{{ field|field_label }}',
      '#context' => [
        'field' => $field,
      ],
    ];

    // Check that cache and attachments from field are present.
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $renderer->executeInRenderContext($context, fn () => $renderer->render($element));

    $bubbled_metadata = [];
    $context->pop()->applyTo($bubbled_metadata);

    $this->assertEqualsCanonicalizing($metadata, $bubbled_metadata);
  }

}
