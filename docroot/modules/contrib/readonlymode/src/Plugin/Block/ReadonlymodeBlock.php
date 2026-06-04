<?php

namespace Drupal\readonlymode\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Read only mode info block.
 *
 * @Block(
 *   id = "readonlymode_block",
 *   admin_label = @Translation("Read Only Mode")
 * )
 */
class ReadonlymodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ReadonlymodeBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    $config = $this->configFactory->get('readonlymode.settings');
    if ($config->get('enabled')) {
      $site = $this->configFactory->get('system.site');
      $output = [
        '#title' => $this->t('Read only mode'),
        '#markup' => $site->get('name') . ' is currently in maintenance. During this maintenance it is not possible to change site content (like comments, pages and users).',
      ];

    }
    return $output;
  }

}
