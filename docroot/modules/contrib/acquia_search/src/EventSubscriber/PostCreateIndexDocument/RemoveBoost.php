<?php

namespace Drupal\acquia_search\EventSubscriber\PostCreateIndexDocument;

use Drupal\search_api_solr\Event\PostCreateIndexDocumentEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes the Boost setting if the default is set to 1.0 on Acquia Search.
 */
class RemoveBoost implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // phpcs:ignore
    // @todo Remove when support for Solr 4.2.1 is no longer supported.
    if (class_exists('Drupal\search_api_solr\Event\SearchApiSolrEvents')) {
      return [
        SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENT => 'postDocumentIndex',
      ];
    }
    return [];
  }

  /**
   * Add EdisMax for Acquia Solr Queries.
   *
   * @param \Drupal\search_api_solr\Event\PostCreateIndexDocumentEvent $event
   *   The dispatched event.
   */
  public function postDocumentIndex(PostCreateIndexDocumentEvent $event) {
    $doc = $event->getSolariumDocument();

    // Acquia Search defaults boost to 1.0 already, remove it from the payload.
    $doc_fields = $doc->getFields();
    if (isset($doc_fields['boost_document']) && $doc_fields['boost_document'] === 1.0) {
      $doc->removeField('boost_document');
    }
  }

}
