<?php

namespace Drupal\give\Form\Donation;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for give donation edit forms.
 *
 * Its not clear why this needs be editable, and why so many fields are missing.
 *
 * @deprecated There's no longer a 'link' to this in the Donation entity definition.
 */
class DonationEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = $this->entity;
    $form = parent::form($form, $form_state);

    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Username'),
      '#placeholder' => $this->t('Enter username'),
      '#target_type' => 'user',
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
      '#process_default_value' => FALSE,
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#required' => FALSE,
      '#size' => '28',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donor name'),
      '#maxlength' => 255,
      '#default_value' => $donation->getDonorName(),
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Donor email address'),
      '#default_value' => $donation->mail->value,
    ];
    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => format_stripe_currency($donation->amount->value),
      '#disabled' => TRUE,
    ];
    $form['recurring'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recurring'),
      '#default_value' => $donation->getRecurrence(),
      '#disabled' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->logger('give')->notice('The donation %label has been updated.', [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('Edit'), 'edit-form'),
    ]);
  }

}
