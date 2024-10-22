<?php

namespace Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck;

use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks that the site is within CloudFlare's API rate limits.
 *
 * CloudFlare currently has a rate limit of 1200 Api calls every 5 minutes.
 *
 * @see https://developers.cloudflare.com/fundamentals/api/reference/limits/
 *
 * @PurgeDiagnosticCheck(
 *   id = "cloudflare_api_rate_limit_check",
 *   title = @Translation("CloudFlare - Api Rate limit check."),
 *   description = @Translation("Checks that the site is not violating CloudFlare's overall Api rate limit."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"cloudflare"}
 * )
 */
class ApiRateLimitCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  // The CloudFlare API sets a maximum of 1,200 requests in a 5-minute period.
  public const API_RATE_LIMIT = 1200;

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * Checks that the Composer dependencies for CloudFlare are met.
   *
   * @var \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface
   */
  protected $cloudFlareComposerDependenciesCheck;

  /**
   * Constructs a ApiRateLimitCheck diagnostic check object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks that the composer dependencies for CloudFlare are met.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CloudFlareStateInterface $state, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cloudflare.state'),
      $container->get('cloudflare.composer_dependency_check')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->cloudFlareComposerDependenciesCheck->check()) {
      $this->recommendation = $this->t("Composer dependencies unmet.  Unable to assess API rate limits.");
      return self::SEVERITY_ERROR;
    }

    // Current number of purges today.
    $rate_count = $this->state->getApiRateCount();
    $this->value = $rate_count;

    // Warn at 75% of capacity.
    $daily_warning_level = .75 * self::API_RATE_LIMIT;

    $message_variables = [
      '@rate_limit' => self::API_RATE_LIMIT,
      '@rate_count' => $rate_count,
    ];

    if ($rate_count >= self::API_RATE_LIMIT) {
      $this->recommendation = $this->t('Exceeded API limit of @rate_count/@rate_limit every 5 minutes.', $message_variables);
      return self::SEVERITY_ERROR;
    }

    elseif ($rate_count >= $daily_warning_level) {
      $this->recommendation = $this->t('Approaching API limit of @rate_count/@rate_limit every 5 minutes.', $message_variables);
      return self::SEVERITY_WARNING;
    }

    elseif ($rate_count < $daily_warning_level) {
      $this->recommendation = $this->t('Site is safely below ( @rate_count ) the rate limit of @rate_limit every 5 minutes.', $message_variables);
      return self::SEVERITY_OK;
    }
  }

}
