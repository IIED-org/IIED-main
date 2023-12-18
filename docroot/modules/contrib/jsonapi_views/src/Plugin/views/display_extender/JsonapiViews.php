<?php

namespace Drupal\jsonapi_views\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Display extender plugin to control JSON:API exposure.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "jsonapi_views",
 *   title = @Translation("Expose via JSON:API"),
 *   help = @Translation("Controls exposure the view with JSON:API."),
 *   no_ui = FALSE,
 * )
 */
class JsonapiViews extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return [
      'enabled' => ['default' => TRUE],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'jsonapi_views') {
      $form['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Expose via JSON:API'),
        '#description' => $this->t('Controls exposure the view with JSON:API.'),
        '#default_value' => $this->options['enabled'],
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'jsonapi_views') {
      $this->options['enabled'] = (bool) $form_state->getValue('enabled');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['jsonapi_views'] = [
      'title' => $this->t('JSON:API'),
      'column' => 'second',
    ];
    $options['jsonapi_views'] = [
      'category' => 'jsonapi_views',
      'title' => $this->t('Exposed via JSON:API'),
      'value' => $this->options['enabled'] ? $this->t('Yes') : $this->t('No'),
    ];
  }

  /**
   * Checks whether it is allowed to expose view via JSON:API.
   *
   * @return bool
   *   Whether view is allowed to be exposed via JSON:API.
   */
  public function isExposed() {
    return $this->options['enabled'];
  }

}
