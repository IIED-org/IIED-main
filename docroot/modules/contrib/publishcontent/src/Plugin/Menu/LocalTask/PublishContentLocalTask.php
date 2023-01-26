<?php

namespace Drupal\publishcontent\Plugin\Menu\LocalTask;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local task plugin with a dynamic title.
 */
class PublishContentLocalTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module configuration for reading.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->nodeStorage = $container->get('entity_type.manager')->getStorage('node');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->languageManager = $container->get('language_manager');
    $instance->config = $container->get('config.factory')->get('publishcontent.settings');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->load($this->routeMatch->getRawParameter('node'));
    if ($node->isTranslatable() && $node->hasTranslation($langcode)) {
      $translatedNode = $node->getTranslation($langcode);
      return $translatedNode->isPublished() ? $this->config->get('unpublish_text_value') : $this->config->get('publish_text_value');
    }
    return $node->isPublished() ? $this->config->get('unpublish_text_value') : $this->config->get('publish_text_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $routeParameters = $this->getRouteParameters($this->routeMatch);
    if (array_key_exists('node', $routeParameters)) {

      return ['node:' . $routeParameters['node']];
    }

    return [];
  }

}
