<?php

/**
 * @file
 * Contains views configurations.
 */

/**
 * Implements hook_views_data_alter().
 */
function publishcontent_views_data_alter(array &$data) {
  $data['node']['publishcontent'] = [
    'title' => t('Publish/Unpublish'),
    'field' => [
      'title' => t('Publish/Unpublish'),
      'help' => t('Link to Publish or Unpublish node.'),
      'id' => 'publishcontent_node',
    ],
  ];
}
