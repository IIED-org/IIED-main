<?php

/**
 * @file
 * Contains \Drupal\field_token_value\WrapperManager.
 */

namespace Drupal\field_token_value;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Gathers and provides the tags that can be used to wrap field content within
 * Field Token Value fields.
 *
 * Extensions can define wrappers in an EXTENSION_NAME.field_token_value.yml
 * file contained in the extension's base directory. Each wrapper has the
 * following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     title: STRING
 *     summary: STRING
 *     tag: STRING
 *     attributes:
 *       class:
 *         - STRING
 *       id:
 *         - STRING
 * @endcode
 *
 * For example:
 *
 * @code
 *   my_tag:
 *     title: My Tag
 *     summary: Styles your field in a specific way
 *     tag: div
 *     attributes:
 *       class:
 *         - my-custom-class
 * @endcode
 *
 * The summary is used by the field formatter as the summary text where as the
 * tag is the wrapping HTML element for the output. Any HTML attribute may be
 * passed to the attributes array.
 */
class WrapperManager extends DefaultPluginManager implements WrapperManagerInterface, PluginManagerInterface, CachedDiscoveryInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $default = [
    'title' => '',
    'summary' => '',
    'tag' => '',
    'attributes' => [],
  ];

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new WrapperManager instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->alterInfo('field_token_value_wrapper_info');
    $this->setCacheBackend($cache_backend, 'field_token_value', ['field_token_value']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('field_token_value', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapperOptions() {
    $options = [];

    foreach ($this->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['title'];
    }

    return $options;
  }

}
