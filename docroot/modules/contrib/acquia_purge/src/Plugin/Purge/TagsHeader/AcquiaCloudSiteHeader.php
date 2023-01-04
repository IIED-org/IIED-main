<?php

namespace Drupal\acquia_purge\Plugin\Purge\TagsHeader;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports the X-Acquia-Site header.
 *
 * @PurgeTagsHeader(
 *   id = "acquiapurgecloudsiteheader",
 *   header_name = "X-Acquia-Site",
 *   dependent_purger_plugins = {"acquia_purge"},
 * )
 */
class AcquiaCloudSiteHeader extends TagsHeaderBase implements TagsHeaderInterface {

  /**
   * The identifier for this site.
   *
   * @var string
   */
  protected $identifier = '';

  /**
   * Constructs a AcquiaCloudSiteHeader object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $acquia_purge_platforminfo
   *   Provides technical information accessors for Acquia Cloud.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, PlatformInfoInterface $acquia_purge_platforminfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->identifier = $acquia_purge_platforminfo->getSiteIdentifier();
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
  public function getValue(array $tags) {
    return $this->identifier;
  }

}
