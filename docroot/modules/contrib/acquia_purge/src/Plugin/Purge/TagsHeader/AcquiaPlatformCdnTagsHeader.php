<?php

namespace Drupal\acquia_purge\Plugin\Purge\TagsHeader;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\AcquiaPlatformCdn\BackendFactory;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports a tags header for the current Platform CDN backend.
 *
 * @PurgeTagsHeader(
 *   id = "acquiapurgecdntagsheader",
 *   header_name = "X-Acquia-Purge-Cdn-Unconfigured",
 *   dependent_purger_plugins = {"acquia_platform_cdn"},
 * )
 */
class AcquiaPlatformCdnTagsHeader extends TagsHeaderBase implements TagsHeaderInterface {

  /**
   * The Acquia Platform CDN backend.
   *
   * @var null|string
   */
  protected $backendClass = NULL;

  /**
   * Information object interfacing with the Acquia platform.
   *
   * @var \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   */
  protected $platformInfo;

  /**
   * Constructs a AcquiaPlatformCdnTagsHeader object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $acquia_purge_platforminfo
   *   Information object interfacing with the Acquia platform.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, PlatformInfoInterface $acquia_purge_platforminfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->platformInfo = $acquia_purge_platforminfo;
    $this->backendClass = BackendFactory::getClass($this->platformInfo);

    // When a backend is available, inject the platform info object.
    if ($this->backendClass) {
      $this->backendClass::platformInfo($this->platformInfo);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('acquia_purge.platforminfo')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderName() {
    if ($this->backendClass) {
      return $this->backendClass::tagsHeaderName();
    }
    return $this->getPluginDefinition()['header_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $tags) {
    if ($this->backendClass) {
      return $this->backendClass::tagsHeaderValue($tags);
    }
    return 'n/a';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if ($this->backendClass) {
      return TRUE;
    }
    return FALSE;
  }

}
