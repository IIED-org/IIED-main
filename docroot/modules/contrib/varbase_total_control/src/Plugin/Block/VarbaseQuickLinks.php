<?php

namespace Drupal\varbase_total_control\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Varbase Quick Links'.
 *
 * @Block(
 * id = "varbase_quick_links",
 * admin_label = @Translation("Quick Links"),
 * category = @Translation("Dashboard")
 * )
 */
class VarbaseQuickLinks extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a VarbaseQuickLinks block instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];

    $links[] = Link::fromTextAndUrl($this->t('Manage menus'), new Url('entity.menu.collection'));
    $links[] = Link::fromTextAndUrl($this->t('Manage taxonomy'), new Url('entity.taxonomy_vocabulary.collection'));
    $links[] = Link::fromTextAndUrl($this->t('Manage users'), new Url('entity.user.collection'));

    $body_data = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $links,
    ];

    $markup_data = $this->renderer->render($body_data);

    return [
      '#type' => 'markup',
      '#markup' => $markup_data,
    ];
  }

}
