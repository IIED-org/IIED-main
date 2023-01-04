<?php

/**
 * @file
 * Views related hooks for views_json_source.
 */

/**
 * Implements hook_views_data().
 */
function views_json_source_views_data() {
  $data = [];
  $data['json']['table']['group'] = t('JSON');

  $data['json']['table']['base'] = [
    'title' => t('JSON'),
    'help' => t('Queries an JSON.'),
    'query_id' => 'views_json_source_query',
  ];

  $data['json']['value'] = [
    'title' => t('JSON Field'),
    'help' => t('Name of the key in the JSON.'),
    'field' => [
      'id' => 'views_json_source_field',
    ],
    'sort' => [
      'id' => 'views_json_source_sort',
    ],
    'filter' => [
      'id' => 'views_json_source_filter',
    ],
    'argument' => [
      'id' => 'views_json_source_argument',
    ],
  ];

  // Contextual filter required to replace the "%" placeholder in the apath.
  $data['json']['parameter'] = [
    'title' => t('Apath Replacement.'),
    'help' => t('Replacement for placeholder text in apath.'),
    'argument' => [
      'id' => 'views_json_source_parameter',
    ],
  ];

  // Contextual filter required to replace the "%" placeholder in the url.
  $data['json']['param'] = [
    'title' => t('URL Parameter.'),
    'help' => t('Replacement for placeholder in URL.'),
    'argument' => [
      'id' => 'views_json_source_uri_param',
    ],
  ];

  return $data;
}
