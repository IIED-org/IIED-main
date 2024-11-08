<?php

namespace Drupal\cloudflare_purge\Controller;

use Drupal\cloudflare_purge\CloudflarePurgeCredentials;
use Drupal\cloudflare_purge\Form\CloudflarePurgeForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for cloudflare_purge module routes.
 */
class CloudflarePurgeController extends ControllerBase {

  /**
   * Store the settings var.
   *
   * @var CloudflarePurgeController
   */
  private $cfPurgeSettings = [];

  /**
   * Get the config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * A request stack symfony instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Get the config and logger.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              LoggerChannelInterface $logger,
                              RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->requestStack = $request_stack;
    $this->getCredentials();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cloudflare_purge.logger.channel.cloudflare_purge'),
      $container->get('request_stack'),
    );
  }

  /**
   * Stay on the same page.
   */
  public function getCurrentUrl() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->server->get('HTTP_REFERER')) {
      return $request->server->get('HTTP_REFERER');
    }

    return base_path();
  }

  /**
   * Get the credentials.
   */
  public function getCredentials() {
    // Get credentials from settings.
    $cloudflareCredentials = Settings::get('cloudflare_purge_credentials');
    // Store credentials.
    if (!empty($cloudflareCredentials)) {
      $this->cfPurgeSettings = $cloudflareCredentials;
      return $cloudflareCredentials;
    }
    return NULL;
  }

  /**
   * Purge cloudflare cache.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to the previous url.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function purgeAll(): RedirectResponse {
    $zoneId = $this->getValueFromSettingsOrConfig('zone_id');
    $authorization = $this->getValueFromSettingsOrConfig('authorization');

    if (!empty($zoneId) && !empty($authorization)) {
      $results = CloudflarePurgeCredentials::cfPurgeCache($zoneId, $authorization);
      if ($results == 200) {
        $this->messenger()
          ->addMessage($this->t('Cloudflare was purged successfully.'));
      }
      else {
        $this->messenger()
          ->addError($this->t('An error happened while clearing cloudflare, check drupal log for more info.'));
      }
    }
    else {
      $this->messenger()
        ->addError($this->t('Please insert Cloudflare credentials.'));
    }

    return new RedirectResponse($this->getCurrentUrl());

  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   Check the value.
   *
   * @return array|bool|mixed|null
   *   Return the value either from settings or config.
   */
  protected function getValueFromSettingsOrConfig(string $name) {
    $valueFromSettings = $this->getCredentials();
    $valueFromConfig = $this->configFactory->get(CloudflarePurgeForm::SETTINGS);
    return $valueFromSettings[$name] ?? $valueFromConfig->get($name);
  }

}
