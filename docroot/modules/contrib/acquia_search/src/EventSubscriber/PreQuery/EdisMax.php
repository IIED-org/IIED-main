<?php

namespace Drupal\acquia_search\EventSubscriber\PreQuery;

use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Drupal\search_api_solr\SolrBackendInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters the EdisMax query on Acquia Search.
 */
class EdisMax implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // phpcs:ignore
    // @todo Remove when support for Solr 4.2.1 is no longer supported.
    if (class_exists('Drupal\search_api_solr\Event\SearchApiSolrEvents')) {
      return [
        SearchApiSolrEvents::PRE_QUERY => 'preQuery',
      ];
    }
    return [];
  }

  /**
   * Add EdisMax for Acquia Solr Queries.
   *
   * @param \Drupal\search_api_solr\Event\PreQueryEvent $event
   *   The dispatched event.
   */
  public function preQuery(PreQueryEvent $event) {
    $solarium_query = $event->getSolariumQuery();
    $handler = $solarium_query->getHandler();
    if ($handler !== 'select') {
      return;
    }

    $search_index = $event->getSearchApiQuery()->getIndex();
    try {
      $server = $search_index->getServerInstance();
      if ($server === NULL) {
        return;
      }
      $backend = $server->getBackend();
      if (!$backend instanceof SolrBackendInterface) {
        return;
      }
      $connector = $backend->getSolrConnector();
      if (!$connector instanceof SearchApiSolrAcquiaConnector) {
        return;
      }
    }
    catch (SearchApiException $exception) {
      return;
    }

    $use_edismax = $search_index->getThirdPartySetting(
      'acquia_search',
      'use_edismax',
      FALSE
    );
    if ($use_edismax) {
      $solarium_query->addParam('defType', 'edismax');
    }
  }

}
