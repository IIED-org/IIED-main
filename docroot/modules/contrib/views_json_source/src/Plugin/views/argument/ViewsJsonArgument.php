<?php

namespace Drupal\views_json_source\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base argument handler for views_json_source.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_json_source_argument")
 */
class ViewsJsonArgument extends ArgumentPluginBase {

  /**
   * Add this filter to the query.
   */
  public function query($group_by = FALSE) {
    $this->query->addFilter($this);
  }

  /**
   * Generate the filter criteria.
   */
  public function generate() {
    $operator = "=";
    $key = $this->options['key'];
    $value = $this->argument;

    return [$key, $operator, $value];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['key'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['key'] = [
      '#title' => $this->t('Key Chooser'),
      '#description' => $this->t('choose a key'),
      '#type' => 'textfield',
      '#default_value' => $this->options['key'],
      '#required' => TRUE,
    ];
  }

}
