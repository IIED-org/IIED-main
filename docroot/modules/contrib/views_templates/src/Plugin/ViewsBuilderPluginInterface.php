<?php

namespace Drupal\views_templates\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates a common interface for Views Builder classes.
 */
interface ViewsBuilderPluginInterface extends PluginInspectionInterface {

  /**
   * Returns base table id.
   *
   * @return string
   *   Returns base table id.
   */
  public function getBaseTable();

  /**
   * Get template description.
   *
   * @return string
   *   Returns template description.
   */
  public function getDescription();

  /**
   * Get template admin label.
   *
   * @return string
   *   Returns template admin label.
   */
  public function getAdminLabel();

  /**
   * Get a value from the plugin definition.
   *
   * @param string $key
   *   The key to get the value from the plugin definition.
   *
   * @return mixed
   *   Returns a a value from the plugin definition.
   */
  public function getDefinitionValue($key);

  /**
   * Create a View. Don't save it.
   *
   * @param mixed $options
   *   Options to create a view.
   *
   * @return \Drupal\views\ViewEntityInterface
   *   Returns a view.
   */
  public function createView($options = NULL);

  /**
   * Return form elements of extra configuration when adding View from template.
   *
   * @param array $form
   *   The form in array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The values from the form.
   *
   * @return mixed
   *   Returns empty array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Determine if a template exists.
   *
   * @return bool
   *   Returns boolean value.
   */
  public function templateExists();

}
