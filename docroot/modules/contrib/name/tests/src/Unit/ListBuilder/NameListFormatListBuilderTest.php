<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Entity\NameListFormat;
use Drupal\name\ListBuilder\NameListFormatListBuilder;
use Drupal\name\Service\GeneratorInterface;
use Drupal\name\Service\NameFormatParserInterface;
use Drupal\name\Service\NameFormatterInterface;

/**
 * @coversDefaultClass \Drupal\name\ListBuilder\NameListFormatListBuilder
 *
 * @group name
 */
final class NameListFormatListBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::buildHeader
   */
  public function testBuildHeaderReturnsExpectedColumns(): void {
    $builder = $this->createBuilder(
      $this->createMock(NameFormatterInterface::class),
      $this->createMock(GeneratorInterface::class),
    );

    $this->assertSame([
      'label' => 'Label',
      'id' => 'Machine name',
      'settings' => 'Settings',
      'examples' => 'Examples',
      'operations' => 'Operations',
    ], array_map('strval', $builder->buildHeader()));
  }

  /**
   * @covers ::buildRow
   * @covers ::examples
   */
  public function testBuildRowSummarizesValidSettingsAndExamples(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->method('getLastDelimiterTypes')
      ->willReturn([
        'text' => 'and',
      ]);
    $formatter->method('getLastDelimiterBehaviors')
      ->with(FALSE)
      ->willReturn([
        'always' => 'Always',
      ]);
    $formatter->method('formatList')
      ->willReturnCallback(static fn (array $names): string => 'List ' . count($names));

    $generator = $this->createMock(GeneratorInterface::class);
    $generator->method('generateSampleNames')
      ->willReturnCallback(static fn (int $count): array => array_fill(0, $count, ['family' => 'Example']));

    $builder = $this->createBuilder($formatter, $generator);
    $entity = $this->createEntity([
      'id' => 'family',
      'label' => 'Family list',
      'delimiter' => '; ',
      'and' => 'text',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => 3,
      'el_al_first' => 2,
    ]);

    $row = $builder->buildRow($entity);

    $this->assertSame('Family list', $row['label']);
    $this->assertSame('family', $row['id']);
    $this->assertSame(['#type' => 'operations'], $row['operations']['data']);

    $settings = (string) $row['settings'];
    $this->assertStringContainsString(
      'Reduce after 3 items and show 2 items followed by <em>el al</em>.',
      $settings,
    );
    $this->assertStringContainsString('Delimiters: "; " and and', $settings);
    $this->assertStringContainsString('Last delimiter: Always', $settings);

    $examples = (string) $row['examples'];
    $this->assertStringContainsString('(1) <em class="placeholder">List 1</em>', $examples);
    $this->assertStringContainsString('(4) <em class="placeholder">List 4</em>', $examples);
  }

  /**
   * @covers ::buildRow
   * @covers ::examples
   */
  public function testBuildRowUsesFallbackLabelsAndLockedSummary(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->method('getLastDelimiterTypes')
      ->willReturn([
        'symbol' => '&',
      ]);
    $formatter->method('getLastDelimiterBehaviors')
      ->with(FALSE)
      ->willReturn([
        'contextual' => 'Contextual',
      ]);
    $formatter->method('formatList')
      ->willReturn('Locked list');

    $generator = $this->createMock(GeneratorInterface::class);
    $generator->method('generateSampleNames')
      ->willReturn([['family' => 'Locked']]);

    $builder = $this->createBuilder($formatter, $generator);
    $entity = $this->createEntity([
      'id' => 'locked_default',
      'label' => 'Locked default',
      'delimiter' => ', ',
      'and' => 'invalid',
      'delimiter_precedes_last' => 'missing',
      'el_al_min' => 0,
      'el_al_first' => 1,
      'locked' => TRUE,
    ]);

    $row = $builder->buildRow($entity);
    $settings = (string) $row['settings'];

    $this->assertStringContainsString('Show all names.', $settings);
    $this->assertStringContainsString('-- invalid option --', $settings);
    $this->assertStringContainsString('Default format (locked)', $settings);
    $this->assertStringContainsString(
      '(1) <em class="placeholder">Locked list</em>',
      (string) $row['examples'],
    );
  }

  /**
   * @covers ::buildRow
   */
  public function testBuildRowThrowsForUnexpectedEntityType(): void {
    $builder = $this->createBuilder(
      $this->createMock(NameFormatterInterface::class),
      $this->createMock(GeneratorInterface::class),
    );

    $this->expectException(\LogicException::class);
    $builder->buildRow($this->createMock(EntityInterface::class));
  }

  /**
   * Creates a list builder under test.
   */
  private function createBuilder(NameFormatterInterface $formatter, GeneratorInterface $generator): TestNameListFormatListBuilder {
    $builder = new TestNameListFormatListBuilder(
      $this->createEntityType(),
      $this->createMock(EntityStorageInterface::class),
      $formatter,
      $this->createMock(NameFormatParserInterface::class),
      $generator,
    );
    $builder->setStringTranslation($this->getStringTranslationStub());

    return $builder;
  }

  /**
   * Creates an entity instance for list rows.
   */
  private function createEntity(array $values): NameListFormat {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->with('name_list_format')
      ->willReturn($this->createEntityType());
    \Drupal::getContainer()->set('entity_type.manager', $entity_type_manager);

    return new NameListFormat($values, 'name_list_format');
  }

  /**
   * Creates a config entity type definition for unit tests.
   */
  private function createEntityType(): ConfigEntityType {
    return new ConfigEntityType([
      'id' => 'name_list_format',
      'label' => 'Name list format',
      'config_prefix' => 'name_list_format',
      'entity_keys' => [
        'id' => 'id',
        'label' => 'label',
        'uuid' => 'uuid',
      ],
    ]);
  }

}

/**
 * Testable list builder with deterministic operations output.
 */
final class TestNameListFormatListBuilder extends NameListFormatListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity): array {
    return ['#type' => 'operations'];
  }

}
