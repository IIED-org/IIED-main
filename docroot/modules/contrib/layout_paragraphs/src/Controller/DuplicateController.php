<?php

namespace Drupal\layout_paragraphs\Controller;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutRefreshTrait;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Class DuplicateController.
 *
 * Duplicates a component of a Layout Paragraphs Layout.
 */
class DuplicateController extends ControllerBase {

  use LayoutParagraphsLayoutRefreshTrait;
  use AjaxHelperTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * {@inheritDoc}
   */
  public function __construct(LayoutParagraphsLayoutTempstoreRepository $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository')
    );
  }

  /**
   * Duplicates a component and returns appropriate response.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   * @param string $source_uuid
   *   The source component to be cloned.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   A build array or Ajax respone.
   */
  public function duplicate(LayoutParagraphsLayout $layout_paragraphs_layout, string $source_uuid) {
    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $duplicate_component = $this->layoutParagraphsLayout->duplicateComponent($source_uuid);
    $this->tempstore->set($this->layoutParagraphsLayout);

    if ($this->isAjax()) {
      $response = new AjaxResponse();
      if ($this->needsRefresh()) {
        return $this->refreshLayout($response);
      }
      $uuid = $duplicate_component->getEntity()->uuid();
      $rendered_item = [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#uuid' => $uuid,
      ];
      $response->addCommand(new AfterCommand('[data-uuid="' . $source_uuid . '"]', $rendered_item));
      $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $uuid, 'component:insert'));
      return $response;
    }
    return [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $layout_paragraphs_layout,
    ];

  }

}
