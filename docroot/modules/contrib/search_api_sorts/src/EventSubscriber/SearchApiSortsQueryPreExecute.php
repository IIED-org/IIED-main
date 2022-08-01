<?php

namespace Drupal\search_api_sorts\EventSubscriber;

use Drupal\search_api\Display\DisplayInterface;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api_sorts\SearchApiSortsManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class that adds the sorting logic to the search api query.
 */
class SearchApiSortsQueryPreExecute implements EventSubscriberInterface {

  /**
   * The search api sorts manager.
   *
   * @var \Drupal\search_api_sorts\SearchApiSortsManagerInterface
   */
  protected $searchApiSortsManager;

  /**
   * SearchApiSortsQueryAlter constructor.
   *
   * @param \Drupal\search_api_sorts\SearchApiSortsManagerInterface $search_api_sorts_manager
   *   The search api sorts manager.
   */
  public function __construct(SearchApiSortsManagerInterface $search_api_sorts_manager) {
    $this->searchApiSortsManager = $search_api_sorts_manager;
  }

  /**
   * Alter the search api query and add our sorting.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The search api sorts manager.
   */
  public function onQueryPreExecute(QueryPreExecuteEvent $event) {
    $display = $event->getQuery()->getDisplayPlugin();

    if (!$display instanceof DisplayInterface) {
      // Display for current search page not implemented. To fix this, implement
      // the search api display plugin. See ViewsPageDisplay.php for an example.
      return;
    }

    $active_sort = $this->searchApiSortsManager->getActiveSort($display);
    $field = $active_sort->getFieldName();
    $order = $active_sort->getOrder();
    if ($field === NULL) {
      // If no field provided, use default field and default order.
      $default_sort = $this->searchApiSortsManager->getDefaultSort($display);
      $field = $default_sort->getFieldName();
      $order = $default_sort->getOrder();
    }

    $event->getQuery()->sort($field, $order);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SearchApiEvents::QUERY_PRE_EXECUTE][] = ['onQueryPreExecute'];
    return $events;
  }

}
