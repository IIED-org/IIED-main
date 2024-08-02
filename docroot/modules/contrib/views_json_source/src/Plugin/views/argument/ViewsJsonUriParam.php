<?php

namespace Drupal\views_json_source\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Base argument handler for views_json_source.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_json_source_uri_param")
 */
class ViewsJsonUriParam extends ArgumentPluginBase {

  /**
   * Add this filter to the query.
   */
  public function query($group_by = FALSE) {
    $this->query->addUrlParams($this->argument);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['markup'] = [
      '#markup' => $this->t('Add this field to pick the dynamic value from the URL.'),
    ];
  }

}
