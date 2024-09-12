<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Media PDF Thumbnail settings for this site.
 */
class MediaPdfThumbnailSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'media_pdf_thumbnail.settings';

  const DESTINATION_URI_PUBLIC = 'destination_uri_public';

  const DESTINATION_URI_PRIVATE = 'destination_uri_private';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'media_pdf_thumbnail_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['media_pdf_thumbnail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['uri'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('By default images are generated in the same place where the source pdf file is. You can set here a different local uri for image destination'),
    ];
    $form['uri'][self::DESTINATION_URI_PUBLIC] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination uri for public files'),
      '#default_value' => $this->config('media_pdf_thumbnail.settings')
        ->get(self::DESTINATION_URI_PUBLIC),
      '#description' => $this->t('Ex : public://pdf-thumbnails'),
    ];
    $form['uri'][self::DESTINATION_URI_PRIVATE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination uri for private file'),
      '#default_value' => $this->config('media_pdf_thumbnail.settings')
        ->get(self::DESTINATION_URI_PRIVATE),
      '#description' => $this->t('Ex : private://pdf-thumbnails'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $destinationUris = [
      self::DESTINATION_URI_PUBLIC => $form_state->getValue(self::DESTINATION_URI_PUBLIC),
      self::DESTINATION_URI_PRIVATE => $form_state->getValue(self::DESTINATION_URI_PRIVATE),
    ];
    foreach ($destinationUris as $name => $destinationUri) {
      if ($destinationUri && (!(str_starts_with($destinationUri, 'public://') || str_starts_with($destinationUri, 'private://')))) {
        $form_state->setErrorByName($name, $this->t('Destination Uri must be a valid uri'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::CONFIG_NAME)
      ->set(self::DESTINATION_URI_PUBLIC, $form_state->getValue(self::DESTINATION_URI_PUBLIC))
      ->save();
    $this->config(self::CONFIG_NAME)
      ->set(self::DESTINATION_URI_PRIVATE, $form_state->getValue(self::DESTINATION_URI_PRIVATE))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get config uri.
   *
   * @param string $value
   *   Config value.
   *
   * @return string
   *   Config uri.
   */
  public static function getConfigUri(string $value): string {
    return match ($value) {
      'public' => self::DESTINATION_URI_PUBLIC,
      'private' => self::DESTINATION_URI_PRIVATE,
      default => NULL
    };
  }

}
