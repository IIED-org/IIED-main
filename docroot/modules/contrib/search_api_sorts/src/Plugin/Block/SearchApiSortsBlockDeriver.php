<?php

namespace Drupal\search_api_sorts\Plugin\Block;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This deriver creates a block for every index that has been created.
 */
class SearchApiSortsBlockDeriver implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The entity storage used for search api sorts.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface $indexStorage
   */
  protected $indexStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static($container, $base_plugin_id);
    $deriver->indexStorage = $container->get('entity_type.manager')->getStorage('search_api_index');
    return $deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    return isset($derivatives[$derivative_id]) ? $derivatives[$derivative_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];

    if (!isset($this->derivatives[$base_plugin_id])) {
      $plugin_derivatives = [];

      /** @var \Drupal\search_api\Display\DisplayPluginManagerInterface $sapi_display_manager */
      $sapi_display_manager = \Drupal::service('plugin.manager.search_api.display');
      foreach ($sapi_display_manager->getInstances() as $display) {
        $machine_name = $display->getPluginId();

        $plugin_derivatives[$machine_name] = [
          'search_api_index' => $display->getIndex(),
          'search_api_display' => $display,
          'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
          'admin_label' => $this->t('Sort by (@index)', ['@index' => strip_tags($display->label())]),
        ] + $base_plugin_definition;
      }

      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

}
