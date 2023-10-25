<?php

namespace Drupal\layout_paragraphs_custom_host_entity_test\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the lp host entity entity edit forms.
 */
class LpHostEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => \Drupal::service('renderer')->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New lp host entity %label has been created.', $message_arguments));
      $this->logger('layout_paragraphs_custom_host_entity_test')->notice('Created new lp host entity %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The lp host entity %label has been updated.', $message_arguments));
      $this->logger('layout_paragraphs_custom_host_entity_test')->notice('Updated new lp host entity %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.lp_host_entity.canonical', ['lp_host_entity' => $entity->id()]);
  }

}
