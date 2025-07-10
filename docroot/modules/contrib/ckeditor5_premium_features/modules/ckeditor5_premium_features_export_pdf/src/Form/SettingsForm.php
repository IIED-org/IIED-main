<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features_export_pdf\Form;

use Drupal\ckeditor5_premium_features\Form\BaseExportSettingsForm;
use Drupal\ckeditor5_premium_features\Utility\FormElement;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form of the "Export to PDF" feature.
 */
class SettingsForm extends BaseExportSettingsForm {

  const EXPORT_PDF_CONFIG_NAME = 'ckeditor5_premium_features_export_pdf.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ckeditor5_premium_features_export_pdf_settings';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSettingsRouteName(): string {
    return 'ckeditor5_premium_features_export_pdf.form.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomCssFileName(): string {
    return 'ckeditor5-custom-pdf-styles';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigId(): string {
    return self::EXPORT_PDF_CONFIG_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public static function form(array $form, FormStateInterface $form_state, Config $config): array {
    if ($form_state->getFormObject()->getFormId() == 'ckeditor5_premium_features_export_pdf_settings') {
      self::checkDependencyPackage();
    }
    $form['converter_url'] = [
      '#type' => 'textfield',
      '#title' => t('Converter URL'),
      '#description' => t('Leave this field empty unless you are using the on-premises version of Export to PDF.'),
      '#default_value' => $config->get('converter_url'),
    ];

    $form['env'] = [
      '#type' => 'textfield',
      '#title' => t('Environment ID'),
      '#required' => FALSE,
      '#description' => t('Leave this field empty unless, for Export to PDF, you are using a different environment than the one from the main module configuration.'),
      '#default_value' => $config->get('env'),
    ];

    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => t('Access key'),
      '#required' => FALSE,
      '#description' => t('Leave this field empty unless, for Export to PDF, you are using a different environment than the one from the main module configuration.'),
      '#default_value' => $config->get('access_key'),
    ];

    $options_key = 'converter_options';
    $form[$options_key] = [
      '#type' => 'details',
      '#title' => t('Converter options'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $options = &$form[$options_key];

    FormElement::format($options, [
      '#default_value' => $config->get($options_key . '.format') ?? 'A4',
    ]);

    $margins = [
      'top',
      'bottom',
      'left',
      'right',
    ];

    foreach ($margins as $margin) {
      $margin_config = $config->get($options_key . '.margin_' . $margin);
      FormElement::marginElement($options, $margin, $margin_config);
    }

    FormElement::pageOrientation($options, [
      '#default_value' => $config->get($options_key . '.page_orientation') ?? 'portrait',
    ]);

    $options['custom_css'] = [
      '#type' => 'textarea',
      '#title' => t('Custom css'),
      '#default_value' => $config->get($options_key . '.custom_css'),
    ];

    $options['header_html'] = [
      '#type' => 'textarea',
      '#title' => t('Header'),
      '#default_value' => $config->get($options_key . '.header_html'),
      '#ajax' => FALSE,
    ];

    $options['footer_html'] = [
      '#type' => 'textarea',
      '#title' => t('Footer'),
      '#default_value' => $config->get($options_key . '.footer_html'),
      '#ajax' => FALSE,
    ];

    $options['header_and_footer_css'] = [
      '#type' => 'textarea',
      '#title' => t('Header and footer css'),
      '#default_value' => $config->get($options_key . '.header_and_footer_css'),
      '#ajax' => FALSE,
    ];

    return $form;
  }

  /**
   * Checks if the required library is installed and displays warning message in case it's missing,
   */
  public static function checkDependencyPackage(): void {
    if (!ckeditor5_premium_features_check_dependency_class('Firebase\JWT\JWT')) {
      $message = t('Export to PDF plugin will work in license key authentication mode because its required dependency <code>firebase/php-jwt</code> is not installed. This may result with limited functionality.');
      ckeditor5_premium_features_display_missing_dependency_warning($message);
    }
  }

}
