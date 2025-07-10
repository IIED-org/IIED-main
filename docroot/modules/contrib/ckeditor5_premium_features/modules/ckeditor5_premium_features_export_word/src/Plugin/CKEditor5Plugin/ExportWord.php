<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features_export_word\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5_premium_features\Plugin\CKEditor5Plugin\ExportBase;
use Drupal\ckeditor5_premium_features_export_word\Form\SettingsForm;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 "Export to Word" plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class ExportWord extends ExportBase {

  const EXPORT_PDF_PLUGIN_ID = 'exportWord';

  const EXPORT_FILE_EXTENSION = '.docx';

  const CONFIGURATION_ID = 'ckeditor5_premium_features_export_word.settings';

  const EXPORT_SETTING_FORM = SettingsForm::class;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('config.factory'),
      $container->get('ckeditor5_premium_features_export_word.config_handler.export_settings'),
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
        'custom_css' => NULL,
        'header' => [
          [
            'html' => NULL,
            'css' => NULL,
            'type' => NULL,
          ],
        ],
        'footer' => [
          [
            'html' => NULL,
            'css' => NULL,
            'type' => NULL,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    if ($this->settingsConfigHandler->getEnvironmentId() && $this->settingsConfigHandler->getAccessKey() && !ckeditor5_premium_features_check_jwt_installed()) {
      $message = $this->t("Export to Word plugin is working in license key authentication mode because its required dependency <code>firebase/php-jwt</code> is not installed. This may result with limited functionality.");
      ckeditor5_premium_features_display_missing_dependency_warning($message);
    }
    $static_plugin_config = parent::getDynamicPluginConfig($static_plugin_config, $editor);

    $options = &$static_plugin_config[$this->getFeaturedPluginId()]['converterOptions'];

    // Word converter requires a different name than the PDF converter.
    if (isset($options['page_orientation'])) {
      $options['orientation'] = $options['page_orientation'];
    }

    foreach (['footer', 'header'] as $item) {
      if (isset($options[$item]) && is_array($options[$item])) {
        $this->cleanUpEmptyHtmlElements($options[$item]);
      }
    }

    if (\Drupal::service('ckeditor5_premium_features.core_library_version_checker')->isLibraryVersionHigherOrEqual('43.0.0')) {
      $this->convertConfigOptionsFormatToV2($options);
    }

    return $static_plugin_config;
  }

  /**
   * Removes items that have the empty HTML content.
   *
   * @param array $element
   *   The element to be processed.
   */
  private function cleanUpEmptyHtmlElements(array &$element): void {
    foreach ($element as $key => $item) {
      if (empty($item['html'])) {
        unset($element[$key]);
      }
    }
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
   * Converts the Export to Word configuration options format to the V2 format.
   *
   * @param array $config
   *   The Export to Word configuration array.
   * @return void
   */
  private function convertConfigOptionsFormatToV2(array &$config): void {
    $oldFormatConfig = $config;
    $config = [];
    $config['document']['size'] = $oldFormatConfig['format'];
    $config['document']['margins'] = [
      'top' => $oldFormatConfig['margin_top'],
      'bottom' => $oldFormatConfig['margin_bottom'],
      'left' => $oldFormatConfig['margin_left'],
      'right' => $oldFormatConfig['margin_right'],
    ];
    if (isset($oldFormatConfig['orientation'])) {
      $config['document']['orientation'] = $oldFormatConfig['orientation'];
    }
    if (isset($oldFormatConfig['header'])) {
      $config['headers'] = $this->convertHeaderAndFooterConfigToV2($oldFormatConfig['header']);
    }
    if (isset($oldFormatConfig['footer'])) {
      $config['footers'] = $this->convertHeaderAndFooterConfigToV2($oldFormatConfig['footer']);
    }
  }

  /**
   * Converts the Export to Word header and footer configuration to the V2 format.
   *
   * @param array $v1Config
   *   The V1 configuration array.
   * @return array
   *   The V2 configuration array.
   */
  private function convertHeaderAndFooterConfigToV2($v1Config): array {
    $v2Config = [];
    foreach ($v1Config as $v1ConfigItem) {
      $type = $v1ConfigItem['type'];
      $v2Config[$type] = [
        'html' => $v1ConfigItem['html'],
        'css' => $v1ConfigItem['css'],
      ];
    }
    return $v2Config;
  }

}
