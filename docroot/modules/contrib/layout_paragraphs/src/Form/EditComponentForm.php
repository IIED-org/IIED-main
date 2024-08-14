<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsEventCommand;

/**
 * Class LayoutParagraphsComponentEditForm.
 *
 * Builds the edit form for an existing layout paragraphs paragraph entity.
 */
class EditComponentForm extends ComponentFormBase {

  /**
   * {@inheritDoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    LayoutParagraphsLayout $layout_paragraphs_layout = NULL,
    string $component_uuid = NULL) {

    $this->setLayoutParagraphsLayout($layout_paragraphs_layout);
    $this->paragraph = $this->layoutParagraphsLayout
      ->getComponentByUuid($component_uuid)
      ->getEntity();
    $form_display_mode = $this->layoutParagraphsLayout->getSetting('form_display_mode');
    $form = $this->buildComponentForm($form, $form_state, $form_display_mode);

    if ($selected_layout = $form_state->getValue(['layout_paragraphs', 'layout'])) {
      $section = $this->layoutParagraphsLayout->getLayoutSection($this->paragraph);
      if ($section && $selected_layout != $section->getLayoutId()) {
        $form['layout_paragraphs']['move_items'] = [
          '#old_layout' => $section->getLayoutId(),
          '#new_layout' => $selected_layout,
          '#weight' => 5,
          '#process' => [
            [$this, 'orphanedItemsElement'],
          ],
        ];
      }
    }
    return $form;
  }

  /**
   * Create the form title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form title.
   */
  protected function formTitle() {
    return $this->t('Edit @type', ['@type' => $this->paragraph->getParagraphType()->label()]);
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

    $response->addCommand(new ReplaceCommand("[data-uuid={$uuid}]", $rendered_item));
    $response->addCommand(new LayoutParagraphsEventCommand($this->layoutParagraphsLayout, $uuid, 'component:update'));
    return $response;
  }

  /**
   * Form #process callback.
   *
   * Builds the orphaned items form element for when a new layout's
   * regions do not match the previous one's.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $form
   *   The complete form.
   *
   * @return array
   *   The form element.
   */
  public function orphanedItemsElement(array $element, FormStateInterface $form_state, array &$form) {

    $old_regions = $this->getLayoutRegionNames($element['#old_layout']);
    $new_regions = $this->getLayoutRegionNames($element['#new_layout']);
    $section = $this->layoutParagraphsLayout->getLayoutSection($this->paragraph);
    $has_orphans = FALSE;

    foreach ($old_regions as $region_name => $region) {
      if ($section->getComponentsForRegion($region_name) && empty($new_regions[$region_name])) {
        $has_orphans = TRUE;
        $element[$region_name] = [
          '#type' => 'select',
          '#options' => $new_regions,
          '#wrapper_attributes' => ['class' => ['container-inline']],
          '#title' => $this->t('Move items from "@region" to', ['@region' => $region]),
        ];
      }
    }
    if ($has_orphans) {
      $element += [
        '#type' => 'fieldset',
        '#title' => $this->t('Move Orphaned Items'),
        '#description' => $this->t('Choose where to move items for missing regions.'),
      ];
    }
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->layoutParagraphsLayout->setComponent($this->paragraph);
    if ($form_state->getValue(['layout_paragraphs', 'move_items'])) {
      $this->moveItemsSubmit($form, $form_state);
    }
    $this->tempstore->set($this->layoutParagraphsLayout);
  }

  /**
   * Moves items from removed regions into designated new ones.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function moveItemsSubmit(array &$form, FormStateInterface $form_state) {
    if ($move_items = $form_state->getValue(['layout_paragraphs', 'move_items'])) {
      $section = $this->layoutParagraphsLayout->getLayoutSection($this->paragraph);
      foreach ($move_items as $source => $destination) {
        $components = $section->getComponentsForRegion($source);
        foreach ($components as $component) {
          $component->setSettings(['region' => $destination]);
          $this->layoutParagraphsLayout->setComponent($component->getEntity());
        }
      }
    }
  }

}
