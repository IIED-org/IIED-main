<?php

namespace Drupal\cloudflare_purge\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default ConfigFormBase for the cloudflare_purge module.
 */
class CloudflarePurgeForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  public const SETTINGS = 'cloudflare_purge.settings';
  /**
   * Log activity when user enter credentials.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Cloudflare purge constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelInterface $logger) {
    parent::__construct($config_factory);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cloudflare_purge.logger.channel.cloudflare_purge')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId():string {
    return 'cloudflare_purge_form';
  }

  /**
   * Build the form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State interface.
   *
   * @return array
   *   Return array.
   */
  public function buildForm(array $form, FormStateInterface $form_state):array {
    $config = $this->config(static::SETTINGS);

    if (!$this->isOverridden('zone_id')) {
      $form['cloudflare_purge_form']['zone_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Zone ID'),
        '#size' => 60,
        '#required' => TRUE,
        '#default_value' => $config->get('zone_id'),
        '#attributes' => [
          'placeholder' => [
            t('Zone ID'),
          ],
        ],
        '#description' => $this->t('Enter Cloudflare Zone ID.'),
      ];
    }
    else {
      $form['cloudflare_purge_form']['zone_id'] = [
        '#type' => 'item',
        '#title' => $this->t('Zone ID'),
        '#markup' => $this->t('Zone ID is currently being overridden in <em>settings.php</em>.'),
      ];
    }

    if (!$this->isOverridden('authorization')) {
      $form['cloudflare_purge_form']['authorization'] = [
        '#type' => 'textfield',
        '#title' => t('Authorization'),
        '#size' => 60,
        '#required' => TRUE,
        '#default_value' => $config->get('authorization'),
        '#attributes' => [
          'placeholder' => [
            $this->t('Authorization'),
          ],
        ],
        '#description' => $this->t('Enter Cloudflare Authorization.'),
      ];
    }
    else {
      $form['cloudflare_purge_form']['authorization'] = [
        '#type' => 'item',
        '#title' => $this->t('Authorization'),
        '#markup' => $this->t('Authorization is currently being overridden in <em>settings.php</em>.'),
      ];
    }

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save all configs on submit.
    $this->config(self::SETTINGS)
      ->set('zone_id', $form_state->getValue('zone_id'))
      ->set('authorization', $form_state->getValue('authorization'))
      ->save();
    parent::submitForm($form, $form_state);

  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   Check for the field value.
   *
   * @return mixed
   *   Return the value
   */
  protected function isOverridden(string $name) {
    $cloudflareCredentials = Settings::get('cloudflare_purge_credentials');
    if (!empty($cloudflareCredentials[$name])) {
      return $cloudflareCredentials[$name];
    }
    return FALSE;
  }

}
