<?php

namespace Drupal\ds_test\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsLayout;

/**
 * Layout class that extends DsLayout.
 */
class DsLayoutExtended extends DsLayout {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'extra_config' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();
    $form['extra_config'] = [
      '#group' => 'additional_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Extra config'),
      '#default_value' => $configuration['extra_config'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['extra_config'] = $form_state->getValue('extra_config');
  }

}
