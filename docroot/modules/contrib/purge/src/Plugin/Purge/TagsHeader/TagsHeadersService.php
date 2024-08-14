<?php

namespace Drupal\purge\Plugin\Purge\TagsHeader;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\IteratingServiceBaseTrait;
use Drupal\purge\ServiceBase;

/**
 * Provides a service that provides access to available tags headers.
 */
class TagsHeadersService extends ServiceBase implements TagsHeadersServiceInterface {
  use IteratingServiceBaseTrait;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * Do not access this property directly, use ::getPurgers.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  private $purgePurgers;

  /**
   * The container service.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Construct the tags headers service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container service.
   */
  public function __construct(PluginManagerInterface $pluginManager, ContainerInterface $container) {
    $this->pluginManager = $pluginManager;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (!is_null($this->pluginsEnabled)) {
      return $this->pluginsEnabled;
    }
    // We blindly load all tag header plugins that we discovered, but not
    // when plugins put dependencies on purger plugins. When plugins do depend,
    // we load 'purge.purgers' and verify if we should load them or not.
    $load = function ($needles, $haystack) {
      if (empty($needles)) {
        return TRUE;
      }
      foreach ($needles as $needle) {
        if (in_array($needle, $haystack)) {
          return TRUE;
        }
      }
      return FALSE;
    };
    $this->pluginsEnabled = [];
    foreach ($this->getPlugins() as $plugin) {
      if (!empty($plugin['dependent_purger_plugins'])) {
        if (!$load($plugin['dependent_purger_plugins'], $this->getPurgers()->getPluginsEnabled())) {
          continue;
        }
      }
      $this->pluginsEnabled[] = $plugin['id'];
    }

    return $this->pluginsEnabled;
  }

  /**
   * Retrieve the 'purge.purgers' service - lazy loaded.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   *   The 'purge.purgers' service.
   */
  protected function getPurgers() {
    if (is_null($this->purgePurgers)) {
      $this->purgePurgers = $this->container->get('purge.purgers');
    }
    return $this->purgePurgers;
  }

  /**
   * {@inheritdoc}
   *
   * @ingroup countable
   */
  public function count(): int {
    $this->initializePluginInstances();
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->reloadIterator();
  }

}
