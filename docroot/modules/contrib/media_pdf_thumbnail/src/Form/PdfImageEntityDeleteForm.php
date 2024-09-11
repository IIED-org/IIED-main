<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class PdfImageEntityDeleteForm.
 *
 * Provides a form for deleting a pdf image entity.
 *
 * @ingroup media_pdf_thumbnail
 */
class PdfImageEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
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
