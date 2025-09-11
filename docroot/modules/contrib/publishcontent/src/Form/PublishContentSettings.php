<?php

namespace Drupal\publishcontent\Form;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form to configure the settings.
 */
class PublishContentSettings extends ConfigFormBase {

  /**
   * Cache invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheInvalidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('cache_tags.invalidator')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, CacheTagsInvalidator $cache_invalidator) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->cacheInvalidator = $cache_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'publishcontent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'publishcontent_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('publishcontent.settings');

    $form['ui'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('User interface preferences'),
      '#description' => $this->t('Configure how users interact with the publish and unpublish toggle.'),
    ];

    $form['ui_localtask'] = [
      '#type' => 'checkbox',
      '#group' => 'ui',
      '#title' => $this->t('Publish and unpublish via local task'),
      '#default_value' => $config->get('ui_localtask'),
      '#description' => $this->t('A Publish/Unpublish link will appear alongside the node’s View and Edit links for users who have appropriate permissions.'),

    ];

    $form['ui_checkbox'] = [
      '#type' => 'checkbox',
      '#group' => 'ui',
      '#title' => $this->t('Publish and unpublish via checkbox'),
      '#default_value' => $config->get('ui_checkbox'),
      '#description' => $this->t('A checkbox will appear near the bottom of node edit forms for users who have permission to publish/unpublish. Users who do not have permission will see the checkbox but will not be able to change its value.'),
    ];

    $form['accountability'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Accountability preferences'),
      '#description' => $this->t('Configure what kind of trace the publish and unpublish toggle will leave in the system.'),
    ];

    $form['create_revision'] = [
      '#type' => 'checkbox',
      '#group' => 'accountability',
      '#title' => $this->t('Create new revision when publising/unpublishing a node'),
      '#default_value' => $config->get('create_revision'),
      '#description' => $this->t('Unpublishing or publishing a node will create a new revision automatically.'),
    ];

    $form['create_log_entry'] = [
      '#type' => 'checkbox',
      '#group' => 'accountability',
      '#title' => $this->t('Create a log entry when publishing or unpublishing a node'),
      '#default_value' => $config->get('create_log_entry'),
      '#description' => $this->t('Make Drupal log all publishing and unpublishing actions, to be able to see when and by whom the action was executed.'),
    ];
    $form['publish_text_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publish button value'),
      '#default_value' => $config->get('publish_text_value'),
      '#description' => $this->t('Set the text value for publishing content types. Default is set to Publish'),
      '#required' => TRUE,
    ];
    $form['unpublish_text_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Un-publish button value'),
      '#default_value' => $config->get('unpublish_text_value'),
      '#description' => $this->t('Set the text value for un-publishing content types. Default is set to Unpublish'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('publishcontent.settings');
    $config->set('ui_localtask', $form_state->getValue('ui_localtask'));
    $config->set('ui_checkbox', $form_state->getValue('ui_checkbox'));
    $config->set('create_revision', $form_state->getValue('create_revision'));
    $config->set('create_log_entry', $form_state->getValue('create_log_entry'));
    $config->set('publish_text_value', $form_state->getValue('publish_text_value'));
    $config->set('unpublish_text_value', $form_state->getValue('unpublish_text_value'));
    $config->save();
    $this->cacheInvalidator->invalidateTags(['local_task']);
    return parent::submitForm($form, $form_state);
  }

}
