<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Hook;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Hook\TokenHooks;
use Drupal\name\Plugin\Field\FieldType\NameItem;

/**
 * @coversDefaultClass \Drupal\name\Hook\TokenHooks
 *
 * @group name
 */
class TokenTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    $this->container->get('entity_type.listener')
      ->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));

    $this->installNameField('field_name_test', 'entity_test', 'entity_test');
  }

  /**
   * Creates and saves a name field storage and field config.
   */
  private function installNameField(string $field_name, string $entity_type, string $bundle): void {
    FieldStorageConfig::create([
      'field_name'   => $field_name,
      'entity_type'  => $entity_type,
      'type'         => 'name',
    ])->save();
    FieldConfig::create([
      'field_name'   => $field_name,
      'entity_type'  => $entity_type,
      'bundle'       => $bundle,
      'type'         => 'name',
    ])->save();
  }

  /**
   * Creates a saved entity_test with a populated name field.
   */
  private function createEntityWithName(): EntityTest {
    $entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create([
        'bundle'           => 'entity_test',
        'field_name_test'  => [
          'given'   => 'Kernel',
          'family'  => 'Token',
        ],
      ]);
    $entity->save();
    return $entity;
  }

  /**
   * @covers ::alterTokenInfo
   */
  public function testAlterTokenInfoRegistersChainTypesAndFormatTokens(): void {
    /** @var \Drupal\name\Hook\TokenHooks $token */
    $token = $this->container->get(TokenHooks::class);
    $info = [
      'types'   => [],
      'tokens'  => [
        'entity_test' => [],
      ],
    ];
    $token->alterTokenInfo($info);

    $chain = 'name_formatted|entity_test|field_name_test';
    $this->assertArrayHasKey($chain, $info['types']);
    $this->assertSame('entity_test', $info['types'][$chain]['needs-data']);
    $this->assertArrayHasKey('given', $info['tokens'][$chain]);
    $this->assertArrayHasKey('formatted_field_name_test', $info['tokens']['entity_test']);
    $this->assertSame($chain, $info['tokens']['entity_test']['formatted_field_name_test']['type']);

    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_name_test');
    $this->assertNotNull($field);
    $description = $info['types'][$chain]['description'];
    $this->assertInstanceOf(TranslatableMarkup::class, $description);
    $this->assertStringContainsString(
      $field->getLabel(),
      (string) $description,
    );
  }

  /**
   * @covers ::alterTokenInfo
   */
  public function testAlterTokenInfoNestsFormattedWhenFieldSubTypeExists(): void {
    /** @var \Drupal\name\Hook\TokenHooks $token */
    $token = $this->container->get(TokenHooks::class);
    $sub_type = 'field_item_entity_test_field_name_test';
    $chain = 'name_formatted|entity_test|field_name_test';
    $info = [
      'types'   => [],
      'tokens'  => [
        'entity_test' => [
          'field_name_test' => [
            'type' => $sub_type,
          ],
        ],
        $sub_type => [
          'given' => [
            'name' => 'Given',
          ],
        ],
      ],
    ];
    $token->alterTokenInfo($info);

    $this->assertArrayHasKey('formatted', $info['tokens'][$sub_type]);
    $this->assertSame($chain, $info['tokens'][$sub_type]['formatted']['type']);
    $this->assertArrayNotHasKey('formatted_field_name_test', $info['tokens']['entity_test']);
  }

  /**
   * @covers ::getChainReplacements
   */
  public function testGetChainReplacementsUsesFormatterForRealEntity(): void {
    $entity = $this->createEntityWithName();
    /** @var \Drupal\name\NameFormatterInterface $formatter */
    $formatter = $this->container->get('name.formatter');
    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $entity->get('field_name_test')->get(0);
    $this->assertInstanceOf(NameItem::class, $item);
    $expected = (string) $formatter->format($item->filteredArray(), 'given');

    /** @var \Drupal\name\Hook\TokenHooks $token */
    $token = $this->container->get(TokenHooks::class);
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|entity_test|field_name_test';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[placeholder]'],
      ['entity_test' => $entity],
      ['langcode' => 'en'],
      $meta,
    );

    $this->assertSame(['[placeholder]' => $expected], $out);
    $this->assertNotEmpty($meta->getCacheTags());
  }

  /**
   * @covers ::alterReplacements
   */
  public function testAlterReplacementsLegacyColonAndPointerForms(): void {
    $entity = $this->createEntityWithName();
    /** @var \Drupal\name\NameFormatterInterface $formatter */
    $formatter = $this->container->get('name.formatter');
    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $entity->get('field_name_test')->get(0);
    $expected = (string) $formatter->format($item->filteredArray(), 'given');

    /** @var \Drupal\name\Hook\TokenHooks $token */
    $token = $this->container->get(TokenHooks::class);
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'entity_test',
      'tokens'  => [
        'field_name_test:formatted:given' => '[legacy]',
        'formatted_field_name_test:given'   => '[pointer]',
      ],
      'data'    => ['entity_test' => $entity],
      'options' => ['langcode' => 'en'],
    ], $meta);

    $this->assertSame($expected, $replacements['[legacy]']);
    $this->assertSame($expected, $replacements['[pointer]']);
    $this->assertNotEmpty($meta->getCacheTags());
  }

  /**
   * @covers ::alterReplacements
   */
  public function testAlterReplacementsWithExplicitDeltaOnMultiValueField(): void {
    FieldStorageConfig::create([
      'field_name'   => 'field_multi',
      'entity_type'  => 'entity_test',
      'type'         => 'name',
      'cardinality'  => 2,
    ])->save();
    FieldConfig::create([
      'field_storage'  => FieldStorageConfig::loadByName('entity_test', 'field_multi'),
      'bundle'         => 'entity_test',
      'entity_type'    => 'entity_test',
    ])->save();

    $entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create([
        'bundle'      => 'entity_test',
        'field_multi' => [
          [
            'given'   => 'First',
            'family'  => 'One',
          ],
          [
            'given'   => 'Second',
            'family'  => 'Two',
          ],
        ],
      ]);
    $entity->save();

    /** @var \Drupal\name\NameFormatterInterface $formatter */
    $formatter = $this->container->get('name.formatter');
    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $entity->get('field_multi')->get(1);
    $expected = (string) $formatter->format($item->filteredArray(), 'given');

    /** @var \Drupal\name\Hook\TokenHooks $token */
    $token = $this->container->get(TokenHooks::class);
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'entity_test',
      'tokens'  => [
        'field_multi:1:formatted:given' => '[delta]',
      ],
      'data'    => ['entity_test' => $entity],
      'options' => ['langcode' => 'en'],
    ], $meta);

    $this->assertSame($expected, $replacements['[delta]']);
  }

  /**
   * Invalid format id falls back to default format output.
   *
   * @covers ::alterReplacements
   */
  public function testAlterReplacementsFallsBackForUnknownFormatId(): void {
    $entity = $this->createEntityWithName();
    /** @var \Drupal\name\NameFormatterInterface $formatter */
    $formatter = $this->container->get('name.formatter');
    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $entity->get('field_name_test')->get(0);
    $expected = (string) $formatter->format(
      $item->filteredArray(),
      'no_such_format____kernel',
    );

    /** @var \Drupal\name\Hook\TokenHooks $token */
    $token = $this->container->get(TokenHooks::class);
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'entity_test',
      'tokens'  => [
        'field_name_test:formatted:no_such_format____kernel' => '[bad]',
      ],
      'data'    => ['entity_test' => $entity],
      'options' => ['langcode' => 'en'],
    ], $meta);

    $this->assertSame($expected, $replacements['[bad]']);
  }

}
