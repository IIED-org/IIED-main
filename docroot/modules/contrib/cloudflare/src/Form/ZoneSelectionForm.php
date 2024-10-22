<?php

namespace Drupal\cloudflare\Form;

use Drupal\cloudflare\CloudFlareZoneInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for selecting a CloudFlare zone.
 *
 * @package Drupal\cloudflare\Form
 */
class ZoneSelectionForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Wrapper to access the CloudFlare zone api.
   *
   * @var \Drupal\cloudflare\CloudFlareZoneInterface
   */
  protected $zoneApi;

  /**
   * A logger instance for CloudFlare.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * List of the zones for the current Api credentials.
   *
   * @var array
   */
  protected $zones;

  /**
   * Boolean indicates if CloudFlare dependencies have been met.
   *
   * @var bool
   */
  protected $cloudFlareComposerDependenciesMet;

  /**
   * Tracks if the current CloudFlare account has multiple zones.
   *
   * @var bool
   */
  protected $hasMultipleZones;

  /**
   * The cloudflare settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Whether the credentials have been validated.
   *
   * @var bool
   */
  protected bool $hasValidCredentials;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // This is a hack because could not get custom ServiceProvider to work.
    // this to work: https://www.drupal.org/node/2026959
    $has_zone_mock = $container->has('cloudflare.zonemock');

    return new static(
      $container->get('config.factory'),
      $has_zone_mock ? $container->get('cloudflare.zonemock') : $container->get('cloudflare.zone'),
      $container->get('logger.factory')->get('cloudflare'),
      $container->get('cloudflare.composer_dependency_check')->check()
    );
  }

  /**
   * Constructs a new ZoneSelectionForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param bool $composer_dependencies_met
   *   Checks that the composer dependencies for CloudFlare are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CloudFlareZoneInterface $zone_api, LoggerInterface $logger, $composer_dependencies_met) {
    $this->configFactory = $config_factory;
    $this->config = $config_factory->getEditable('cloudflare.settings');
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
    $this->cloudFlareComposerDependenciesMet = $composer_dependencies_met;
    $this->hasValidCredentials = $this->config->get('valid_credentials') === TRUE;

    // This test should be unnecessary since this form should only ever be
    // reached when the 2 conditions are met. It's being done from an abundance
    // of caution.
    if ($this->hasValidCredentials && $this->cloudFlareComposerDependenciesMet) {
      try {
        $this->zones = $this->zoneApi->listZones();
        $this->hasMultipleZones = count($this->zones) > 1;
      }
      catch (RequestException $e) {
        $this->messenger()->addError($this->t('Unable to connect to CloudFlare. You will not be able to change the selected Zone.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudflare_zone_selection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->buildZoneSelectSection();
  }

  /**
   * Builds zone selection section for inclusion in the settings form.
   *
   * @return array
   *   Form Api render array with selection section.
   */
  protected function buildZoneSelectSection() {
    $section = [];
    $section['zone_selection_fieldset'] = [
      '#type' => 'fieldset',
      '#weight' => 0,
    ];

    if (!$this->hasMultipleZones && $this->hasValidCredentials) {

      // It is possible to authenticate with the API without having configured a
      // domain in the CloudFlare console. This prevents a fatal error where
      // zones[0]->getZoneId() is called on a NULL reference.
      if (empty($this->zones)) {
        $add_site_link = Link::fromTextAndUrl(
          $this->t('add a site'),
          Url::fromUri('https://www.cloudflare.com/a/setup')
        );
        $section['zone_selection_fieldset']['zone_selection'] = [
          '#markup' => $this->t('<p>Your CloudFlare account does not have any zones configured. Verify your API details or @add_site_link via the console.</p>', [
            '@add_site_link' => $add_site_link->toString(),
          ]),
        ];
        return $section;
      }

      $zone_id = $this->zones[0]->id;
      $this->config->set('zone_id', [$zone_id])->save();
      $section['zone_selection_fieldset']['zone_selection'] = [
        '#markup' => $this->t('<p>Your CloudFlare account has a single zone which has been automatically selected for you.  Simply click "Finish" to save your settings.</p>'),
      ];

      return $section;
    }

    $listing = $this->buildZoneListing();
    $section['zone_selection_fieldset']['zone_selection'] = $listing;
    return $section;
  }

  /**
   * Builds a form render array for zone selection.
   *
   * @return array
   *   Form Api Render array for zone select.
   */
  public function buildZoneListing() {
    $zone_select = [];

    foreach ($this->zones as $zone) {
      $zone_select[$zone->id] = $zone->name;
    }

    $form_select_field = [
      '#type' => 'select',
      '#title' => $this->t('Select Zones'),
      '#disabled' => FALSE,
      '#options' => $zone_select,
      '#multiple' => TRUE,
      '#default_value' => $this->config->get('zone_id'),
      '#description' => $this->t('Select one or more zones (top level domain for the site). The zone ID corresponding to the domain will then be saved in the field.'),
    ];

    return $form_select_field;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->hasMultipleZones) {
      $zone_ids = $form_state->getValue('zone_selection');
      $this->config->set('zone_id', $zone_ids)->save();
    }

    $form_state->setRedirect('cloudflare.admin_settings_form');
  }

}
