<?php

/**
 * @file
 * Theme for IIED Slider views.
 */
function template_preprocess_views_view_iied_slider(&$variables) {
  // View options set by user.
  $options = $variables['view']->style_plugin->options;

  // Build a two-dimension array with years and months.
  $time_pool = array();

  foreach ($variables['view']->result as $id => $result) {
    $created = $result->node_field_data_created;
    $created_year = date('Y', $created);
    // Month date format.
    $month_date_format = (isset($options['month_date_format'])) ? $options['month_date_format'] : 'm';
    $created_month_digits = date('m', $created);
    $created_month = date($month_date_format, $created);
    $time_pool[$created_year][$created_month_digits] = "$created_month";
  }

  $options['time_pool'] = $time_pool;

  // Update options for twig.
  $variables['options'] = $options;

  $view = $variables['view'];
  $rows = $variables['rows'];
  $style = $view->style_plugin;
  $options = $style->options;

  $variables['default_row_class'] = !empty($options['default_row_class']);
  foreach ($rows as $id => $row) {
    $variables['rows'][$id] = [];
    $variables['rows'][$id]['content'] = $row;
    $variables['rows'][$id]['attributes'] = new Attribute();
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['rows'][$id]['attributes']->addClass($row_class);
    }
  }
}
