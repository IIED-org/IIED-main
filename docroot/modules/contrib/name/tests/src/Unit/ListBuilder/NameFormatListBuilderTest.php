<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Entity\NameFormat;
use Drupal\name\ListBuilder\NameFormatListBuilder;
use Drupal\name\Service\GeneratorInterface;
use Drupal\name\Service\NameFormatParserInterface;
use Drupal\name\Service\NameFormatterInterface;

/**
 * @coversDefaultClass \Drupal\name\ListBuilder\NameFormatListBuilder
 *
 * @group name
 */
final class NameFormatListBuilderTest extends UnitTestCase {

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
   * @covers ::__construct
   */
  public function testConstructorUsesInjectedFormatter(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $builder = $this->createBuilder($formatter);

    $this->assertSame($formatter, $this->readProperty($builder, 'formatter'));
  }

  /**
   * @covers ::__construct
   */
  public function testConstructorFallsBackToDrupalFormatterService(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    \Drupal::getContainer()->set('name.formatter', $formatter);

    $builder = $this->createBuilder(NULL);

    $this->assertSame($formatter, $this->readProperty($builder, 'formatter'));
  }

  /**
   * @covers ::buildHeader
   */
  public function testBuildHeaderReturnsExpectedColumns(): void {
    $builder = $this->createBuilder($this->createMock(NameFormatterInterface::class));

    $this->assertSame([
      'label' => 'Label',
      'id' => 'Machine name',
      'format' => 'Format',
      'examples' => 'Examples',
      'operations' => 'Operations',
    ], array_map('strval', $builder->buildHeader()));
  }

  /**
   * @covers ::buildRow
   * @covers ::examples
   */
  public function testBuildRowBuildsExamplesAndOperations(): void {
    $generator = $this->createMock(GeneratorInterface::class);
    $generator->expects($this->once())
      ->method('loadSampleValues')
      ->with(4)
      ->willReturn([
        ['given' => 'Alpha'],
        ['given' => 'Beta'],
        ['given' => 'Gamma'],
        ['given' => 'Delta'],
      ]);

    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->expects($this->exactly(4))
      ->method('format')
      ->willReturnOnConsecutiveCalls('Alpha', '', 'Gamma', 'Delta');

    $builder = $this->createBuilder($formatter, $generator);
    $entity = $this->createEntity([
      'id' => 'formal',
      'label' => 'Formal',
      'pattern' => '!t !g !f',
    ]);

    $row = $builder->buildRow($entity);

    $this->assertSame('Formal', $row['label']);
    $this->assertSame('formal', $row['id']);
    $this->assertSame('!t !g !f', $row['format']);
    $this->assertSame(['#type' => 'operations'], $row['operations']['data']);

    $examples = (string) $row['examples'];
    $this->assertStringContainsString('(1) <em class="placeholder">Alpha</em>', $examples);
    $this->assertStringContainsString('&lt;&lt;empty&gt;&gt;', $examples);
    $this->assertStringContainsString('(4) <em class="placeholder">Delta</em>', $examples);
  }

  /**
   * @covers ::buildRow
   */
  public function testBuildRowThrowsForUnexpectedEntityType(): void {
    $builder = $this->createBuilder(
      $this->createMock(NameFormatterInterface::class),
    );

    $this->expectException(\LogicException::class);
    $builder->buildRow($this->createMock(EntityInterface::class));
  }

  /**
   * Creates a list builder under test.
   */
  private function createBuilder(?NameFormatterInterface $formatter, ?GeneratorInterface $generator = NULL): TestNameFormatListBuilder {
    $builder = new TestNameFormatListBuilder(
      $this->createEntityType(),
      $this->createMock(EntityStorageInterface::class),
      $this->createMock(NameFormatParserInterface::class),
      $generator ?? $this->createMock(GeneratorInterface::class),
      $formatter,
    );
    $builder->setStringTranslation($this->getStringTranslationStub());

    return $builder;
  }

  /**
   * Creates an entity instance for list rows.
   */
  private function createEntity(array $values): NameFormat {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->with('name_format')
      ->willReturn($this->createEntityType());
    \Drupal::getContainer()->set('entity_type.manager', $entity_type_manager);

    return new NameFormat($values, 'name_format');
  }

  /**
   * Creates a config entity type definition for unit tests.
   */
  private function createEntityType(): ConfigEntityType {
    return new ConfigEntityType([
      'id' => 'name_format',
      'label' => 'Name format',
      'config_prefix' => 'name_format',
      'entity_keys' => [
        'id' => 'id',
        'label' => 'label',
        'uuid' => 'uuid',
      ],
    ]);
  }

  /**
   * Reads a protected property from the builder.
   */
  private function readProperty(NameFormatListBuilder $builder, string $property): mixed {
    $reflection = new \ReflectionProperty(NameFormatListBuilder::class, $property);
    $reflection->setAccessible(TRUE);
    return $reflection->getValue($builder);
  }

}

/**
 * Testable list builder with deterministic operations output.
 */
final class TestNameFormatListBuilder extends NameFormatListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity): array {
    return ['#type' => 'operations'];
  }

}
