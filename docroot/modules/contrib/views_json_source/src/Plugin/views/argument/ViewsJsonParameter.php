<?php

namespace Drupal\views_json_source\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base argument handler for views_json_source.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_json_source_parameter")
 */
class ViewsJsonParameter extends ArgumentPluginBase {

  /**
   * Add this filter to the query.
   */
  public function query($group_by = FALSE) {
    $this->query->addContextualFilter($this->argument);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['markup'] = [
      '#markup' => $this->t('Add this field to pick the dynamic node from the response.'),
    ];
  }

}
