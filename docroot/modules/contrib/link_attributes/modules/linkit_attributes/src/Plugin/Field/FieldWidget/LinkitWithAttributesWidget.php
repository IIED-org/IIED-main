<?php

namespace Drupal\linkit_attributes\Plugin\Field\FieldWidget;

use Drupal\link_attributes\LinkWithAttributesWidgetTrait;
use Drupal\linkit\Plugin\Field\FieldWidget\LinkitWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'linkit' widget.
 *
 * @FieldWidget(
 *   id = "linkit_attributes",
 *   label = @Translation("Linkit (with attributes)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkitWithAttributesWidget extends LinkitWidget {

  use LinkWithAttributesWidgetTrait;

  /**
   * Link attributes plugin manager.
   *
   * @var \Drupal\link_attributes\LinkAttributesManager
   */
  protected $linkAttributesManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->linkAttributesManager = $container->get('plugin.manager.link_attributes');
    return $instance;
  }

}
