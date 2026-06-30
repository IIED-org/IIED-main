<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Hook;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Entity\NameFormatInterface;
use Drupal\name\Hook\TokenHooks;
use Drupal\name\Plugin\Field\FieldType\NameItem;
use Drupal\name\Service\NameFormatterInterface;

/**
 * @coversDefaultClass \Drupal\name\Hook\TokenHooks
 *
 * @group name
 */
class TokenHooksTest extends UnitTestCase {

  /**
   * @covers ::alterTokenInfo
   */
  public function testAlterTokenInfoSkipsWhenEntityTokenGroupMissing(): void {
    $field_map = [
      'node' => [
        'field_realname' => [
          'type'     => 'name',
          'bundles'  => [],
        ],
      ],
    ];
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldMapByFieldType')
      ->with('name')
      ->willReturn($field_map);

    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('loadMultiple')->willReturn([]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $token = new TokenHooks(
      $entity_field_manager,
      $entity_type_manager,
      $this->createMock(NameFormatterInterface::class),
    );

    $info = [
      'types'   => [],
      'tokens'  => ['user' => []],
    ];
    $token->alterTokenInfo($info);

    $this->assertArrayNotHasKey('name_formatted|node|field_realname', $info['types']);
  }

  /**
   * @covers ::alterTokenInfo
   */
  public function testAlterTokenInfoAddsFallbackFormattedPointerWhenNoFieldSubType(): void {
    $field_map = [
      'node' => [
        'field_realname' => [
          'type'     => 'name',
          'bundles'  => [],
        ],
      ],
    ];
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldMapByFieldType')
      ->with('name')
      ->willReturn($field_map);

    $format = $this->createMock(NameFormatInterface::class);
    $format->method('id')->willReturn('given');
    $format->method('label')->willReturn('Given');

    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('loadMultiple')->willReturn([$format]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $name_formatter = $this->createMock(NameFormatterInterface::class);
    $name_formatter->method('format')
      ->willReturn('Mr. John Q. Public, Sr., PhD');

    $token = new TokenHooks($entity_field_manager, $entity_type_manager, $name_formatter);

    $chain_type = 'name_formatted|node|field_realname';
    $info = [
      'types'   => [],
      'tokens'  => [
        'node' => [],
      ],
    ];
    $token->alterTokenInfo($info);

    $this->assertArrayHasKey($chain_type, $info['types']);
    $this->assertArrayHasKey('given', $info['tokens'][$chain_type]);
    $this->assertArrayHasKey('formatted_field_realname', $info['tokens']['node']);
    $this->assertSame($chain_type, $info['tokens']['node']['formatted_field_realname']['type']);
  }

  /**
   * @covers ::alterTokenInfo
   */
  public function testAlterTokenInfoNestsFormattedUnderFieldSubType(): void {
    $field_map = [
      'node' => [
        'field_realname' => [
          'type'     => 'name',
          'bundles'  => [],
        ],
      ],
    ];
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->method('getFieldMapByFieldType')
      ->with('name')
      ->willReturn($field_map);

    $format = $this->createMock(NameFormatInterface::class);
    $format->method('id')->willReturn('given');
    $format->method('label')->willReturn('Given');

    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('loadMultiple')->willReturn([$format]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $name_formatter = $this->createMock(NameFormatterInterface::class);
    $name_formatter->method('format')->willReturn('demo');

    $token = new TokenHooks($entity_field_manager, $entity_type_manager, $name_formatter);

    $sub_type = 'field_item_node_field_realname';
    $chain_type = 'name_formatted|node|field_realname';
    $info = [
      'types'   => [],
      'tokens'  => [
        'node' => [
          'field_realname' => [
            'type' => $sub_type,
          ],
        ],
        $sub_type => [
          'given' => ['name' => 'Given'],
        ],
      ],
    ];
    $token->alterTokenInfo($info);

    $this->assertArrayHasKey('formatted', $info['tokens'][$sub_type]);
    $this->assertSame($chain_type, $info['tokens'][$sub_type]['formatted']['type']);
    $this->assertArrayNotHasKey('formatted_field_realname', $info['tokens']['node']);
  }

  /**
   * @covers ::getChainReplacements
   */
  public function testGetChainReplacementsReturnsEmptyForWrongTypePrefix(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $out = $token->getChainReplacements('node', ['given' => '[x]'], [], [], $meta);
    $this->assertSame([], $out);
  }

  /**
   * @covers ::getChainReplacements
   */
  public function testGetChainReplacementsReturnsEmptyForIncompleteType(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $out = $token->getChainReplacements('name_formatted|node', ['given' => '[x]'], [], [], $meta);
    $this->assertSame([], $out);
  }

  /**
   * @covers ::getChainReplacements
   */
  public function testGetChainReplacementsReturnsEmptyWhenEntityMissing(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|node|field_name';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[tok]'],
      [],
      [],
      $meta,
    );
    $this->assertSame([], $out);
  }

  /**
   * @covers ::getChainReplacements
   * @covers ::formattedValue
   */
  public function testGetChainReplacementsSuccess(): void {
    $components = [
      'title'          => '',
      'given'          => 'Pat',
      'middle'         => '',
      'family'         => 'Lee',
      'generational'   => '',
      'credentials'    => '',
    ];
    $name_item = $this->createMock(NameItem::class);
    $name_item->method('filteredArray')->willReturn($components);

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('name');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('count')->willReturn(1);
    $list->method('get')->with(0)->willReturn($name_item);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);
    $entity->method('getCacheTags')->willReturn(['entity_test:1']);
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    $used_format = $this->createMock(NameFormatInterface::class);
    $used_format->method('getCacheTags')->willReturn(['config:name_format.given']);
    $used_format->method('getCacheContexts')->willReturn([]);
    $used_format->method('getCacheMaxAge')->willReturn(-1);

    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('load')
      ->willReturnCallback(static function (string $id) use ($used_format): ?object {
        return match ($id) {
          'given' => $used_format,
          default => NULL,
        };
      });

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $name_formatter = $this->createMock(NameFormatterInterface::class);
    $name_formatter->expects($this->once())
      ->method('format')
      ->with($components, 'given', NULL)
      ->willReturn('Pat Lee');

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    $token = new TokenHooks($entity_field_manager, $entity_type_manager, $name_formatter);
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|node|field_name';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[node:formatted_field_name:given]'],
      ['node' => $entity],
      ['langcode' => NULL],
      $meta,
    );

    $this->assertSame(
      ['[node:formatted_field_name:given]' => 'Pat Lee'],
      $out,
    );
    $this->assertNotEmpty($meta->getCacheTags());
  }

  /**
   * @covers ::getChainReplacements
   * @covers ::formattedValue
   */
  public function testGetChainReplacementsAddsCacheableFieldItemListDependency(): void {
    $components = [
      'title'          => '',
      'given'          => 'Pat',
      'middle'         => '',
      'family'         => 'Lee',
      'generational'   => '',
      'credentials'    => '',
    ];
    $name_item = $this->createMock(NameItem::class);
    $name_item->method('filteredArray')->willReturn($components);

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('name');

    $list = $this->createCacheableFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('count')->willReturn(1);
    $list->method('get')->with(0)->willReturn($name_item);

    $entity = $this->createMock(ContentEntityInterface::class);
    $this->stubMockCacheMetadata($entity);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);

    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('load')->willReturn(NULL);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $name_formatter = $this->createMock(NameFormatterInterface::class);
    $name_formatter->method('format')->willReturn('Pat Lee');

    $token = new TokenHooks(
      $this->createMock(EntityFieldManagerInterface::class),
      $entity_type_manager,
      $name_formatter,
    );
    $meta = new BubbleableMetadata();
    $out = $token->getChainReplacements(
      'name_formatted|node|field_name',
      ['given' => '[x]'],
      ['node' => $entity],
      [],
      $meta,
    );

    $this->assertSame(['[x]' => 'Pat Lee'], $out);
    $this->assertContains('cacheable_field_item_list', $meta->getCacheTags());
  }

  /**
   * @covers ::getChainReplacements
   * @covers ::formattedValue
   */
  public function testGetChainReplacementsReturnsEmptyWhenFieldMissing(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->with('field_name')->willReturn(FALSE);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|node|field_name';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[x]'],
      ['node' => $entity],
      [],
      $meta,
    );
    $this->assertSame(['[x]' => ''], $out);
  }

  /**
   * @covers ::getChainReplacements
   * @covers ::formattedValue
   */
  public function testGetChainReplacementsReturnsEmptyWhenFieldNotNameType(): void {
    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('string');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|node|field_name';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[x]'],
      ['node' => $entity],
      [],
      $meta,
    );
    $this->assertSame(['[x]' => ''], $out);
  }

  /**
   * @covers ::getChainReplacements
   * @covers ::formattedValue
   */
  public function testGetChainReplacementsReturnsEmptyWhenListEmpty(): void {
    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('name');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);
    $list->method('isEmpty')->willReturn(TRUE);

    $entity = $this->createMock(ContentEntityInterface::class);
    $this->stubMockCacheMetadata($entity);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|node|field_name';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[x]'],
      ['node' => $entity],
      [],
      $meta,
    );
    $this->assertSame(['[x]' => ''], $out);
  }

  /**
   * @covers ::getChainReplacements
   * @covers ::formattedValue
   */
  public function testGetChainReplacementsReturnsEmptyWhenItemNotNameItem(): void {
    $wrong_item = $this->createMock(FieldItemInterface::class);

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('name');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('count')->willReturn(1);
    $list->method('get')->with(0)->willReturn($wrong_item);

    $entity = $this->createMock(ContentEntityInterface::class);
    $this->stubMockCacheMetadata($entity);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $type = 'name_formatted|node|field_name';
    $out = $token->getChainReplacements(
      $type,
      ['given' => '[x]'],
      ['node' => $entity],
      [],
      $meta,
    );
    $this->assertSame(['[x]' => ''], $out);
  }

  /**
   * @covers ::alterReplacements
   */
  public function testAlterReplacementsNoOpWhenTokensEmpty(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $replacements = ['keep' => 'yes'];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [],
      'data'    => [],
      'options' => [],
    ], $meta);
    $this->assertSame(['keep' => 'yes'], $replacements);
  }

  /**
   * @covers ::alterReplacements
   */
  public function testAlterReplacementsNoOpWhenEntityMissing(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => ['field_name:formatted:given' => '[t]'],
      'data'    => [],
      'options' => [],
    ], $meta);
    $this->assertSame([], $replacements);
  }

  /**
   * @covers ::alterReplacements
   * @covers ::formattedValue
   * @covers ::parseFormattedLegacyColon
   * @covers ::parseFormattedName
   * @covers ::parseFormattedPointerToken
   */
  public function testAlterReplacementsLegacyAndPointerSyntax(): void {
    $components = [
      'title'          => '',
      'given'          => 'A',
      'middle'         => '',
      'family'         => 'B',
      'generational'   => '',
      'credentials'    => '',
    ];
    $name_item = $this->createMock(NameItem::class);
    $name_item->method('filteredArray')->willReturn($components);

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('name');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('count')->willReturn(2);
    $list->method('get')->willReturnMap([
      [0, $name_item],
      [1, $name_item],
    ]);

    $entity = $this->createMock(ContentEntityInterface::class);
    $this->stubMockCacheMetadata($entity);
    $entity->method('hasField')->willReturnCallback(static function (string $name): bool {
      return $name === 'field_name';
    });
    $entity->method('get')->with('field_name')->willReturn($list);

    $used_format = $this->createMock(NameFormatInterface::class);
    $this->stubMockCacheMetadata($used_format);
    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('load')
      ->willReturnCallback(static function (string $id) use ($used_format): ?object {
        return $id === 'given' ? $used_format : NULL;
      });

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $name_formatter = $this->createMock(NameFormatterInterface::class);
    $name_formatter->method('format')->willReturn('formatted-out');

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $token = new TokenHooks($entity_field_manager, $entity_type_manager, $name_formatter);

    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [
        'field_name:formatted:given'   => '[legacy]',
        'field_name:1:formatted:given' => '[delta]',
        'formatted_field_name:given'     => '[pointer]',
        'unrelated'                      => '[skip]',
      ],
      'data'    => ['node' => $entity],
      'options' => ['langcode' => 'en'],
    ], $meta);

    $this->assertSame('formatted-out', $replacements['[legacy]']);
    $this->assertSame('formatted-out', $replacements['[delta]']);
    $this->assertSame('formatted-out', $replacements['[pointer]']);
    $this->assertArrayNotHasKey('[skip]', $replacements);
  }

  /**
   * @covers ::alterReplacements
   */
  public function testAlterReplacementsIgnoresNonMatchingTokenNames(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $entity = $this->createMock(ContentEntityInterface::class);
    $meta = new BubbleableMetadata();
    $replacements = ['[existing]' => 'unchanged'];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [
        'field_name:given' => '[existing]',
      ],
      'data'    => ['node' => $entity],
      'options' => [],
    ], $meta);
    $this->assertSame('unchanged', $replacements['[existing]']);
  }

  /**
   * @covers ::fieldSubType
   */
  public function testFieldSubTypeReturnsNullWhenEntityTokensMissing(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $info = [
      'types'  => [],
      'tokens' => [],
    ];
    $this->assertNull($this->invokeFieldSubType($token, $info, 'node', 'field_x'));
  }

  /**
   * @covers ::fieldSubType
   */
  public function testFieldSubTypeReturnsNullWhenEntityTokensNotArray(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $info = [
      'types'  => [],
      'tokens' => [
        'node' => 'not-an-array',
      ],
    ];
    $this->assertNull($this->invokeFieldSubType($token, $info, 'node', 'field_x'));
  }

  /**
   * @covers ::fieldSubType
   */
  public function testFieldSubTypeResolvesViaDeltaTokenKey(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $sub_type = 'field_item_node_field_x';
    $info = [
      'types'  => [],
      'tokens' => [
        'node' => [
          'field_x' => [
            'name' => 'Name field',
          ],
          'field_x:0' => [
            'type' => $sub_type,
          ],
        ],
        $sub_type => [
          'given' => ['name' => 'Given'],
        ],
      ],
    ];
    $this->assertSame(
      $sub_type,
      $this->invokeFieldSubType($token, $info, 'node', 'field_x'),
    );
  }

  /**
   * @covers ::fieldSubType
   */
  public function testFieldSubTypeReturnsNullWhenSubTypeSubtreeMissing(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $info = [
      'types'  => [],
      'tokens' => [
        'node' => [
          'field_x' => [
            'type' => 'missing_sub_type',
          ],
        ],
      ],
    ];
    $this->assertNull($this->invokeFieldSubType($token, $info, 'node', 'field_x'));
  }

  /**
   * @covers ::fieldSubType
   */
  public function testFieldSubTypeReturnsNullWhenSubTypeSubtreeNotArray(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $sub_type = 'field_item_node_field_x';
    $info = [
      'types'  => [],
      'tokens' => [
        'node' => [
          'field_x' => [
            'type' => $sub_type,
          ],
        ],
        $sub_type => 'not-an-array',
      ],
    ];
    $this->assertNull($this->invokeFieldSubType($token, $info, 'node', 'field_x'));
  }

  /**
   * @covers ::alterReplacements
   * @covers ::parseFormattedLegacyColon
   * @covers ::parseFormattedName
   * @covers ::parseFormattedPointerToken
   */
  public function testAlterReplacementsIgnoresFormattedPointerWithEmptyFieldName(): void {
    $token = $this->createTokenWithFieldAndFormatMocks();
    $entity = $this->createMock(ContentEntityInterface::class);
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [
        'formatted_:given' => '[empty-field]',
      ],
      'data'    => ['node' => $entity],
      'options' => [],
    ], $meta);
    $this->assertArrayNotHasKey('[empty-field]', $replacements);
  }

  /**
   * @covers ::alterReplacements
   * @covers ::formattedValue
   * @covers ::parseFormattedLegacyColon
   * @covers ::parseFormattedName
   */
  public function testAlterReplacementsReturnsEmptyWhenFieldMissing(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->with('field_name')->willReturn(FALSE);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [
        'field_name:formatted:given' => '[legacy]',
      ],
      'data'    => ['node' => $entity],
      'options' => [],
    ], $meta);
    $this->assertSame(['[legacy]' => ''], $replacements);
  }

  /**
   * @covers ::alterReplacements
   * @covers ::formattedValue
   * @covers ::parseFormattedLegacyColon
   * @covers ::parseFormattedName
   */
  public function testAlterReplacementsReturnsEmptyWhenFieldNotNameType(): void {
    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('string');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [
        'field_name:formatted:given' => '[legacy]',
      ],
      'data'    => ['node' => $entity],
      'options' => [],
    ], $meta);
    $this->assertSame(['[legacy]' => ''], $replacements);
  }

  /**
   * @covers ::alterReplacements
   * @covers ::formattedValue
   * @covers ::parseFormattedLegacyColon
   * @covers ::parseFormattedName
   */
  public function testAlterReplacementsReturnsEmptyWhenItemNotNameItem(): void {
    $wrong_item = $this->createMock(FieldItemInterface::class);

    $field_def = $this->createMock(FieldDefinitionInterface::class);
    $field_def->method('getType')->willReturn('name');

    $list = $this->createFieldItemListMock();
    $list->method('getFieldDefinition')->willReturn($field_def);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('count')->willReturn(1);
    $list->method('get')->with(0)->willReturn($wrong_item);

    $entity = $this->createMock(ContentEntityInterface::class);
    $this->stubMockCacheMetadata($entity);
    $entity->method('hasField')->with('field_name')->willReturn(TRUE);
    $entity->method('get')->with('field_name')->willReturn($list);

    $token = $this->createTokenWithFieldAndFormatMocks();
    $meta = new BubbleableMetadata();
    $replacements = [];
    $token->alterReplacements($replacements, [
      'type'    => 'node',
      'tokens'  => [
        'field_name:formatted:given' => '[legacy]',
      ],
      'data'    => ['node' => $entity],
      'options' => [],
    ], $meta);
    $this->assertSame(['[legacy]' => ''], $replacements);
  }

  /**
   * Invokes the private fieldSubType() method for branch coverage.
   */
  private function invokeFieldSubType(
    TokenHooks $token_hooks,
    array $info,
    string $entity_type_id,
    string $field_name,
  ): ?string {
    $method = new \ReflectionMethod(TokenHooks::class, 'fieldSubType');
    $method->setAccessible(TRUE);
    return $method->invoke($token_hooks, $info, $entity_type_id, $field_name);
  }

  /**
   * PHPUnit mock of core’s field list class.
   *
   * Drupal 10 ships PHPUnit 9, which has no
   * `createStubForIntersectionOfInterfaces()`. `FieldItemList` is the default
   * `list_class` for field types; it is not `CacheableDependencyInterface`, so
   * cache metadata for the list is not stubbed here (see core
   * `RefinableCacheableDependencyTrait::addCacheableDependency()`).
   *
   * @return \Drupal\Core\Field\FieldItemList&\PHPUnit\Framework\MockObject\MockObject
   *   A mock field item list for tests.
   */
  private function createFieldItemListMock(): FieldItemList {
    return $this->createMock(FieldItemList::class);
  }

  /**
   * PHPUnit mock for a field list that is cacheable.
   *
   * @return \Drupal\Tests\name\Unit\Hook\CacheableFieldItemList&\PHPUnit\Framework\MockObject\MockObject
   *   A cacheable field item list mock for tests.
   */
  private function createCacheableFieldItemListMock(): CacheableFieldItemList {
    return $this->getMockBuilder(CacheableFieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getFieldDefinition', 'isEmpty', 'count', 'get'])
      ->getMock();
  }

  /**
   * Stubs cache metadata on a CacheableDependencyInterface test double.
   *
   * BubbleableMetadata::addCacheableDependency() requires arrays from
   * getCacheContexts()/getCacheTags(); PHPUnit mocks return NULL by default.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface&\PHPUnit\Framework\MockObject\MockObject $object
   *   The object to stub.
   */
  private function stubMockCacheMetadata(CacheableDependencyInterface $object): void {
    $object->method('getCacheTags')->willReturn([]);
    $object->method('getCacheContexts')->willReturn([]);
    $object->method('getCacheMaxAge')->willReturn(-1);
  }

  /**
   * Builds a TokenHooks with format storage that returns NULL for all ids.
   */
  private function createTokenWithFieldAndFormatMocks(): TokenHooks {
    $format_storage = $this->createMock(EntityStorageInterface::class);
    $format_storage->method('load')->willReturn(NULL);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('name_format')
      ->willReturn($format_storage);

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    return new TokenHooks(
      $entity_field_manager,
      $entity_type_manager,
      $this->createMock(NameFormatterInterface::class),
    );
  }

}

/**
 * Cacheable list test double for branch coverage.
 */
class CacheableFieldItemList extends FieldItemList implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return ['cacheable_field_item_list'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return -1;
  }

}
