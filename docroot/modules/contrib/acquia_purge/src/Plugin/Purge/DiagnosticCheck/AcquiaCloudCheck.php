<?php

namespace Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acquia Purge.
 *
 * @PurgeDiagnosticCheck(
 *   id = "acquia_purge_cloud_check",
 *   title = @Translation("Acquia Cloud"),
 *   description = @Translation("Validates the Acquia Cloud configuration."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"acquia_purge"}
 * )
 */
class AcquiaCloudCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Information object interfacing with the Acquia platform.
   *
   * @var \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   */
  protected $platformInfo;

  /**
   * Constructs a AcquiaCloudCheck object.
   *
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $acquia_purge_platforminfo
   *   Information object interfacing with the Acquia platform.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  final public function __construct(PlatformInfoInterface $acquia_purge_platforminfo, ModuleExtensionList $moduleExtensionList, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleExtensionList = $moduleExtensionList;
    $this->platformInfo = $acquia_purge_platforminfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('acquia_purge.platforminfo'),
      $container->get('extension.list.module'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Use get_object_vars() to avoid the following Phpstan error:
    // Access to an undefined property Drupal\Core\Extension\Extension::$info.
    $info = get_object_vars($this->moduleExtensionList->get('acquia_purge'));
    $version = isset($info['info']['version']) ? $info['info']['version'] : 'dev';
    $this->value = $version;

    // Block the entire system when this is a third-party platform.
    if (!$this->platformInfo->isThisAcquiaCloud()) {
      $this->recommendation = $this->t("Acquia Purge only works on your Acquia Cloud environment and doesn't work outside of it.");
      return self::SEVERITY_INFO;
    }

    // Check the balancer composition for crazy setups.
    $balancers = $this->platformInfo->getBalancerAddresses();
    $balancerscount = count($balancers);
    $this->value = $balancerscount ? implode(', ', $balancers) : '';
    if (!$balancerscount) {
      $this->value = '';
      $this->recommendation = $this->t("No balancers found, therefore cache invalidation has been disabled. Please contact Acquia Support!");
      return self::SEVERITY_ERROR;
    }
    elseif ($balancerscount < 2) {
      $this->recommendation = $this->t("You have only one load balancer, this means your site cannot be failed over in case of emergency. Please contact Acquia Support!");
      return self::SEVERITY_WARNING;
    }
    elseif ($balancerscount >= 5) {
      $this->recommendation = $this->t("Your site has @n load balancers, which will put severe stress on your system. Please pay attention to your queue, contact Acquia Support and request less but bigger load balancers!", ['@n' => $balancerscount]);
      return self::SEVERITY_WARNING;
    }

    // Under normal operating conditions, we'll report site info and version.
    $this->value = $this->t(
      "@site_group.@site_env (@version)",
      [
        '@site_group' => $this->platformInfo->getSiteGroup(),
        '@site_env' => $this->platformInfo->getSiteEnvironment(),
        '@version' => $version,
      ]
    );
    $this->recommendation = " ";
    return self::SEVERITY_OK;
  }

}
