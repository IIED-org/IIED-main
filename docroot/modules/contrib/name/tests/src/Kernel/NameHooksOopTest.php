<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Hook\FieldConfigHooks;
use Drupal\name\Hook\FieldHooks;
use Drupal\name\Hook\HelpHooks;
use Drupal\name\Hook\ThemeHooks;
use Drupal\name\Hook\TokenHooks;
use Drupal\name\Hook\UserHooks;
use Drupal\name\Hook\WidgetLayoutHooks;

/**
 * Verifies the autowired OOP hook services resolve and invoke cleanly.
 *
 * Complements NameModuleHooksTest (which covers the procedural shims that the
 * #[LegacyHook] attribute leaves in place for Drupal 10.3 through 11.0).
 *
 * @group name
 */
class NameHooksOopTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name']);
    $this->installEntitySchema('user');
  }

  /**
   * The module's Hook classes resolve via the container by FQCN.
   */
  public function testHookServicesAreAutowired(): void {
    $this->assertInstanceOf(
      HelpHooks::class,
      $this->container->get(HelpHooks::class),
    );
    $this->assertInstanceOf(
      WidgetLayoutHooks::class,
      $this->container->get(WidgetLayoutHooks::class),
    );
    $this->assertInstanceOf(
      ThemeHooks::class,
      $this->container->get(ThemeHooks::class),
    );
    $this->assertInstanceOf(
      UserHooks::class,
      $this->container->get(UserHooks::class),
    );
    $this->assertInstanceOf(
      FieldConfigHooks::class,
      $this->container->get(FieldConfigHooks::class),
    );
    $this->assertInstanceOf(
      FieldHooks::class,
      $this->container->get(FieldHooks::class),
    );
    $this->assertInstanceOf(
      TokenHooks::class,
      $this->container->get(TokenHooks::class),
    );
  }

  /**
   * Representative invocations exercise the autowired dependencies.
   */
  public function testRepresentativeHookMethodsExecute(): void {
    $help = $this->container->get(HelpHooks::class)
      ->help('help.page.name', $this->createMock(RouteMatchInterface::class));
    $this->assertStringContainsString('stores a person\'s name in parts', $help);

    $layouts = $this->container->get(WidgetLayoutHooks::class)
      ->nameWidgetLayouts();
    $this->assertArrayHasKey('stacked', $layouts);
    $this->assertArrayHasKey('inline', $layouts);

    $theme = $this->container->get(ThemeHooks::class)->theme();
    $this->assertArrayHasKey('name_item', $theme);
    $this->assertArrayHasKey('name', $theme);
  }

}
