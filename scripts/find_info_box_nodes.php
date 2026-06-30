<?php
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$field_name = 'field_paragraphs'; // Machine name of the paragraph field

$nids = $node_storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('status', 1) // Published nodes only - remove if you want all
  ->exists($field_name)
  ->execute();

$node_ids_with_info_box = [];

$nodes = $node_storage->loadMultiple($nids);

foreach ($nodes as $node) {
  $paragraphs = $node->get($field_name)->referencedEntities();
  if (!empty($paragraphs)) {
    foreach ($paragraphs as $paragraph) {
      if ($paragraph->bundle() === 'info_box') {
        $node_ids_with_info_box[] = $node->id();
        break; // Only need to add the node ID once
      }
    }
  }
}

// Output the results
\Drupal::logger('my_module')->notice('Node IDs using info_box paragraph: @ids', [
  '@ids' => implode(', ', $node_ids_with_info_box),
]);
