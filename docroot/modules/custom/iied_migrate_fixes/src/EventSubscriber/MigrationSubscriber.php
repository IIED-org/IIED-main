<?php

namespace Drupal\iied_migrate_fixes\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

    $event->logMessage('We got this far.');
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

    $ids = $event->getDestinationIdValues();
    $updated_file_id =  $ids [0];


    $connection = \Drupal\Core\Database\Database::getConnection();
    $num_updated = $connection->update('media__field_media_document')
    ->fields([
      'field_media_document_target_id' => $updated_file_id,
    ])
    ->condition('entity_id', $original_media_entity_id )
    ->execute();


    $migration_id = $event->getMigration()->getBaseId();
  }

}
