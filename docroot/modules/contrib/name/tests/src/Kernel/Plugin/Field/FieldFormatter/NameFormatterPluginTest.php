<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Plugin\Field\FieldFormatter\NameFormatter;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the NameFormatter field formatter plugin.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Plugin\Field\FieldFormatter\NameFormatter
 */
final class NameFormatterPluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
    'node',
    'link',
  ];

  /**
   * Name field machine name.
   */
  private string $nameFieldName = 'field_name_plugin';

  /**
   * Reference field machine name.
   */
  private string $referenceFieldName = 'field_name_reference';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name', 'node']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->nameFieldName,
      'entity_type' => 'node',
      'type' => 'name',
    ])->save();

    FieldConfig::create([
      'field_name' => $this->nameFieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Name',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->referenceFieldName,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $this->referenceFieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Name reference',
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => [],
      ],
    ])->save();

    $role = Role::load(Role::AUTHENTICATED_ID) ?? Role::create([
      'id' => Role::AUTHENTICATED_ID,
      'label' => 'Authenticated',
    ]);
    $role->grantPermission('access content')->save();

    $account = User::create([
      'name' => 'formatter-plugin-test',
      'status' => 1,
      'roles' => [Role::AUTHENTICATED_ID],
    ]);
    $account->save();
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Tests getLinkableTargetUrl() for the owning entity.
   *
   * @covers ::getLinkableTargetUrl
   * @covers ::resolveEntitySelfUrl
   */
  public function testGetLinkableTargetUrlSelfWithRealEntity(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Owning page',
      'status' => 1,
      $this->nameFieldName => [
        'given' => 'Owning',
        'family' => 'Page',
      ],
    ]);
    $node->save();

    $formatter = $this->buildFormatter('_self');
    $url = $this->getLinkableTargetUrl($formatter, $node->get($this->nameFieldName));

    $this->assertSame('entity.node.canonical', $url->getRouteName());
    $this->assertSame($node->id(), $url->getRouteParameters()['node']);
  }

  /**
   * Tests getLinkableTargetUrl() for an entity reference field.
   *
   * @covers ::getLinkableTargetUrl
   * @covers ::resolveFieldTargetUrl
   * @covers ::resolveEntityReferenceUrl
   */
  public function testGetLinkableTargetUrlWithEntityReferenceField(): void {
    $target = Node::create([
      'type' => 'page',
      'title' => 'Referenced page',
      'status' => 1,
    ]);
    $target->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Owning page',
      'status' => 1,
      $this->nameFieldName => [
        'given' => 'Owning',
        'family' => 'Page',
      ],
      $this->referenceFieldName => [
        'target_id' => $target->id(),
      ],
    ]);
    $node->save();

    $formatter = $this->buildFormatter($this->referenceFieldName);
    $url = $this->getLinkableTargetUrl($formatter, $node->get($this->nameFieldName));

    $this->assertSame('entity.node.canonical', $url->getRouteName());
    $this->assertSame($target->id(), $url->getRouteParameters()['node']);
  }

  /**
   * Builds the NameFormatter plugin instance.
   *
   * @param string $link_target
   *   The configured link target.
   *
   * @return \Drupal\name\Plugin\Field\FieldFormatter\NameFormatter
   *   The formatter plugin.
   */
  private function buildFormatter(string $link_target): NameFormatter {
    $field_config = FieldConfig::loadByName('node', 'page', $this->nameFieldName);
    $settings = NameFormatter::defaultSettings();
    $settings['link_target'] = $link_target;

    return NameFormatter::create($this->container, [
      'field_definition' => $field_config,
      'settings' => $settings,
      'label' => 'hidden',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ], 'name_default', []);
  }

  /**
   * Calls the protected getLinkableTargetUrl() method.
   *
   * @param \Drupal\name\Plugin\Field\FieldFormatter\NameFormatter $formatter
   *   The formatter plugin.
   * @param mixed $items
   *   The name field items.
   *
   * @return \Drupal\Core\Url
   *   The resolved URL.
   */
  private function getLinkableTargetUrl(NameFormatter $formatter, mixed $items): Url {
    return (function (mixed $items): Url {
      return $this->getLinkableTargetUrl($items);
    })->call($formatter, $items);
  }

}
