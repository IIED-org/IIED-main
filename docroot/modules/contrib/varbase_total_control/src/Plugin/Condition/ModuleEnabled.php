<?php

namespace Drupal\varbase_total_control\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Module' condition.
 *
 * @Condition(
 *   id = "module_enabled",
 *   label = @Translation("Module enabled"),
 * )
 */
class ModuleEnabled extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['module'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Module machine name'),
      '#default_value' => $this->configuration['module'],
      '#description' => $this->t('The module’s machine name. Example: <em>page_manager</em>.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'module' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['module'] = $form_state->getValue('module');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {

    // Use the role labels. They will be sanitized below.
    $module = $this->configuration['module'];
    if (!empty($this->configuration['negate'])) {
      return $this->t('If the module <b>@module</b> is not installed.', ['@module' => $module]);
    }
    else {
      return $this->t('If the module <b>@module</b> is installed.', ['@module' => $module]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $moduleHandler = \Drupal::service('module_handler');
    return (bool) $moduleHandler->moduleExists($this->configuration['module']);
  }

}
