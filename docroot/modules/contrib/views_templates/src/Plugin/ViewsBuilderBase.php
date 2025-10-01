<?php

namespace Drupal\views_templates\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\views\Entity\View;

/**
 * Base builder for View Templates.
 *
 * This class get Views information for Plugin definition.
 * Extending classes can use derivatives to make many plugins.
 */
abstract class ViewsBuilderBase extends PluginBase implements ViewsBuilderPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return $this->getDefinitionValue('base_table');
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    return $this->getDefinitionValue('admin_label');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getDefinitionValue('description');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionValue($key) {
    $def = $this->getPluginDefinition();
    if (isset($def[$key])) {
      return $def[$key];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createView($options = NULL) {

    $view_values = [
      'id' => $options['id'],
      'label' => $options['label'],
      'description' => $options['description'],
      'base_table' => $this->getBaseTable(),
    ];
    return View::create($view_values);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function templateExists() {
    return TRUE;
  }

}
