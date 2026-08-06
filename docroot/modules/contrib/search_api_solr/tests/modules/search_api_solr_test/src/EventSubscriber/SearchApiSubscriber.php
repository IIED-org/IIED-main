<?php

<<<<<<< HEAD
// phpcs:ignoreFile SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
namespace Drupal\search_api_solr_test\EventSubscriber;

use Drupal\search_api_solr\Event\PostConfigFilesGenerationEvent;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
<<<<<<< HEAD
use Solarium\QueryType\Update\Query\Document;
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Search API Solr events subscriber.
 */
class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SearchApiSolrEvents::POST_CONFIG_FILES_GENERATION][] = ['postConfigFilesGeneration'];
    $events[SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENTS][] = ['postCreateIndexDocuments'];

    return $events;
  }

<<<<<<< HEAD
  /**
   * Adds a test config file after config generation.
   *
   * @param \Drupal\search_api_solr\Event\PostConfigFilesGenerationEvent $event
   *   The dispatched event.
   */
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
  public function postConfigFilesGeneration(PostConfigFilesGenerationEvent $event): void {
    $files = $event->getConfigFiles();

    $files['test.txt'] =
      "hook_search_api_solr_config_files_alter() works\n" .
      $event->getServerId() . "\n";

    $event->setConfigFiles($files);
  }

<<<<<<< HEAD
  /**
   * Alters generated index documents for fallback test coverage.
   *
   * @param \Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent $event
   *   The dispatched event.
   */
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
  public function postCreateIndexDocuments(PostCreateIndexDocumentsEvent $event): void {
    global $_search_api_solr_test_index_fallback_test;

    if ($_search_api_solr_test_index_fallback_test) {
      $documents = $event->getSolariumDocuments();
      foreach ($documents as $document) {
<<<<<<< HEAD
        assert($document instanceof Document);
        $fields = $document->getFields();
        if (
          'entity:entity_test_mulrev_changed/2:en' ===
          ($fields['ss_search_api_id'] ?? NULL)
        ) {
          // Send a string as value of a float field for the fallback test.
=======
        if ('entity:entity_test_mulrev_changed/2:en' === $document->ss_search_api_id) {
          // Mess up this document by sending a string as value of a float field.
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
          $document->setField('fts_width', 'bar');
        }
      }
      $event->setSolariumDocuments($documents);
    }
  }

}
