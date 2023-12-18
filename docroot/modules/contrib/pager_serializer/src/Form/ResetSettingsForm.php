<?php

namespace Drupal\pager_serializer\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ResetSettingsForm extends ConfirmFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pager_serializer.settings';

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this
      ->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    \Drupal::service('config.factory')->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('rows_label', 'rows')
      ->set('pager_label', 'pager')
      ->set('pager_object_enabled', TRUE)

      ->set('current_page_enabled', TRUE)
      ->set('current_page_label', 'current_page')

      ->set('total_items_enabled', TRUE)
      ->set('total_items_label', 'total_items')

      ->set('total_pages_enabled', TRUE)
      ->set('total_pages_label', 'total_pages')

      ->set('items_per_page_enabled', TRUE)
      ->set('items_per_page_label', 'items_per_page')
      ->save();

    $url = Url::fromRoute('pager_serializer.settings');
    $form_state->setRedirectUrl($url);

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "page_serializer_reset_settings_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('pager_serializer.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to reset to defaults?');
  }

}
