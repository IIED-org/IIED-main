<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Plugin\diff\Field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Plugin\diff\Field\NameFieldBuilder;
use Drupal\name\Service\FormatOptionInterface;
use Drupal\name\Service\NameFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the name field diff builder plugin.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Plugin\diff\Field\NameFieldBuilder
 */
final class NameFieldBuilderTest extends UnitTestCase {

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
   * Creates the plugin under test.
   */
  private function buildPlugin(
    array $configuration,
    NameFormatterInterface $formatter,
    ?FormatOptionInterface $format_options,
  ): object {
    if (!class_exists('\Drupal\diff\FieldDiffBuilderBase')) {
      $this->markTestSkipped('Diff module is not available in this test environment.');
    }

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_parser = $this->createMock('\Drupal\diff\DiffEntityParser');

    return new NameFieldBuilder(
      $configuration,
      'name_field_diff_builder',
      [],
      $entity_type_manager,
      $entity_parser,
      $formatter,
      $format_options,
    );
  }

  /**
   * Skips tests when the Diff module base class is unavailable.
   */
  private function skipIfDiffUnavailable(): void {
    if (!class_exists('\Drupal\diff\FieldDiffBuilderBase')) {
      $this->markTestSkipped('Diff module is not available in this test environment.');
    }
  }

  /**
   * Builds a traversable field item list mock.
   *
   * @param array<int, object> $items
   *   Item objects yielded by foreach.
   */
  private function buildFieldItems(array $items): FieldItemListInterface {
    return new class($items) extends FieldItemList {

      /**
       * Constructor.
       *
       * @param array<int, object> $items
       *   Iterated item collection.
       */
      public function __construct(private readonly array $items) {}

      /**
       * {@inheritdoc}
       */
      public function getIterator(): \ArrayIterator {
        return new \ArrayIterator($this->items);
      }

    };
  }

  /**
   * Creates an item mock with formatter-facing methods.
   */
  private function buildItem(array $filtered, array $values, array $active): object {
    return new class($filtered, $values, $active) {

      /**
       * Constructor.
       */
      public function __construct(
        private readonly array $filtered,
        private readonly array $values,
        private readonly array $active,
      ) {}

      /**
       * Returns filtered values.
       */
      public function filteredArray(): array {
        return $this->filtered;
      }

      /**
       * Returns raw values.
       */
      public function toArray(): array {
        return $this->values;
      }

      /**
       * Returns active labels.
       */
      public function activeComponents(): array {
        return $this->active;
      }

    };
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfigurationHasEmptyCompareFormat(): void {
    $plugin = $this->buildPlugin(
      ['compare_format' => ''],
      $this->createMock(NameFormatterInterface::class),
      $this->createMock(FormatOptionInterface::class),
    );

    $defaults = $plugin->defaultConfiguration();
    $this->assertSame('', $defaults['compare_format']);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructorStoresFormatterAndFormatOptions(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $format_options = $this->createMock(FormatOptionInterface::class);
    $plugin = $this->buildPlugin(
      ['compare_format' => ''],
      $formatter,
      $format_options,
    );

    $reflection = new \ReflectionObject($plugin);

    $formatter_property = $reflection->getProperty('formatter');
    $formatter_property->setAccessible(TRUE);
    $this->assertSame($formatter, $formatter_property->getValue($plugin));

    $options_property = $reflection->getProperty('formatOptions');
    $options_property->setAccessible(TRUE);
    $this->assertSame($format_options, $options_property->getValue($plugin));
  }

  /**
   * @covers ::build
   */
  public function testBuildWithCompareFormatUsesFormatter(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->expects($this->once())
      ->method('format')
      ->with(['given' => 'Pat', 'family' => 'Lee'], 'default')
      ->willReturn('Pat Lee');

    $plugin = $this->buildPlugin(
      ['compare_format' => 'default'],
      $formatter,
      $this->createMock(FormatOptionInterface::class),
    );

    $item = $this->buildItem(
      ['given' => 'Pat', 'family' => 'Lee'],
      ['given' => 'Pat', 'family' => 'Lee'],
      ['given' => 'Given', 'family' => 'Family'],
    );

    $result = $plugin->build($this->buildFieldItems([$item]));
    $this->assertSame(['Pat Lee'], $result);
  }

  /**
   * @covers ::build
   */
  public function testBuildWithoutCompareFormatUsesComponents(): void {
    $plugin = $this->buildPlugin(
      ['compare_format' => ''],
      $this->createMock(NameFormatterInterface::class),
      $this->createMock(FormatOptionInterface::class),
    );

    $item = $this->buildItem(
      ['given' => 'Pat', 'family' => 'Lee'],
      ['given' => 'Pat', 'family' => 'Lee'],
      ['given' => 'Given', 'family' => 'Family'],
    );

    $result = $plugin->build($this->buildFieldItems([$item]));
    $this->assertSame(["Given: Pat\nFamily: Lee"], $result);
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationFormHasFormatSelectAndEmptyOption(): void {
    $format_options = $this->createMock(FormatOptionInterface::class);
    $format_options->method('getCustomFormatOptions')->willReturn([
      'default' => 'Default',
    ]);

    $plugin = $this->buildPlugin(
      ['compare_format' => 'default'],
      $this->createMock(NameFormatterInterface::class),
      $format_options,
    );

    $form = $plugin->buildConfigurationForm([], $this->createMock(FormStateInterface::class));

    $this->assertSame('select', $form['compare_format']['#type']);
    $this->assertSame('-- components --', (string) $form['compare_format']['#empty_option']);
    $this->assertSame(['default' => 'Default'], $form['compare_format']['#options']);
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationFormWithNullFormatOptions(): void {
    $plugin = $this->buildPlugin(
      ['compare_format' => ''],
      $this->createMock(NameFormatterInterface::class),
      NULL,
    );

    $form = $plugin->buildConfigurationForm([], $this->createMock(FormStateInterface::class));
    $this->assertSame([], $form['compare_format']['#options']);
  }

  /**
   * @covers ::create
   */
  public function testCreateBuildsPluginFromContainerServices(): void {
    $this->skipIfDiffUnavailable();

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_parser = $this->createMock('\Drupal\diff\DiffEntityParser');
    $formatter = $this->createMock(NameFormatterInterface::class);
    $format_options = $this->createMock(FormatOptionInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('diff.entity_parser', $entity_parser);
    $container->set('name.formatter', $formatter);
    $container->set('name.format_options', $format_options);

    $plugin = NameFieldBuilder::create($container, ['compare_format' => 'default'], 'name_field_diff_builder', []);

    $this->assertInstanceOf(NameFieldBuilder::class, $plugin);
    $this->assertSame('default', $plugin->getConfiguration()['compare_format']);
  }

  /**
   * @covers ::create
   */
  public function testCreateAllowsMissingFormatOptionsService(): void {
    $this->skipIfDiffUnavailable();

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_parser = $this->createMock('\Drupal\diff\DiffEntityParser');
    $formatter = $this->createMock(NameFormatterInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('diff.entity_parser', $entity_parser);
    $container->set('name.formatter', $formatter);

    $plugin = NameFieldBuilder::create($container, ['compare_format' => ''], 'name_field_diff_builder', []);
    $form = $plugin->buildConfigurationForm([], $this->createMock(FormStateInterface::class));

    $this->assertSame([], $form['compare_format']['#options']);
  }

  /**
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationFormStoresCompareFormat(): void {
    $plugin = $this->buildPlugin(
      ['compare_format' => ''],
      $this->createMock(NameFormatterInterface::class),
      $this->createMock(FormatOptionInterface::class),
    );
    $form_state = new FormState();
    $form_state->setValue('compare_format', 'long');

    $form = [];
    $plugin->submitConfigurationForm($form, $form_state);

    $this->assertSame('long', $plugin->getConfiguration()['compare_format']);
  }

}
