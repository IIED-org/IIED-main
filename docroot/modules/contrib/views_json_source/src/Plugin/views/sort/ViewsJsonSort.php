<?php

namespace Drupal\views_json_source\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base sort handler for views_json_source.
 *
 * @ViewsSort("views_json_source_sort")
 */
class ViewsJsonSort extends SortPluginBase {

  /**
   * Option definition.
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['key'] = ['default' => ''];
    return $options;
  }

  /**
   * Options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['key'] = [
      '#title' => $this->t('Key Chooser'),
      '#description' => $this->t('choose a key'),
      '#type' => 'textfield',
      '#default_value' => $this->options['key'],
      '#required' => TRUE,
    ];
  }

  /**
   * Called to add the sort to a query.
   */
  public function query() {
    $this->query->addOrderBy(NULL, $this->options['key'], $this->options['order']);
  }

}
