<?php

namespace Drupal\search_api_sorts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\search_api\Display\DisplayPluginManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_sorts\ConfigIdEscapeTrait;
use Drupal\search_api_sorts\Entity\SearchApiSortsField;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AdminController.
 *
 * @package Drupal\search_api_sorts\Controller
 */
class AdminController extends ControllerBase {

  use ConfigIdEscapeTrait;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * The Search API display manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManagerInterface
   */
  protected $searchApiDisplayManager;

  /**
   * AdminController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\search_api\Display\DisplayPluginManagerInterface $search_api_display_manager
   *   The Search API display manager.
   */
  public function __construct(RequestStack $requestStack, DisplayPluginManagerInterface $search_api_display_manager) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->searchApiDisplayManager = $search_api_display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('plugin.manager.search_api.display')
    );
  }

  /**
   * Overview of search api displays to choose to manage sort fields for.
   */
  public function displayListing(IndexInterface $search_api_index) {

    $rows = [];
    foreach ($this->searchApiDisplayManager->getInstances() as $display) {
      if ($display->getIndex() instanceof IndexInterface && $search_api_index->id() === $display->getIndex()->id()) {
        $row = [];
        $row['display'] = $display->label();
        $row['description'] = $display->getDescription();
        $search_api_display = $display->getPluginId();
        $escaped_search_api_display = $this->getEscapedConfigId($search_api_display);

        $links['configure'] = [
          'title' => $this->t('Manage sort fields'),
          'url' => Url::fromRoute('search_api_sorts.search_api_display.sorts', [
            'search_api_index' => $search_api_index->id(),
            'search_api_display' => $escaped_search_api_display,
          ]),
        ];

        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => $links,
        ];
        $rows[] = $row;
      }
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Display'), $this->t('Description'), $this->t('Operations')],
      '#title' => $this->t('Sorts configuration.'),
      '#rows' => $rows,
      '#empty' => $this->t('You have no search displays defined yet. An example of a display is a views page using this index, or a search api pages page.'),
    ];

    return $build;
  }

  /**
   * Callback for search_api_sorts_field edit form.
   *
   * Redirect the user to the ManageSortsFieldsForm.
   *
   * @see \Drupal\search_api_sorts\Form\ManageSortFieldsForm
   */
  public function redirectEditForm(SearchApiSortsField $search_api_sorts_field) {
    // When accessing this page from the config_translation overview,
    // a destination parameter is added, which prevents the redirect added in
    // this route method.
    $this->currentRequest->query->remove('destination');

    $display_id = $this->getOriginalConfigId($search_api_sorts_field->getDisplayId());
    /** @var \Drupal\search_api\Display\DisplayInterface $display */
    $display = $this->searchApiDisplayManager->createInstance($display_id);

    return $this->redirect('search_api_sorts.search_api_display.sorts', [
      'search_api_index' => $display->getIndex()->id(),
      'search_api_display' => $search_api_sorts_field->getDisplayId(),
    ]);
  }

}
