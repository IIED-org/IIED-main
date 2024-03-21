<?php

namespace Drupal\give\Form\Donation;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for give donation edit forms.
 */
class DonationCompleteForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = $this->entity;
    $form = parent::form($form, $form_state);

    $form['sure'] = [
      '#type' => 'markup',
      '#markup' => $this->t(
        'Are you sure you want to mark this donation from %name as complete?',
        ['%name' => $donation->name->value]
      ),
    ];

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
     return [
       'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Yes, payment is completed.'),
        '#submit' => ['::submitForm']
      ]
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->complete = 1;
    $this->entity->save();
    $form_state->setRedirect('entity.give_donation.collection');
  }

}
