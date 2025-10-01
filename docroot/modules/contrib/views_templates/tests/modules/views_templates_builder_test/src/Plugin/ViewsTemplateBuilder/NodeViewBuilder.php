<?php

namespace Drupal\views_templates_builder_test\Plugin\ViewsTemplateBuilder;

use Drupal\views_templates\Plugin\ViewsBuilderBase;

/**
 * Test comment.
 *
 * @todo Switch to our own annotation in ViewsBuilderPluginManager
 *
 * @ViewsBuilder(
 *  id = "node_builder",
 *  admin_label = "Node View",
 *  description = "A Test Node View",
 *  base_table = "node_field_data",
 * )
 */
class NodeViewBuilder extends ViewsBuilderBase {

  /**
   * {@inheritDoc}
   */
  public function createView($options = NULL) {
    $view = parent::createView($options);

    $display_options = $view->getDisplay('default');
    $display_options['fields']['title']['id'] = 'title';
    $display_options['fields']['title']['table'] = 'node_field_data';
    $display_options['fields']['title']['field'] = 'title';
    $display_options['fields']['title']['entity_type'] = 'node';
    $display_options['fields']['title']['entity_field'] = 'title';
    $display_options['fields']['title']['label'] = '';
    $display_options['fields']['title']['alter']['alter_text'] = 0;
    $display_options['fields']['title']['alter']['make_link'] = 0;
    $display_options['fields']['title']['alter']['absolute'] = 0;
    $display_options['fields']['title']['alter']['trim'] = 0;
    $display_options['fields']['title']['alter']['word_boundary'] = 0;
    $display_options['fields']['title']['alter']['ellipsis'] = 0;
    $display_options['fields']['title']['alter']['strip_tags'] = 0;
    $display_options['fields']['title']['alter']['html'] = 0;
    $display_options['fields']['title']['hide_empty'] = 0;
    $display_options['fields']['title']['empty_zero'] = 0;
    $display_options['fields']['title']['settings']['link_to_entity'] = 1;
    $display_options['fields']['title']['plugin_id'] = 'field';

    // $executable = $view->getExecutable();
    // Display: Master
    // $default_display = $executable->newDisplay('default', 'Master',
    // 'default');
    $view->addDisplay('page');

    /*
    foreach ($display_options['default'] as $option => $value) {
    $master->setOption($option, $value);
    }
     */

    // $executable->save();
    return $view;

  }

}
