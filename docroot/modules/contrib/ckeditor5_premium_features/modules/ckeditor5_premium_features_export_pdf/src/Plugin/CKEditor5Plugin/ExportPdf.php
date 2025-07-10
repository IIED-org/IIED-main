<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features_export_pdf\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5_premium_features\Plugin\CKEditor5Plugin\ExportBase;
use Drupal\ckeditor5_premium_features_export_pdf\Form\SettingsForm;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 "Export to Pdf" plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class ExportPdf extends ExportBase {

  const EXPORT_PDF_PLUGIN_ID = 'exportPdf';

  const EXPORT_FILE_EXTENSION = '.pdf';

  const CONFIGURATION_ID = 'ckeditor5_premium_features_export_pdf.settings';

  const EXPORT_SETTING_FORM = SettingsForm::class;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('config.factory'),
      $container->get('ckeditor5_premium_features_export_pdf.config_handler.export_settings'),
      $container->get('ckeditor5_premium_features.file_name_generator'),
      $container->get('ckeditor5_premium_features.css_style_provider'),
      $container->get('file_system'),
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'converter_url' => NULL,
      'converter_options' => [
        'format' => NULL,
        'margin_top' => [
          'value' => NULL,
          'units' => NULL,
        ],
        'margin_bottom' => [
          'value' => NULL,
          'units' => NULL,
        ],
        'margin_left' => [
          'value' => NULL,
          'units' => NULL,
        ],
        'margin_right' => [
          'value' => NULL,
          'units' => NULL,
        ],
        'page_orientation' => NULL,
        'custom_css' => NULL,
        'header_html' => NULL,
        'footer_html' => NULL,
        'header_and_footer_css' => NULL,
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getSettingsForm(): string {
    return self::EXPORT_SETTING_FORM;
  }

  /**
   * {@inheritDoc}
   */
  public function getConfigId(): string {
    return self::CONFIGURATION_ID;
  }

  /**
   * {@inheritDoc}
   */
  public function getFeaturedPluginId(): string {
    return self::EXPORT_PDF_PLUGIN_ID;
  }

  /**
   * {@inheritDoc}
   */
  public function getExportFileExtension(): string {
    return self::EXPORT_FILE_EXTENSION;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    if ($this->settingsConfigHandler->getEnvironmentId() && $this->settingsConfigHandler->getAccessKey() && !ckeditor5_premium_features_check_jwt_installed()) {
      $message = $this->t("Export to PDF plugin is working in license key authentication mode because its required dependency <code>firebase/php-jwt</code> is not installed. This may result with limited functionality.");
      ckeditor5_premium_features_display_missing_dependency_warning($message);
    }
    return parent::getDynamicPluginConfig($static_plugin_config, $editor);
  }

}
