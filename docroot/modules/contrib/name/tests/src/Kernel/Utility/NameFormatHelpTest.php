<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Utility;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Utility\NameFormatHelp;

/**
 * Kernel tests for NameFormatHelp.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatHelp
 *
 * @group name
 */
class NameFormatHelpTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
  ];

  /**
   * Tests that the theme hook is registered by the module.
   */
  public function testThemeHookIsRegistered(): void {
    $registry = $this->container->get('theme.registry')->get();
    $this->assertArrayHasKey('name_format_parameter_help', $registry);
  }

  /**
   * @covers ::renderableTokenHelp
   */
  public function testRenderableTokenHelpRendersThemeOutput(): void {
    $build  = NameFormatHelp::renderableTokenHelp();
    $output = (string) $this->container->get('renderer')->renderRoot($build);

    $this->assertStringContainsString(
      'recognized in the format parameter string',
      $output
    );
    $this->assertStringContainsString('<dl>', $output);
    $this->assertStringContainsString('<dt>g</dt>', $output);
    // renderableTokenHelp uses described token help, so case hints appear.
    $this->assertStringContainsString('(lowercase G)', $output);
  }

  /**
   * @covers ::tokenHelpPlain
   */
  public function testTokenHelpPlainInRenderedOutput(): void {
    $build = [
      '#theme'  => 'name_format_parameter_help',
      '#tokens' => NameFormatHelp::tokenHelpPlain(),
    ];
    $output = (string) $this->container->get('renderer')->renderRoot($build);

    $this->assertStringContainsString('<dt>g</dt>', $output);
    $this->assertStringNotContainsString('(lowercase G)', $output);
  }

}
