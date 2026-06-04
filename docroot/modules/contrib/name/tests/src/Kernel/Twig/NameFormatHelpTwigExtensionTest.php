<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Twig;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Twig\NameFormatHelpTwigExtension;

/**
 * Kernel tests for NameFormatHelpTwigExtension.
 *
 * @coversDefaultClass \Drupal\name\Twig\NameFormatHelpTwigExtension
 *
 * @group name
 */
class NameFormatHelpTwigExtensionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
    'help',
  ];

  /**
   * Tests that the Twig extension is registered as a service.
   */
  public function testExtensionIsRegistered(): void {
    $this->assertInstanceOf(
      NameFormatHelpTwigExtension::class,
      $this->container->get('name.twig.name_format_help'),
    );
  }

  /**
   * Tests that name_format_token_help() is a registered Twig function.
   */
  public function testTwigFunctionIsRegistered(): void {
    /** @var \Twig\Environment $twig */
    $twig = $this->container->get('twig');

    $function = $twig->getFunction('name_format_token_help');
    $this->assertNotFalse($function);
  }

  /**
   * @covers ::getTokenHelp
   */
  public function testGetTokenHelpRendersTokenList(): void {
    /** @var \Drupal\name\Twig\NameFormatHelpTwigExtension $ext */
    $ext    = $this->container->get('name.twig.name_format_help');
    $build  = $ext->getTokenHelp();
    $output = (string) $this->container->get('renderer')->renderRoot($build);

    $this->assertStringContainsString('<dl>', $output);
    $this->assertStringContainsString('<dt>g</dt>', $output);
    // describe=TRUE by default, so case hints must appear.
    $this->assertStringContainsString('(lowercase G)', $output);
  }

  /**
   * Tests that the help topic renders the token list via the Twig function.
   */
  public function testNameFormatsHelpTopicContainsTokenList(): void {
    /** @var \Drupal\help\HelpTopicPluginManagerInterface $manager */
    $manager  = $this->container->get('plugin.manager.help_topic');
    $renderer = $this->container->get('renderer');
    $topic    = $manager->createInstance('name.formats');

    // HelpTopicTwig::getBody() renders the Twig template eagerly, so
    // render_var() inside the template requires an active render context.
    $output = '';
    $renderer->executeInRenderContext(
      new RenderContext(),
      static function () use ($topic, $renderer, &$output): void {
        $build  = $topic->getBody();
        $output = (string) $renderer->render($build);
      },
    );

    $this->assertStringContainsString('<dt>g</dt>', $output);
    $this->assertStringContainsString('(lowercase G)', $output);
  }

}
