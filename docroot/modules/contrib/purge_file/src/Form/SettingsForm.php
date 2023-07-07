<?php

namespace Drupal\purge_file\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Purge file settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Used to get the processors option list.
   *
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->purgeProcessors = $container->get('purge.processors');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_file_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['purge_file.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $enabled_processors = $this->processorsOptionList();

    $config = $this->config('purge_file.settings');
    $form['base_urls'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL(s)'),
      '#description' => $this->t('The base URLs of the site, separated by commas. Set it up for sites which front URL is different than the backoffice URL.'),
      '#default_value' => $config->get('base_urls'),
    ];

    $form['processor'] = [
      '#type' => 'select',
      '#title' => $this->t('Processor'),
      '#description' => $this->t('The purge processor that will be used to purge the updated / deleted files.'),
      '#default_value' => $config->get('processor'),
      '#options' => $enabled_processors,
      '#disabled' => empty($enabled_processors),
      '#required' => TRUE,
    ];

    if (empty($enabled_processors)) {
      $form['processors_unavailable'] = [
        '#type' => 'item',
        '#markup' => $this->t('No processors available. Please enable a purge processor to your site to make the module works.'),
      ];
    }

    $form['wildcard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wilcard'),
      '#description' => $this->t('Add wilcard to end of purge URL, purging all variants, such as query string tracking codes.'),
      '#default_value' => $config->get('wildcard'),
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('By checking this, a log will be created for each time a file is purged. Use it only for debugging purposes.'),
      '#default_value' => $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * List of processors that can be selected to purge the files.
   *
   * @return array
   *   Key-value of respective plugin id and plugin label.
   */
  protected function processorsOptionList() {
    $processors_enabled = $this->purgeProcessors->getPluginsEnabled();
    $processors_list = [];
    foreach ($processors_enabled as $processor_id) {
      $processors_list[$processor_id] = $this->purgeProcessors->get($processor_id)->getLabel();
    }
    return $processors_list;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('purge_file.settings')
      ->set('wildcard', $form_state->getValue('wildcard'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('base_urls', $form_state->getValue('base_urls'))
      ->set('processor', $form_state->getValue('processor'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
