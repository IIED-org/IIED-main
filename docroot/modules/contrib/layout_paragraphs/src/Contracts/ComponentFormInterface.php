<?php

namespace Drupal\layout_paragraphs\Contracts;

use Drupal\Core\Form\FormInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;

/**
 * Defines an interface for Layout Paragraphs component forms.
 */
interface ComponentFormInterface extends FormInterface {

  /**
   * Gets the paragraph entity.
   */
  public function getParagraph();

  /**
   * Sets the paragraph entity.
   */
  public function setParagraph(Paragraph $paragraph);

  /**
   * Gets the Layout Paragraphs Layout object.
   */
  public function getLayoutParagraphsLayout();

  /**
   * Setter for layoutParagraphsLayout property.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   *
   * @return $this
   */
  public function setLayoutParagraphsLayout(LayoutParagraphsLayout $layout_paragraphs_layout);

  /**
   * Builds the paragraph component using submitted form values.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   The paragraph entity.
   */
  public function buildParagraphComponent(array $form, FormStateInterface $form_state);

  /**
   * Form #process callback.
   *
   * Renders the layout paragraphs behavior form for layout selection.
   *
   * @param array $element
   *   The form element.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   *
   * @return array
   *   The processed element.
   */
  public function layoutParagraphsBehaviorForm(array $element, FormStateInterface $form_state, array &$form);

  /**
   * Form #process callback.
   *
   * Attaches the behavior plugin forms.
   *
   * @param array $element
   *   The form element.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   *
   * @return array
   *   The processed element.
   */
  public function behaviorPluginsForm(array $element, FormStateInterface $form_state, array &$form);

  /**
   * Provides an Ajax reponse to inject the new / editing component.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function successfulAjaxSubmit(array $form, FormStateInterface $form_state);

  /**
   * Get the form mode.
   */
  public function getFormMode();

  /**
   * Set the form view mode.
   *
   * @param string $view_mode
   *   The view mode.
   */
  public function setFormMode($view_mode);

}
