<?php

namespace Drupal\search_api_sorts;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\Display\DisplayInterface;
use Drupal\search_api\Display\DisplayPluginManagerInterface;
use Drupal\search_api\IndexInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages search api sorts.
 */
class SearchApiSortsManager implements SearchApiSortsManagerInterface {
  use ConfigIdEscapeTrait;

  /**
   * Current Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The search api sorts field storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $searchApiSortsFieldStorage;

  /**
   * The search api display manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManagerInterface
   */
  protected $searchApiDisplayManager;

  /**
   * SearchApiSortsManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack, containing the current request.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\search_api\Display\DisplayPluginManagerInterface $searchApiDisplayManager
   *   The search api display manager.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, DisplayPluginManagerInterface $searchApiDisplayManager) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->searchApiSortsFieldStorage = $entity_type_manager->getStorage('search_api_sorts_field');
    $this->moduleHandler = $module_handler;
    $this->searchApiDisplayManager = $searchApiDisplayManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveSort(DisplayInterface $display) {
    $order = (strtolower($this->currentRequest->get('order', '')) === 'desc') ? 'desc' : 'asc';
    $active_sort = new SortsField($this->currentRequest->get('sort'), $order);

    // Allow altering the active sort (if there is an active sort).
    if ($active_sort->getFieldName()) {
      $this->moduleHandler->alter('search_api_sorts_active_sort', $active_sort, $display);
    }
    return $active_sort;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledSorts(DisplayInterface $display) {
    return $this->searchApiSortsFieldStorage->loadByProperties([
      'status' => TRUE,
      'display_id' => $this->getEscapedConfigId($display->getPluginId()),
    ]);
  }

  /**
   * Returns all sort fields for a given search api display.
   *
   * @param \Drupal\search_api\Display\DisplayInterface $display
   *   The display where the sorts should be returned for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array containing sort fields for the given search display/
   */
  protected function getSorts(DisplayInterface $display) {
    return $this->searchApiSortsFieldStorage->loadByProperties([
      'display_id' => $this->getEscapedConfigId($display->getPluginId()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSort(DisplayInterface $display) {

    // By default use relevance, which will be overridden when defaults are set.
    $default_sort = new SortsField('search_api_relevance', 'desc');

    foreach ($this->getEnabledSorts($display) as $enabled_sort) {
      if ($enabled_sort->getDefaultSort()) {
        $default_sort = new SortsField($enabled_sort->getFieldIdentifier(), $enabled_sort->getDefaultOrder());
      }
    }

    // Allow altering the default sort.
    $this->moduleHandler->alter('search_api_sorts_default_sort', $default_sort, $display);

    return $default_sort;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupSortFields(IndexInterface $index) {
    foreach ($this->searchApiDisplayManager->getInstances() as $display) {
      if ($display->getIndex() instanceof IndexInterface && $index->id() === $display->getIndex()->id()) {
        foreach ($this->getSorts($display) as $search_api_sorts_field) {
          // Dummy field therefore the index has no field.
          if ($search_api_sorts_field->getFieldIdentifier() === 'search_api_relevance') {
            continue;
          }
          $field = $index->getField($search_api_sorts_field->getFieldIdentifier());
          if ($field === NULL) {
            $search_api_sorts_field->delete();
          }
        }
      }
    }
  }

}
