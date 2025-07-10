<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features_email_editing\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5_premium_features\Utility\LibraryVersionChecker;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 Premium Features Email Editing Plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class EmailEditing extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface, CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * Creates the plugin instance.
   *
   * @param \Drupal\ckeditor5_premium_features\Utility\LibraryVersionChecker $libraryVersionChecker
   *   Helper for checking CKEditor 5 version.
   * @param \Drupal\ckeditor5_premium_features\Utility\PluginHelper $pluginHelper
   *   Helper for getting the editor toolbar plugins.
   * @param mixed ...$parent_arguments
   *   The parent plugin arguments.
   */
  public function __construct(
    protected LibraryVersionChecker $libraryVersionChecker, ...$parent_arguments) {
    parent::__construct(...$parent_arguments);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('ckeditor5_premium_features.core_library_version_checker'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'enable_configuration_helper' => FALSE,
      'suppress_all' => FALSE,
      'suppress_html_element' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['configuration_helper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email Configuration Helper'),
    ];
    $form['configuration_helper']['description'] = [
      '#markup' => $this->t('This plugin helps to configure text format used for creating email templates by providing information about configuration that is not well supported by email clients.'),

    ];
    $form['configuration_helper']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Email Configuration Helper'),
      '#default_value' => $this->configuration['enable_configuration_helper'] ?? FALSE,
      '#description' => $this->t('Enabling this setting will allow to use the Email Configuration Helper.'),
    ];
    $form['configuration_helper']['suppress_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Suppress all email editing logs'),
      '#default_value' => $this->configuration['suppress_all'] ?? FALSE,
      '#states' => [
        'visible' => [
          ':input[name="editor[settings][plugins][ckeditor5_premium_features_email_editing__email_editing][configuration_helper][enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['configuration_helper']['suppress_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Suppress unsupported HTML element logs'),
      '#default_value' => $this->configuration['suppress_html_element'] ?? FALSE,
      '#description' => $this->t('General HTML support is enabled by default. It allows to use some HTML tags that are not supported byt email clients. This setting allows to suppress logs about unsupported HTML elements.'),
      '#states' => [
        'visible' => [
          ':input[name="editor[settings][plugins][ckeditor5_premium_features_email_editing__email_editing][configuration_helper][enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['enable_configuration_helper'] = $form_state->getValue(['configuration_helper', 'enable']);
    $this->configuration['suppress_all'] = $form_state->getValue(['configuration_helper', 'suppress_all']);
    $this->configuration['suppress_html_element'] = $form_state->getValue(['configuration_helper', 'suppress_html']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $static_plugin_config['removePlugins'] = [];

    if ($this->configuration['enable_configuration_helper']) {
      if ($this->configuration['suppress_all']) {
        $static_plugin_config['email']['logs']['suppressAll'] = TRUE;
      }
      $static_plugin_config['email']['logs']['suppress'] = ['email-configuration-missing-merge-fields-plugin'];
      if ($this->configuration['suppress_html_element']) {
        $static_plugin_config['email']['logs']['suppress'][] = 'email-unsupported-html-element';
      }
    }
    else {
      $static_plugin_config['removePlugins'][] = 'EmailConfigurationHelper';
    }

    if (empty($static_plugin_config['removePlugins'])) {
      unset($static_plugin_config['removePlugins']);
    }

    return $static_plugin_config;
  }

}
