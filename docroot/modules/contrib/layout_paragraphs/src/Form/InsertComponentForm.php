<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;

/**
 * Class InsertComponentForm.
 *
 * Builds the form for inserting a new component.
 */
class InsertComponentForm extends ComponentFormBase {

  /**
   * DOM element selector.
   *
   * @var string
   */
  protected $domSelector;

  /**
   * The jQuery insertion method to use for adding the new component.
   *
   * Must be "before", "after", "prepend", or "append.".
   *
   * @var string
   */
  protected $method = 'prepend';

  /**
   * The uuid of the parent component / paragraph.
   *
   * @var string
   */
  protected $parentUuid;

  /**
   * The region this component will be inserted into.
   *
   * @var string
   */
  protected $region;

  /**
   * Where to place the new component in relation to sibling.
   *
   * @var string
   *   "before" or "after"
   */
  protected $placement;

  /**
   * The sibling component's uuid.
   *
   * @var string
   *   The sibling component's uuid.
   */
  protected $siblingUuid;

  /**
   * {@inheritDoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type
   *   The paragraph type.
   * @param string $parent_uuid
   *   The parent component's uuid.
   * @param string $region
   *   The region to insert the new component into.
   * @param string $sibling_uuid
   *   The uuid of the sibling component.
   * @param string $placement
   *   Where to place the new component - either "before" or "after".
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    LayoutParagraphsLayout $layout_paragraphs_layout = NULL,
    ParagraphsTypeInterface $paragraph_type = NULL,
    string $parent_uuid = NULL,
    string $region = NULL,
    string $sibling_uuid = NULL,
    string $placement = NULL
    ) {

    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $langcode = $this->layoutParagraphsLayout->getEntity()->language()->getId();
    $this->paragraph = $this->newParagraph($paragraph_type, $langcode);

    $this->parentUuid = $parent_uuid;
    $this->region = $region;
    $this->siblingUuid = $sibling_uuid;
    $this->placement = $placement;

    if ($this->siblingUuid && $this->placement) {
      $this->domSelector = '[data-uuid="' . $sibling_uuid . '"]';
      $this->method = $placement;
    }
    elseif ($this->parentUuid && $this->region) {
      $this->domSelector = '[data-region-uuid="' . $parent_uuid . '-' . $region . '"]';
    }
    else {
      $this->domSelector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
    }

    $form_display_mode = $this->layoutParagraphsLayout->getSetting('form_display_mode');
    return $this->buildComponentForm($form, $form_state, $form_display_mode);
  }

  /**
   * Create the form title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form title.
   */
  protected function formTitle() {
    return $this->t('Create new @type', ['@type' => $this->paragraph->getParagraphType()->label()]);
  }

  /**
   * {@inheritDoc}
   */
  public function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $this->ajaxCloseForm($response);
    if ($this->needsRefresh()) {
      return $this->refreshLayout($response);
    }

    $uuid = $this->paragraph->uuid();
    $rendered_item = $this->renderParagraph($uuid);

    switch ($this->method) {
      case 'before':
        $response->addCommand(new BeforeCommand($this->domSelector, $rendered_item));
        break;

      case 'after':
        $response->addCommand(new AfterCommand($this->domSelector, $rendered_item));
        break;

      case 'append':
        $response->addCommand(new AppendCommand($this->domSelector, $rendered_item));
        break;

      case 'prepend':
        $response->addCommand(new PrependCommand($this->domSelector, $rendered_item));
        break;
    }

    $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $uuid, 'component:insert'));
    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->insertComponent();
    $this->tempstore->set($this->layoutParagraphsLayout);
  }

  /**
   * Inserts the new component into the layout.
   *
   * Determines the correct method based on provided values.
   *
   * - If parent uuid and region are provided, new component
   *   is added to the specified region within the specified parent.
   * - If sibling uuid and placement are provided, the new component
   *   is added before or after the existing sibling.
   * - If no parameters are added, the new component is simply added
   *   to the layout at the root level.
   *
   * @return $this
   */
  public function insertComponent() {
    if ($this->siblingUuid && $this->placement) {
      switch ($this->placement) {
        case 'before':
          $this->layoutParagraphsLayout->insertBeforeComponent($this->siblingUuid, $this->paragraph);
          break;

        case 'after':
          $this->layoutParagraphsLayout->insertAfterComponent($this->siblingUuid, $this->paragraph);
          break;
      }
    }
    elseif ($this->parentUuid && $this->region) {
      $this->layoutParagraphsLayout->insertIntoRegion($this->parentUuid, $this->region, $this->paragraph);
    }
    else {
      $this->layoutParagraphsLayout->appendComponent($this->paragraph);
    }
    return $this;
  }

  /**
   * Creates a new, empty paragraph empty of the provided type.
   *
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type
   *   The paragraph type.
   * @param string $langcode
   *   The language code for the new paragraph.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The new paragraph.
   */
  protected function newParagraph(ParagraphsTypeInterface $paragraph_type, string $langcode) {
    $entity_type = $this->entityTypeManager->getDefinition('paragraph');
    $langcode_key = $entity_type->getKey('langcode');
    $bundle_key = $entity_type->getKey('bundle');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph_entity */
    $paragraph = $this->entityTypeManager->getStorage('paragraph')
      ->create([
        $bundle_key => $paragraph_type->id(),
        $langcode_key => $langcode,
        '_layoutParagraphsLayout' => $this->getLayoutParagraphsLayout(),
      ]);
    return $paragraph;
  }

}
