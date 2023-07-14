<?php

namespace Drupal\login_destination\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for login destination.
 */
class LoginDestinationDeleteRuleForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\login_destination\Entity\LoginDestination $login_destination */
    $login_destination = $this->entity;
    return $this->t('Are you sure you want to delete the login destination "@destination"?', [
      '@destination' => $login_destination->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('login_destination.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\login_destination\Entity\LoginDestination $login_destination */
    $login_destination = $this->entity;
    $login_destination->delete();
    $this->messenger()->addMessage($this->t('The login destination %destination has been deleted.', [
      '%destination' => $login_destination->getLabel(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
