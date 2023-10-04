<?php

namespace Drupal\layout_paragraphs\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Class ReorderController.
 *
 * Reorders the components of a Layout Paragraphs Layout.
 */
class ReorderController extends ControllerBase {

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
   * Reorders a Layout Paragraphs Layout's components.
   *
   * Expects an two-dimmensional array of components in the "components" POST
   * parameter with key/value pairs for "uuid", "parent_uuid", and "region".
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing a "components" POST parameter.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The Layout Paragraphs Layout object.
   */
  public function build(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout) {
    if ($ordered_components = Json::decode($request->request->get("components"))) {
      $layout_paragraphs_layout->reorderComponents($ordered_components);
      $this->tempstore->set($layout_paragraphs_layout);
    }
    // If invoked via ajax, no need to re-render the builder UI.
    if ($this->isAjax()) {
      return new AjaxResponse();
    }
    return [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $layout_paragraphs_layout,
    ];
  }

}
