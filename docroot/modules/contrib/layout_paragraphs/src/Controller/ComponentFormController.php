<?php

namespace Drupal\layout_paragraphs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_paragraphs\Utility\Dialog;
use Symfony\Component\HttpFoundation\Request;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsComponentDefaultsEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class definition for ComponentFormController.
 */
class ComponentFormController extends ControllerBase {

  use AjaxHelperTrait;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Construct a Component Form Controller.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher_service
   *   The event dispatcher service.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher_service) {
    $this->eventDispatcher = $event_dispatcher_service;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Responds with a component insert form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   * @param string $paragraph_type_id
   *   The paragraph type id.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   A build array or Ajax respone.
   */
  public function insertForm(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout, string $paragraph_type_id) {

    $parent_uuid = $request->query->get('parent_uuid');
    $region = $request->query->get('region');
    $sibling_uuid = $request->query->get('sibling_uuid');
    $placement = $request->query->get('placement');

    // Dispatch a LayoutParagraphsComponentDefaultsEvent to allow other modules
    // to alter the paragraph type and default values.
    $event = new LayoutParagraphsComponentDefaultsEvent($paragraph_type_id, []);
    $this->eventDispatcher->dispatch($event, $event::EVENT_NAME);
    $paragraph_type = $this->entityTypeManager()
      ->getStorage('paragraphs_type')
      ->load($event->getParagraphTypeId());

    $form = $this->formBuilder()->getForm(
      $this->getInsertComponentFormClass(),
      $layout_paragraphs_layout,
      $paragraph_type,
      $parent_uuid,
      $region,
      $sibling_uuid,
      $placement,
      $event->getDefaultValues());

    return $this->openForm($form, $layout_paragraphs_layout);
  }

  /**
   * Returns the form, with ajax if appropriate.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   *
   * @return array|AjaxResponse
   *   The form or ajax response.
   */
  protected function openForm(array $form, LayoutParagraphsLayout $layout_paragraphs_layout) {
    if ($this->isAjax()) {
      $response = new AjaxResponse();
      $selector = Dialog::dialogSelector($layout_paragraphs_layout);
      $response->addCommand(new OpenDialogCommand($selector, $form['#title'], $form, Dialog::dialogSettings()));
      return $response;
    }
    return $form;
  }

  /**
   * Returns the insert component form class.
   */
  protected function getInsertComponentFormClass() {
    return '\Drupal\layout_paragraphs\Form\InsertComponentForm';
  }

}
