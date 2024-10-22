<?php

namespace Drupal\cloudflare_form_tester\Mocks;

// cspell:ignore multizone singlezone
use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Zone methods for CloudFlare.
 */
class ZoneMock implements CloudFlareZoneInterface {
  use StringTranslationTrait;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * The current cloudflare ZoneId.
   *
   * @var string
   */
  protected $zone;

  /**
   * Flag for valid credentials.
   *
   * @var bool
   */
  protected $validCredentials;

  /**
   * Checks that the composer dependencies for CloudFlare are met.
   *
   * @var \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface
   */
  protected $cloudFlareComposerDependenciesCheck;

  /**
   * {@inheritdoc}
   */
  public static function create(ConfigFactoryInterface $config_factory, LoggerInterface $logger, CloudFlareStateInterface $state, CloudFlareComposerDependenciesCheckInterface $check_interface) {

    return new static(
      $config_factory,
      $logger,
      $state,
      $check_interface
    );
  }

  /**
   * Zone constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks that composer dependencies are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, CloudFlareStateInterface $state, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $this->config = $config_factory->get('cloudflare.settings');
    $this->logger = $logger;
    $this->state = $state;
    $this->zone = $this->config->get('zone');
    $this->validCredentials = $this->config->get('valid_credentials');
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
  }

  /**
   * {@inheritdoc}
   */
  public function listZones() {
    $cloudflare_zone_settings = [];

    $cloudflare_zone_settings['id'] = '123456789';
    $cloudflare_zone_settings['name'] = 'test-domain.com';
    $cloudflare_zone_settings['status'] = 'Active';
    $cloudflare_zone_settings['paused'] = FALSE;
    $cloudflare_zone_settings['type'] = 'zone';
    $cloudflare_zone_settings['development_mode'] = FALSE;
    $cloudflare_zone_settings['name_servers'] = ['1.2.3.4', '1.2.3.5'];
    $cloudflare_zone_settings['original_name_servers'] = ['1.2.3.4', '1.2.3.5'];
    $cloudflare_zone_settings['original_registrar'] = 'rsa.com';
    $cloudflare_zone_settings['original_dnshost'] = ['1.2.3.4', '1.2.3.5'];
    $cloudflare_zone_settings['modified_on'] = '1453579581 ';
    $cloudflare_zone_settings['created_on'] = '1453579581';
    $cloudflare_zone_settings['owner'] = 'Drupal Developer';
    $cloudflare_zone_settings['permissions'] = 'EMPTY';
    $cloudflare_zone_settings['plan'] = 'EMPTY';

    $cloudflare_zone_settings2['id'] = '123456789999';
    $cloudflare_zone_settings2['name'] = 'test-domain2.com';
    $cloudflare_zone_settings2['status'] = 'Active';
    $cloudflare_zone_settings2['paused'] = FALSE;
    $cloudflare_zone_settings2['type'] = 'zone';
    $cloudflare_zone_settings2['development_mode'] = FALSE;
    $cloudflare_zone_settings2['name_servers'] = ['1.2.3.4', '1.2.3.5'];
    $cloudflare_zone_settings2['original_name_servers'] = ['1.2.3.4', '1.2.3.5'];
    $cloudflare_zone_settings2['original_registrar'] = 'rsa.com';
    $cloudflare_zone_settings2['original_dnshost'] = ['1.2.3.4', '1.2.3.5'];
    $cloudflare_zone_settings2['modified_on'] = '1453579581 ';
    $cloudflare_zone_settings2['created_on'] = '1453579581';
    $cloudflare_zone_settings2['owner'] = 'Drupal Developer';
    $cloudflare_zone_settings2['permissions'] = 'EMPTY';
    $cloudflare_zone_settings2['plan'] = 'EMPTY';

    $zone1 = (object) $cloudflare_zone_settings;
    $zone2 = (object) $cloudflare_zone_settings2;

    $has_multi_zone = \Drupal::state()->get('cloudflaretesting.multizone');

    if ($has_multi_zone) {
      return [$zone1, $zone2];
    }

    else {
      return [$zone1];
    }
  }

  /**
   * Tells the mock to assert if credentials are valid or not.
   *
   * @param bool $assert_valid_credentials
   *   TRUE to mock credentials are met.  FALSE otherwise.
   */
  public static function mockAssertValidCredentials($assert_valid_credentials) {
    \Drupal::state()->set('cloudflaretesting.assetValidCredentials', $assert_valid_credentials);
  }

  /**
   * Tells the mock to return multiple zones.
   *
   * @param bool $multi_zone
   *   TRUE to mock multipleZones.  FALSE for singlezone.
   */
  public static function mockMultiZoneAccount($multi_zone) {
    \Drupal::state()->set('cloudflaretesting.multizone', $multi_zone);
  }

  /**
   * {@inheritdoc}
   */
  public static function assertValidToken($apitoken, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state, $zone_name = '') {
    $assert_valid_credentials = \Drupal::state()->get('cloudflaretesting.assetValidCredentials');
    if ($assert_valid_credentials != TRUE) {
      throw new \Exception("invalid", 1);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function assertValidCredentials($apikey, $email, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state) {
    $assert_valid_credentials = \Drupal::state()->get('cloudflaretesting.assetValidCredentials');
    if ($assert_valid_credentials != TRUE) {
      throw new \Exception("invalid", 1);
    }

  }

}
