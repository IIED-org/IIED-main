<?php

namespace Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;

/**
 * Trait ImageFieldFormatterSettingsFormTrait.
 *
 * Get settings form element.
 *
 * @package Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter
 */
trait ImageFieldFormatterSettingsFormTrait {

  /**
   * Get settings form element.
   *
   * @param array $element
   *   Element.
   *
   * @return array
   *   Settings form element.
   */
  protected function getSettingsFormElement(array $element): array {
    $parentFileFields = $this->getParentEntityFields();

    if (!empty($parentFileFields)) {
      $element[static::PDF_FILE_FIELD_SETTING] = [
        '#type' => 'select',
        '#title' => $this->t('Field containing the pdf'),
        '#options' => $parentFileFields,
        '#default_value' => $this->getSetting(static::PDF_FILE_FIELD_SETTING),
      ];

      $element[static::PDF_PAGE_SETTING] = [
        '#type' => 'number',
        '#title' => $this->t('Choose the page of the pdf to get the image from'),
        '#min' => 1,
        '#default_value' => !empty($this->getSetting(static::PDF_PAGE_SETTING)) ? $this->getSetting(static::PDF_PAGE_SETTING) : static::DEFAULT_PDF_PAGE_SETTING,
      ];

      $element[static::IMAGE_FORMAT_SETTINGS] = [
        '#type' => 'select',
        '#title' => $this->t('Choose image format'),
        '#options' => ['jpg' => 'jpg', 'png' => 'png'],
        '#default_value' => !empty($this->getSetting(static::IMAGE_FORMAT_SETTINGS)) ? $this->getSetting(static::IMAGE_FORMAT_SETTINGS) : static::DEFAULT_IMAGE_FORMAT_SETTING,
      ];

      $element['image_link']['#options'][static::IMAGE_LINK_TYPE_SETTING] = $this->t('PDF File');
      $element['image_link']['#weight'] = 0;
      $element['image_link']['#attributes']['class'][] = 'thumbnail-pdf-link';

      $element[static::IMAGE_LINK_ATTRIBUTE_DOWNLOAD_SETTING] = [
        '#prefix' => '<div id="thumbnail-pdf-link-attributes" class="fieldset"><div class="fieldset__legend"><span class="fieldset__label">' . $this->t('HTML extra attributes') . '</span><div class="fieldset__wrapper">',
        '#type' => 'checkbox',
        '#title' => 'download',
        '#weight' => 0,
        '#default_value' => $this->getSetting(static::IMAGE_LINK_ATTRIBUTE_DOWNLOAD_SETTING),
      ];

      $element[static::IMAGE_LINK_ATTRIBUTE_TARGET_SETTING] = [
        '#type' => 'textfield',
        '#title' => $this->t('target :'),
        '#description' => t('Ex: _self, _blank, _parent, _top'),
        '#weight' => 0,
        '#size' => 5,
        '#default_value' => $this->getSetting(static::IMAGE_LINK_ATTRIBUTE_TARGET_SETTING),
      ];

      $element[static::IMAGE_LINK_ATTRIBUTE_REL_SETTING] = [
        '#suffix' => '</div></div></div>',
        '#type' => 'textfield',
        '#title' => $this->t('rel :'),
        '#description' => t('Ex: alternate, author, bookmark, icon, nofollow, etc..'),
        '#size' => 5,
        '#weight' => 0,
        '#default_value' => $this->getSetting(static::IMAGE_LINK_ATTRIBUTE_REL_SETTING),
      ];

      $element[static::IMAGE_USE_CRON] = [
        '#type' => 'checkbox',
        '#title' => 'Use cron',
        '#weight' => 100,
        '#description' => t('Generates image in queue worker instead of on the fly.
        The first time the field is displayed, a default image will be shown while queueing image generation</br>
        You can wait for the cron run or you can execute the queue worker in config page <a href="@url">queue</a>', ['@url' => Url::fromRoute('media_pdf_thumbnail.settings.queue')->toString()]),
        '#default_value' => $this->getSetting(static::IMAGE_USE_CRON),
      ];
    }

    return $element;
  }

  /**
   * Get default settings.
   *
   * @return array
   *   Default settings.
   */
  public static function getDefaultSettings(): array {
    $settings = [
      static::PDF_FILE_FIELD_SETTING => '',
      static::PDF_PAGE_SETTING => static::DEFAULT_PDF_PAGE_SETTING,
      static::IMAGE_LINK_ATTRIBUTE_DOWNLOAD_SETTING => '',
      static::IMAGE_LINK_ATTRIBUTE_TARGET_SETTING => '',
      static::IMAGE_LINK_ATTRIBUTE_REL_SETTING => '',
      static::IMAGE_USE_CRON => '',
      static::IMAGE_FORMAT_SETTINGS => static::DEFAULT_IMAGE_FORMAT_SETTING,
    ];

    foreach (_media_pdf_thumbnail_get_fields_list() as $bundleId => $infos) {
      $settings[$bundleId . static::MEDIA_BUNDLE_PAGE] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_FIELD] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_ENABLE] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_LINK] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_ATTRIBUTES_DOWNLOAD] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_ATTRIBUTES_TARGET] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_ATTRIBUTES_REL] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_USE_CRON] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_IMAGE_FORMAT] = '';
      $settings[$bundleId . static::MEDIA_BUNDLE_IMAGE_STYLE] = '';
    }
    return $settings + parent::defaultSettings();
  }

  /**
   * Get settings summary.
   *
   * @return array
   *   Settings summary.
   */
  public function getSettingsSummary(): array {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Original image');
    }

    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
      static::IMAGE_LINK_TYPE_SETTING => t('Linked to pdf file'),
    ];

    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    $image_format = $this->getSetting(static::IMAGE_FORMAT_SETTINGS);
    $image_format = empty($image_format) ? 'jpg' : $image_format;
    $summary[] = t('Image format : @format', ['@format' => $image_format]);

    $user_cron = $this->getSetting(static::IMAGE_USE_CRON);
    if (!empty($user_cron)) {
      $summary[] = t('Cron enable');
    }

    return $summary;
  }

}
