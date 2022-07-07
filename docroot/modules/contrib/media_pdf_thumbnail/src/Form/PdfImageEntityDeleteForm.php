<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class PdfImageEntityDeleteForm
 *
 * @ingroup media_pdf_thumbnail
 */
class PdfImageEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('entity.pdf_image_entity.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('pdf_image_entity')->notice('deleted %title.',
      [
        '%title' => $this->entity->id(),
      ]);
    // Redirect to term list after delete.
    $form_state->setRedirect('view.pdf_image_entity.list');
  }
}
