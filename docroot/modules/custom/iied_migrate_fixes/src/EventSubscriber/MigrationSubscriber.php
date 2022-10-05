<?php

namespace Drupal\iied_migrate_fixes\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Path\AliasManager;

/**
 * Class MigrationSubscriber.
 *
 * Cleans up file references.
 *
 * @package Drupal\iied_migrate_fixes
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_IMPORT][] = ['onMigratePreImport'];
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onMigratePostRowSave'];

    return $events;
  }

  /**
   * Check for the image server status just once to avoid thousands of requests.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePreImport(MigrateImportEvent $event) {
    $migration_id = $event->getMigration()->getBaseId();
  }



  /**
   * Check for our specified last node migration and run our flagging mechanisms.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    $migration_id = $event->getMigration()->getBaseId();

  }

  /**
   * Check for our specified last node migration and run our flagging mechanisms.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostRowSave(MigratePostRowSaveEvent $event) {

    $row = $event->getRow();
    $original_media_entity_id = $row->getSourceProperty('entity_id');
    $original_media_entity_uri = $row->getSourceProperty('uri');
    $ids = $event->getDestinationIdValues();
    $updated_file_id =  $ids [0];


    $connection = \Drupal\Core\Database\Database::getConnection();
    $num_updated = $connection->update('media__field_media_document')
    ->fields([
      'field_media_document_target_id' => $updated_file_id,
    ])
    ->condition('entity_id', $original_media_entity_id )
    ->execute();

    // Look up the original node or entity:
    $query = $connection->select('node__field_media', 'nfm');
    $query->fields('nfm');
    $query->condition('field_media_target_id', $original_media_entity_id);
    $results = $query->execute();
    $node_path = '[undefined]';
    $alias = '[undefined]';
    foreach ($results as $result) {
      $node_path = '/node/' . $result->entity_id;
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $result->entity_id);
    }

    // Log a message.
    $event->logMessage("$num_updated media entity updated. Entity ID: $original_media_entity_id URI: $original_media_entity_uri. The node that references this media entity is $node_path or $alias .");
  }

}
