<?php

namespace Drupal\link_attributes\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\link_attributes\LinkAttributesManager;
use Drupal\link_attributes\LinkWithAttributesWidgetTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "link_attributes",
 *   label = @Translation("Link (with attributes)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkWithAttributesWidget extends LinkWidget {

  use LinkWithAttributesWidgetTrait;

  public const WIDGET_OPEN_EXPAND_IF_VALUES_SET = 'expandIfValuesSet';
  public const WIDGET_OPEN_COLLAPSED = 'collapsed';
  public const WIDGET_OPEN_EXPANDED = 'expanded';

  /**
   * The link attributes manager.
   *
   * @var \Drupal\link_attributes\LinkAttributesManager
   */
  protected $linkAttributesManager;

  /**
   * Constructs a LinkWithAttributesWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\link_attributes\LinkAttributesManager $link_attributes_manager
   *   The link attributes manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LinkAttributesManager $link_attributes_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->linkAttributesManager = $link_attributes_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.link_attributes')
    );
  }

}
