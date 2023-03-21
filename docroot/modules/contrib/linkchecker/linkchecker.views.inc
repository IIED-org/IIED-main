<?php

/**
 * @file
 * Contains Views integration for the linkchecker module.
 */

/**
 * Implements hook_views_data().
 */
function linkchecker_views_data_alter(array &$data) {
  $data['linkchecker_link']['linkcheckerlink_page_entity_label'] = [
    'field' => [
      'title' => t('Page entity label'),
      'help' => t('The label of the entity that is related to the file.'),
      'id' => 'linkcheckerlink_page_entity_label',
    ],
  ];
}
