<?php

namespace Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acquia Purge Recommendations.
 *
 * @PurgeDiagnosticCheck(
 *   id = "acquia_purge_recommendations_check",
 *   title = @Translation("Acquia Purge Recommendations"),
 *   description = @Translation(""),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class RecommendationsCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * The instantiated Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * A config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path to Drupal's main .htaccess file in the app root.
   *
   * @var string
   */
  protected $htaccess;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The purge processors service.
   *
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * The purge queuers service.
   *
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * The purge purgers service.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * The global Drupal settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a RecommendationsCheck object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge purgers service.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal site settings object.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  final public function __construct($root, CacheBackendInterface $cache_backend, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ProcessorsServiceInterface $purge_processors, QueuersServiceInterface $purge_queuers, PurgersServiceInterface $purge_purgers, Settings $settings, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache_backend;
    $this->configFactory = $config_factory;
    $this->htaccess = $root . '/.htaccess';
    $this->moduleHandler = $module_handler;
    $this->purgeProcessors = $purge_processors;
    $this->purgeQueuers = $purge_queuers;
    $this->purgePurgers = $purge_purgers;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->getParameter('app.root'),
      $container->get('cache.default'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('purge.processors'),
      $container->get('purge.queuers'),
      $container->get('purge.purgers'),
      $container->get('settings'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Analyze the current Drupal site for signs of applied HTTP Authentication.
   *
   * On Acquia Cloud, all requests using basic HTTP authentication will skip
   * caching and this becomes a problem when still invalidating caches using
   * Acquia Purge. Nothing will fail, but because the invalidations just succeed
   * it creates a false sense of effectiveness.
   *
   * @return bool
   *   Boolean indicating if basic auth was found.
   */
  protected function basicHttpAuthenticationFound() {
    $cid = 'acquia_purge_recommendations_basicauth';

    // Attempt to recycle a previously cached answer.
    if ($cache = $this->cache->get($cid)) {
      $found = $cache->data;
    }
    else {
      $found = FALSE;

      // Test for the shield module and whether it is activated using a user
      // name. This module puts entire sites behind HTTP auth.
      if ($this->moduleHandler->moduleExists('shield')) {
        if ($this->configFactory->get('shield.settings')->get('shield_enable') &&
          $this->configFactory->get('shield.settings')->get('credentials.shield.user')) {
          $found = TRUE;
        }
      }

      // Else, wade through .htaccess for signs of active HTTP auth directives.
      if (!$found && file_exists($this->htaccess) && is_readable($this->htaccess)) {
        $handle = fopen($this->htaccess, "r");
        if ($handle) {
          while (($found == FALSE) && (($line = fgets($handle)) !== FALSE)) {
            $line = trim($line);
            $not_a_comment = strpos($line, '#') === FALSE;
            if ($not_a_comment && (strpos($line, 'AuthType') !== FALSE)) {
              $found = TRUE;
            }
            elseif ($not_a_comment && (strpos($line, 'AuthName') !== FALSE)) {
              $found = TRUE;
            }
            elseif ($not_a_comment && (strpos($line, 'AuthUserFile') !== FALSE)) {
              $found = TRUE;
            }
            elseif ($not_a_comment && (strpos($line, 'Require valid-user') !== FALSE)) {
              $found = TRUE;
            }
          }
          fclose($handle);
        }
      }

      // Cache the bool for at least two hours to prevent straining the system.
      $this->cache->set($cid, $found, time() + 7200);
    }

    return $found;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {

    // Check for the use of basic HTTP authentication.
    if ($this->basicHttpAuthenticationFound()) {
      $this->recommendation = $this->t('Acquia Purge detected that you are protecting your website with basic HTTP authentication. However, on Acquia Cloud all HTTP responses with access authentication deliberately MISS cache to prevent sensitive content from getting served to prying eyes. Acquia Purge cannot detect if specific parts of the site are protected or all pages, but does recommend you to temporarily disable invalidating caches if indeed your full site is protected. Please wipe Drupal\'s "default" cache bin when this warning persists after you updated your .htaccess file or uninstalled the Shield module!');
      return self::SEVERITY_WARNING;
    }

    // Issue a warning when the user forgot to add the AcquiaCloudPurger.
    if (!in_array('acquia_purge', $this->purgePurgers->getPluginsEnabled())) {
      $this->recommendation = $this->t("The 'Acquia Cloud' purger is not installed!");
      return self::SEVERITY_WARNING;
    }

    // The purge_queuer_url can quickly cause issues.
    if ($this->moduleHandler->moduleExists('purge_queuer_url')) {
      $this->recommendation = $this->t("For an optimal experience, you're recommended to not use the URLs queuer (and module) as this module creates a very high load. If you keep using it, make sure your website has only a small number of content so that the risks of using it, are contained.");
      return self::SEVERITY_WARNING;
    }

    // Test for the existence of the lateruntime and cron processors.
    if ((!$this->purgeProcessors->get('lateruntime')) || (!$this->purgeProcessors->get('cron'))) {
      $this->recommendation = $this->t("For an optimal experience, you're recommended to enable the cron processor and the late runtime processors simultaneously. These two processors will complement each other and assure that the queue is processed as fast as possible.");
      return self::SEVERITY_WARNING;
    }

    // Test for the existence of the tags queuer, to ensure we're queuing tags!
    if (!$this->purgeQueuers->get('coretags')) {
      $this->recommendation = $this->t("For an optimal experience, you're recommended to enable the coretags queuer as this queues cache tags for Acquia Purge to process.");
      return self::SEVERITY_WARNING;
    }

    // All okay!
    $this->value = $this->t("Nothing to recommend!");
    return self::SEVERITY_OK;
  }

}
