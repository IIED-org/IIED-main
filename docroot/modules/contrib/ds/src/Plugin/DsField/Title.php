<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin that renders a title.
 */
abstract class Title extends Field {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $settings['link'] = [
      '#type' => 'checkbox',
      '#title' => 'Link',
      '#default_value' => $config['link'],
    ];
    $settings['link class'] = [
      '#type' => 'textfield',
      '#title' => 'Link class',
      '#default_value' => $config['link class'],
      '#description' => $this->t('Put a class on the link. Eg: btn btn-default'),
      '#states' => [
        'visible' => [
          ':input[name="fields[node_title][settings_edit_form][settings][link]"]' => [
            'checked' => TRUE
          ]
        ]
      ]
    ];
    $settings['wrapper'] = [
      '#type' => 'textfield',
      '#title' => 'Wrapper',
      '#default_value' => $config['wrapper'],
      '#description' => $this->t('Eg: h1, h2, p'),
    ];
    $settings['class'] = [
      '#type' => 'textfield',
      '#title' => 'Class',
      '#default_value' => $config['class'],
      '#description' => $this->t('Put a class on the wrapper. Eg: block-title'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    if (!empty($config['link'])) {
      $summary[] = 'Link: yes';
    }
    else {
      $summary[] = 'Link: no';
    }

    if (!empty($config['link']) && !empty($config['link class'])) {
      $summary[] = 'Link class: ' . $config['link class'];
    }

    $summary[] = 'Wrapper: ' . $config['wrapper'];

    if (!empty($config['class'])) {
      $summary[] = 'Class: ' . $config['class'];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $configuration = [
      'link' => 0,
      'link class' => '',
      'wrapper' => 'h2',
      'class' => '',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityRenderKey() {
    return 'title';
  }

}
