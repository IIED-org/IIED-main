<?php
/**
 * @file
 * Administration settings form.
 */

/**
 * Callback for admin/config/taxonomy_block;
 */ 
function taxonomy_block_admin_settings() {
  $form = array();
  
  $vocabs = array();
  foreach(taxonomy_vocabulary_get_names() as $vocab) {
    $vocabs[$vocab->vid] = $vocab->name;
  }

  $form['taxonomy_block_vid'] = array(
    '#type'           => 'select',
    '#title'          => t('Select a Vocabulary'),
    '#default_value'  => variable_get('taxonomy_block_vid', 0),
    '#description'    => t('Select Vocabulary for used by taxonomy block.'),
    '#options'        => $vocabs
  );  
  
  $form['taxonomy_block_node_count'] = array(
    '#type'           => 'checkbox',
    '#title'          => t('Show node count'),
    '#default_value'  => variable_get('taxonomy_block_node_count', 0)
  );    
  
  return system_settings_form($form);
}