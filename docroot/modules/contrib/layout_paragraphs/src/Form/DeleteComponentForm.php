<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\Utility\Dialog;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutRefreshTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for confirming deletion of a Layout Paragraphs component.
 */
class DeleteComponentForm extends FormBase {

  use LayoutParagraphsLayoutRefreshTrait;

  /**
   * The layout paragraphs layout tempstore.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * The uuid of the component to delete.
   *
   * @var string
   */
  protected $componentUuid;

  /**
   * {@inheritDoc}
   */
  protected function __construct($tempstore) {
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
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'layout_paragraphs_delete_component_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, LayoutParagraphsLayout $layout_paragraphs_layout = NULL, string $component_uuid = NULL) {
    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $this->componentUuid = $component_uuid;
    $component = $this->layoutParagraphsLayout->getComponentByUuid($this->componentUuid);
    $type = $component->getEntity()->getParagraphType()->label();
    $form['#title'] = $this->t('Delete component', ['@type' => $type]);
    $form['confirm'] = [
      '#markup' => $this->t('Really delete this "@type" component? There is no undo.', ['@type' => $type]),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#ajax' => [
          'callback' => '::deleteComponent',
        ],
        '#attributes' => [
          'class' => ['lpb-btn--confirm-delete'],
          'data-disable-refocus' => 'true',
        ],
      ],
      'cancel' => [
        '#type' => 'button',
        '#value' => $this->t('Cancel'),
        '#ajax' => [
          'callback' => '::closeForm',
        ],
        '#attributes' => [
          'class' => [
            'dialog-cancel',
            'lpb-btn--cancel',
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   *
   * Deletes the component and saves the layout.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->layoutParagraphsLayout->deleteComponent($this->componentUuid, TRUE);
    $this->tempstore->set($this->layoutParagraphsLayout);
  }

  /**
   * Ajax callback - deletes component and closes the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function deleteComponent(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand(Dialog::dialogSelector($this->layoutParagraphsLayout)));
    if ($this->needsRefresh()) {
      return $this->refreshLayout($response);
    }
    $response->addCommand(new RemoveCommand('[data-uuid="' . $this->componentUuid . '"]'));
    $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $this->componentUuid, 'component:delete'));
    return $response;
  }

  /**
   * Ajax callback - closes the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function closeForm(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(Dialog::closeDialogCommand($this->layoutParagraphsLayout));
    return $response;
  }

}
