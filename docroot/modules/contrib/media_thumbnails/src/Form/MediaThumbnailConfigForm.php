<?php

namespace Drupal\media_thumbnails\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the media thumbnails config form.
 */
class MediaThumbnailConfigForm extends ConfigFormBase {

  /**
   * Stores a module manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_thumbnails_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['width'] = [
      '#default_value' => $this->config('media_thumbnails.settings')
        ->get('width'),
      '#description' => $this->t('The width for the generated thumbnails. Height will be calculated automatically.'),
      '#title' => $this->t('Thumbnail width'),
      '#type' => 'number',
    ];
    $form['bgcolor'] = [
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,
      '#title' => t('Background color'),
      '#type' => 'fieldset',
    ];
    $form['bgcolor']['bgcolor_active'] = [
      '#default_value' => $this->config('media_thumbnails.settings')
        ->get('bgcolor_active'),
      '#title' => $this->t('Add a custom background color. Uncheck to keep transparency.'),
      '#type' => 'checkbox',
    ];
    $form['bgcolor']['bgcolor_value'] = [
      '#default_value' => $this->config('media_thumbnails.settings')
        ->get('bgcolor_value'),
      '#description' => $this->t('Background color for transparent thumbnails, for plugins supporting this feature.'),
      '#title' => $this->t('Background color'),
      '#type' => 'color',
    ];
    $form['update_thumbnail'] = [
      '#default_value' => $this->config('media_thumbnails.settings')->get('update_thumbnail'),
      '#description' => $this->t('Update existing thumbnail on media save.'),
      '#title' => $this->t('Update thumbnail'),
      '#type' => 'checkbox',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('media_thumbnails.settings')
      ->set('width', $form_state->getValue('width'))
      ->set('bgcolor_active', $form_state->getValue('bgcolor_active'))
      ->set('bgcolor_value', $form_state->getValue('bgcolor_value'))
      ->set('update_thumbnail', $form_state->getValue('update_thumbnail'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['media_thumbnails.settings'];
  }

}
