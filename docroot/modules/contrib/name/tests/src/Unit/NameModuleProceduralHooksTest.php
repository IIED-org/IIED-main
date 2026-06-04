<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;

require_once __DIR__ . '/../../../name.module';

/**
 * Unit coverage for selected procedural hooks in name.module.
 *
 * @group name
 */
final class NameModuleProceduralHooksTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::setContainer(new ContainerBuilder());
    parent::tearDown();
  }

  /**
   * @covers \template_preprocess_name_item
   */
  public function testTemplatePreprocessNameItemFallsBackToDefaultPattern(): void {
    $format_options = new class() {

      /**
       * Returns patterns for known machine names.
       */
      public function getFormatPatternByMachineName(string $format_id): string {
        return match ($format_id) {
          'default' => '@given @family',
          default => '',
        };
      }

    };

    $format_parser = new class() {

      /**
       * Captures parse arguments for assertions.
       *
       * @var array<int, mixed>
       */
      public array $capturedArguments = [];

      /**
       * Mimics parsing and records arguments.
       */
      public function parse(array $item, string $format, array $settings): string {
        $this->capturedArguments = [$item, $format, $settings];
        return 'Pat Fallback';
      }

    };

    $container = new ContainerBuilder();
    $container->set('name.format_options', $format_options);
    $container->set('name.format_parser', $format_parser);
    \Drupal::setContainer($container);

    $variables = [
      'item' => [
        'given' => 'Pat',
        'family' => 'Fallback',
      ],
      'format' => 'missing',
      'settings' => [],
    ];

    template_preprocess_name_item($variables);

    $this->assertSame(0, $variables['settings']['markup']);
    $this->assertSame('Pat Fallback', $variables['formatted_name']);
    $this->assertSame(
      [
        ['given' => 'Pat', 'family' => 'Fallback'],
        '@given @family',
        ['markup' => 0],
      ],
      $format_parser->capturedArguments,
    );
  }

  /**
   * @covers \name_token_info_alter
   */
  public function testTokenInfoAlterReturnsEarlyWhenTokenServiceMissing(): void {
    \Drupal::setContainer(new ContainerBuilder());

    $info = [
      'types' => [],
      'tokens' => ['entity_test' => []],
    ];

    name_token_info_alter($info);

    $this->assertSame([], $info['types']);
    $this->assertSame([], $info['tokens']['entity_test']);
  }

  /**
   * @covers \name_tokens
   */
  public function testTokensReturnsEmptyArrayWhenTokenServiceMissing(): void {
    \Drupal::setContainer(new ContainerBuilder());

    $actual = name_tokens(
      'entity_test',
      ['given' => '[placeholder]'],
      [],
      [],
      new BubbleableMetadata(),
    );

    $this->assertSame([], $actual);
  }

  /**
   * @covers \name_tokens_alter
   */
  public function testTokensAlterReturnsEarlyWhenTokenServiceMissing(): void {
    \Drupal::setContainer(new ContainerBuilder());

    $replacements = ['existing' => 'value'];
    $context = [
      'type' => 'entity_test',
      'tokens' => [],
      'data' => [],
      'options' => [],
    ];

    name_tokens_alter($replacements, $context, new BubbleableMetadata());

    $this->assertSame(['existing' => 'value'], $replacements);
  }

}
