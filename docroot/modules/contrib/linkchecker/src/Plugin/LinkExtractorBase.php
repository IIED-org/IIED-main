<?php

namespace Drupal\linkchecker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Link extractor plugins.
 */
abstract class LinkExtractorBase extends PluginBase implements LinkExtractorInterface, ContainerFactoryPluginInterface {

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $linkcheckerSetting;

  /**
   * LinkExtractorBase plugin constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkcheckerSetting = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('linkchecker.settings')
    );
  }

  /**
   * Extracts a URLs from field.
   *
   * @return array
   *   Array of URLs.
   */
  abstract protected function extractUrlFromField(array $value);

  /**
   * {@inheritdoc}
   */
  public function extract(array $value) {
    $urls = [];

    foreach ($value as $item) {
      $urls = array_merge($urls, $this->extractUrlFromField($item));
    }

    return $urls;
  }

}
