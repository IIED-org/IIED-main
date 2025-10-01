<?php

namespace Drupal\views_templates_builder_test\Plugin\ViewsTemplateBuilder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_templates\Plugin\ViewsDuplicateBuilderBase;

/**
 * The ViewsBuilder.
 *
 * @ViewsBuilder(
 *  id = "view_duplicator_test",
 *  view_template_id = "simple_view",
 *  module = "views_templates_builder_test",
 *  replace_values = {
 *    "__TITLE" = "Title Changed",
 *    "__TITLE_ID" = "title"
 *  }
 * )
 */
class ViewDuplicatorTest extends ViewsDuplicateBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function alterViewTemplateAfterCreation(array &$view_template, $options = NULL) {
    parent::alterViewTemplateAfterCreation($view_template, $options);
    // Make a simple change. This one goes to 11!
    $view_template['display']['default']['display_options']['pager']['options']['items_per_page'] = $options['pager_count'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config_form['pager_count'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pager Count'),
      '#default_value' => '10',
    ];
    return $config_form;
  }

}
