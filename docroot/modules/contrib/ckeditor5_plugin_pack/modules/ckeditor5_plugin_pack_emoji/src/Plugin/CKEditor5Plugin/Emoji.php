<?php

namespace Drupal\ckeditor5_plugin_pack_emoji\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Emoji plugin.
 */
class Emoji extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'definitionsUrl' => '',
      'dropdownLimit' => 6,
      'skinTone' => 'default',
      'useCustomFont' => FALSE,
      'version' => 16,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Custom definitions section.
    $form['definitionsUrl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom emoji definitions URL'),
      '#description' => $this->t('URL to a custom emoji definitions JSON file. Required if using custom definitions.'),
      '#default_value' => $this->configuration['definitionsUrl'],
      '#maxlength' => 1000,
    ];

    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('Emoji database version'),
      '#description' => $this->t('The emoji database version to use. Ignored if custom definitions URL is provided.'),
      '#default_value' => $this->configuration['version'],
      '#options' => [
        15 => 'v15',
        16 => 'v16',
      ]
    ];

    $form['dropdownLimit'] = [
      '#type' => 'number',
      '#title' => $this->t('Dropdown limit'),
      '#description' => $this->t('The maximum number of emojis displayed in the dropdown list.'),
      '#default_value' => $this->configuration['dropdownLimit'],
      '#min' => 2,
      '#max' => 20,
    ];

    $form['skinTone'] = [
      '#type' => 'select',
      '#title' => $this->t('Default skin tone'),
      '#default_value' => $this->configuration['skinTone'],
      '#options' => [
        'default' => $this->t('Default'),
        'light' => $this->t('Light'),
        'medium-light' => $this->t('Medium-light'),
        'medium' => $this->t('Medium'),
        'medium-dark' => $this->t('Medium-dark'),
        'dark' => $this->t('Dark'),
      ],
    ];

    $form['useCustomFont'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom font'),
      '#description' => $this->t('Disable OS-level emoji filtering and use custom font rendering.'),
      '#default_value' => $this->configuration['useCustomFont'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validate dropdown limit.
    $dropdownLimit = $form_state->getValue(['dropdownLimit']);
    if ($dropdownLimit < 1 || $dropdownLimit > 20) {
      $form_state->setErrorByName('display][dropdownLimit', $this->t('Dropdown limit must be between 1 and 20.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['definitionsUrl'] = $form_state->getValue(['definitionsUrl']);
    $this->configuration['version'] = $form_state->getValue(['version']);
    $this->configuration['dropdownLimit'] = $form_state->getValue(['dropdownLimit']);
    $this->configuration['skinTone'] = $form_state->getValue(['skinTone']);
    $this->configuration['useCustomFont'] = $form_state->getValue(['useCustomFont']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config = [];

    $config['emoji'] = [
      'dropdownLimit' => $this->configuration['dropdownLimit'] + 1,
      'useCustomFont' => $this->configuration['useCustomFont'],
    ];

    if (!empty($this->configuration['skinTone'])) {
      $config['emoji']['skinTone'] = $this->configuration['skinTone'];
    }

    if (empty($this->configuration['definitionsUrl'])) {
      if (!empty($this->configuration['version'])) {
        $config['emoji']['version'] = $this->configuration['version'];
      }
    }
    else{
      $config['emoji']['definitionsUrl'] = $this->configuration['definitionsUrl'];
    }

    return $config;
  }
}
