<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Class LayoutParagraphsBuilderForm.
 *
 * Builds a Layout Paragraphs Builder form with save / cancel buttons
 * for saving the host entity.
 */
class LayoutParagraphsBuilderForm extends FormBase {

  /**
   * A layout paragraphs layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layoutParagraphsLayout;

  /**
   * The layout paragraphs layout tempstore service.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'layout_paragraphs_builder_form';
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(LayoutParagraphsLayoutTempstoreRepository $tempstore, EntityTypeManagerInterface $entity_type_manager) {
    $this->tempstore = $tempstore;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the layout paragraphs builder form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity that contains a layout.
   * @param string $field_name
   *   The name of the layout paragraphs field.
   * @param string $view_mode
   *   The view mode.
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ContentEntityInterface $entity = NULL,
    string $field_name = NULL,
    string $view_mode = NULL) {

    $parents = array_merge($form['#parents'] ?? [], ['layout_paragraphs_storage_key']);
    $input = $form_state->getUserInput();
    $layout_paragraphs_storage_key = NestedArray::getValue($input, $parents);

    // If the form is being rendered for the first time, save the Layout
    // Paragraphs Layout instance to tempstore and store the key.
    if (empty($layout_paragraphs_storage_key)) {
      $render_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      $renderer = $render_display->getRenderer($field_name);
      $layout_paragraphs_settings = $renderer->getSettings() + ['reference_field_view_mode' => $view_mode];
      $this->layoutParagraphsLayout = new LayoutParagraphsLayout($entity->{$field_name}, $layout_paragraphs_settings);
      $this->tempstore->set($this->layoutParagraphsLayout);
      $layout_paragraphs_storage_key = $this->tempstore->getStorageKey($this->layoutParagraphsLayout);
    }
    // On subsequent form renders, this loads the correct Layout Paragraphs
    // Layout from the tempstore using the storage key.
    else {
      $this->layoutParagraphsLayout = $this->tempstore->getWithStorageKey($layout_paragraphs_storage_key);
    }

    $form['layout_paragraphs_builder_ui'] = [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
    ];
    $form['layout_paragraphs_storage_key'] = [
      '#type' => 'hidden',
      '#default_value' => $layout_paragraphs_storage_key,
    ];
    $form['#attributes']['data-lpb-form-id'] = Html::getId($this->layoutParagraphsLayout->id());
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#ajax' => [
          'callback' => '::save',
        ],
        '#attributes' => [
          'class' => ['button--primary'],
        ],
      ],
      'close' => [
        '#type' => 'button',
        '#value' => $this->t('Close'),
        '#ajax' => [
          'callback' => '::close',
        ],
        '#attributes' => [
          'class' => ['lpb-btn--cancel'],
        ],
      ],
    ];
    $form['actions']['#attributes']['class'][] = 'lpb-form__actions';

    return $form;
  }

  /**
   * Ajax callback.
   *
   * Closes the builder and returns the rendered layout.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax command.
   */
  public function close(array $form, FormStateInterface $form_state) {
    $this->tempstore->delete($this->layoutParagraphsLayout);
    $view_mode = $this->layoutParagraphsLayout->getSetting('reference_field_view_mode', 'default');
    $rendered_layout = $this->layoutParagraphsLayout->getParagraphsReferenceField()->view($view_mode);
    $response = new AjaxResponse();
    $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, '', 'builder:close'));
    $response->addCommand(new ReplaceCommand('[data-lpb-form-id="' . $form['#attributes']['data-lpb-form-id'] . '"]', $rendered_layout));
    return $response;
  }

  /**
   * Ajax callback.
   *
   * Displays a message when the entity is saved.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax command.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->layoutParagraphsLayout->getEntity();

    $response = new AjaxResponse();
    $t_args = [
      '@type' => $entity->getEntityType()->getLabel(),
      '%title' => $entity->label(),
    ];
    $response->addCommand(new MessageCommand($this->t('@type %title has been updated.', $t_args)));
    $response->addCommand(new ReplaceCommand('[data-lpb-form-id="' . $form['#attributes']['data-lpb-form-id'] . '"]', $form));
    $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, '', 'builder:save'));
    return $response;
  }

  /**
   * {@inheritDoc}
   *
   * Saves the layout to its parent entity.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The entity may have been altered by another process, and needs to be
    // loaded from storage to ensure edits to other fields are not overwritten.
    // @see https://www.drupal.org/project/layout_paragraphs/issues/3275179
    $entity = $this->layoutParagraphsLayout->getEntity();
    $entity_id = $entity->id();
    $entity_type = $entity->getEntityTypeId();
    $field_name = $this->layoutParagraphsLayout->getFieldName();
    if ($entity instanceof RevisionableInterface) {
      $revision_id = $entity->getRevisionId();
      /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type)->loadRevision($revision_id);
      $entity->setNewRevision(FALSE);
    }
    else {
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    }
    if ($entity instanceof EntityChangedInterface) {
      $entity->setChangedTime(time());
    }
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime(time());
      $entity->setRevisionLogMessage($this->t('Updated with the Layout Paragraphs Frontend Builder.'));
    }
    $entity->$field_name = $this->layoutParagraphsLayout->getParagraphsReferenceField();
    $entity->save();
    $this->layoutParagraphsLayout->setParagraphsReferenceField($entity->$field_name);
    $this->tempstore->set($this->layoutParagraphsLayout);
  }

}
