<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\FieldConfigInterface;
use Drupal\name\Hook\HelpHooks;

/**
 * @coversDefaultClass \Drupal\name\Hook\HelpHooks
 *
 * @group name
 */
class HelpHooksTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name']);
  }

  /**
   * @covers ::help
   * @dataProvider provideRouteSpecificHelpRoutes
   */
  public function testHelpForRouteSpecificPages(
    string $route_name,
    string $expected_text,
    string $expected_link,
  ): void {
    /** @var \Drupal\name\Hook\HelpHooks $hooks */
    $hooks = $this->container->get(HelpHooks::class);
    $route_match = $this->createMock(RouteMatchInterface::class);

    $output = $hooks->help($route_name, $route_match);

    $this->assertStringContainsString($expected_text, $output);
    $this->assertStringContainsString($expected_link, $output);
  }

  /**
   * Provides route-specific help routes handled in HelpHooks::help().
   *
   * @return array<string, array{route_name: string, expected_text: string, expected_link: string}>
   *   Route name, expected copy, and expected help-topic link.
   */
  public static function provideRouteSpecificHelpRoutes(): array {
    return [
      'settings route' => [
        'route_name'    => 'name.settings',
        'expected_text' => 'separator replacement tokens',
        'expected_link' => '<a href="/admin/help/topic/name.formats">',
      ],
      'name format list route' => [
        'route_name'    => 'name.name_format_list',
        'expected_text' => 'Name formats control how a single name is shown.',
        'expected_link' => '<a href="/admin/help/topic/name.formats">',
      ],
      'name format add route' => [
        'route_name'    => 'name.name_format_add',
        'expected_text' => 'Name formats control how a single name is shown.',
        'expected_link' => '<a href="/admin/help/topic/name.formats">',
      ],
      'name format edit route' => [
        'route_name'    => 'entity.name_format.edit_form',
        'expected_text' => 'Name formats control how a single name is shown.',
        'expected_link' => '<a href="/admin/help/topic/name.formats">',
      ],
      'name list format list route' => [
        'route_name'    => 'name.name_list_format_list',
        'expected_text' => 'Name list formats control how multiple names are joined together.',
        'expected_link' => '<a href="/admin/help/topic/name.list_formats">',
      ],
      'name list format add route' => [
        'route_name'    => 'name.name_list_format_add',
        'expected_text' => 'Name list formats control how multiple names are joined together.',
        'expected_link' => '<a href="/admin/help/topic/name.list_formats">',
      ],
      'name list format edit route' => [
        'route_name'    => 'entity.name_list_format.edit_form',
        'expected_text' => 'Name list formats control how multiple names are joined together.',
        'expected_link' => '<a href="/admin/help/topic/name.list_formats">',
      ],
    ];
  }

  /**
   * @covers ::help
   */
  public function testHelpReturnsEmptyStringForUnhandledRoute(): void {
    /** @var \Drupal\name\Hook\HelpHooks $hooks */
    $hooks = $this->container->get(HelpHooks::class);
    $route_match = $this->createMock(RouteMatchInterface::class);

    $this->assertSame('', $hooks->help('name.noop_route', $route_match));
  }

  /**
   * @covers ::help
   * @covers ::isNameFieldConfigRoute
   * @covers ::fieldConfigHelp
   */
  public function testHelpReturnsFieldConfigHelpForNameFieldRoutes(): void {
    /** @var \Drupal\name\Hook\HelpHooks $hooks */
    $hooks = $this->container->get(HelpHooks::class);

    $field_config = $this->createMock(FieldConfigInterface::class);
    $field_config->method('getType')->willReturn('name');
    $field_config->method('getTargetEntityTypeId')->willReturn('node');

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('field_config')
      ->willReturn($field_config);

    $output = $hooks->help('entity.field_config.node_field_edit_form', $route_match);

    $this->assertStringContainsString(
      'Name field settings control which name parts are active',
      $output,
    );
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.field_settings">',
      $output,
    );
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.widget_options">',
      $output,
    );
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.autocomplete">',
      $output,
    );
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.titles">',
      $output,
    );
    $this->assertStringNotContainsString(
      '<a href="/admin/help/topic/name.user_display_name">',
      $output,
    );
  }

  /**
   * @covers ::help
   * @covers ::isNameFieldConfigRoute
   * @covers ::fieldConfigHelp
   */
  public function testHelpAddsUserDisplayNameLinkForUserEntityType(): void {
    /** @var \Drupal\name\Hook\HelpHooks $hooks */
    $hooks = $this->container->get(HelpHooks::class);

    $field_config = $this->createMock(FieldConfigInterface::class);
    $field_config->method('getType')->willReturn('name');
    $field_config->method('getTargetEntityTypeId')->willReturn('user');

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('field_config')
      ->willReturn($field_config);

    $output = $hooks->help('entity.field_config.user_field_edit_form', $route_match);

    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.user_display_name">',
      $output,
    );
  }

  /**
   * @covers ::help
   * @covers ::isNameFieldConfigRoute
   */
  public function testFieldConfigRoutesWithoutNameFieldReturnEmptyString(): void {
    /** @var \Drupal\name\Hook\HelpHooks $hooks */
    $hooks = $this->container->get(HelpHooks::class);

    $field_config = $this->createMock(FieldConfigInterface::class);
    $field_config->method('getType')->willReturn('string');
    $field_config->expects($this->never())->method('getTargetEntityTypeId');

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('field_config')
      ->willReturn($field_config);

    $this->assertSame(
      '',
      $hooks->help('entity.field_config.node_field_edit_form', $route_match),
    );
  }

  /**
   * @covers ::help
   * @covers ::isNameFieldConfigRoute
   */
  public function testFieldConfigRoutesWithoutFieldConfigParameterReturnEmptyString(): void {
    /** @var \Drupal\name\Hook\HelpHooks $hooks */
    $hooks = $this->container->get(HelpHooks::class);

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('field_config')
      ->willReturn(NULL);

    $this->assertSame(
      '',
      $hooks->help('entity.field_config.node_field_edit_form', $route_match),
    );
  }

}
