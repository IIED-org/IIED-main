<?php

namespace Drupal\give\Form\Donation;

use Drupal\give\Entity\Donation;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for give donation forms.
 */
class DonationForm extends ContentEntityForm {

  /**
   * The donation being used by this form.
   *
   * @var \Drupal\give\DonationInterface
   */
  protected $entity;

  /**
   * The flood control mechanism.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a DonationForm object.
   *
   * @param EntityRepositoryInterface $entity_repository
   * @param EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param TimeInterface $time
   * @param FloodInterface $flood
   * @param LanguageManagerInterface $language_manager
   * @param DateFormatterInterface $date_formatter
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, FloodInterface $flood, LanguageManagerInterface $language_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->flood = $flood;
    $this->languageManager = $language_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('flood'),
      $container->get('language_manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);
    $form['#attributes']['class'][] = 'give-form';

    if (!empty($donation->preview)) {
      $form['preview'] = [
        '#theme_wrappers' => ['container__preview'],
        '#attributes' => ['class' => ['preview']],
      ];
      $form['preview']['donation'] = $this->entityTypeManager->getViewBuilder('give_donation')->view($donation, 'full');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#required' => TRUE,
    ];
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }
    // Do not allow authenticated users to alter the name or email values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getDisplayName();
      $form['name']['#required'] = FALSE;
      $form['name']['#plain_text'] = $user->getDisplayName();

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->getEmail();
      $form['mail']['#required'] = FALSE;
      $form['mail']['#plain_text'] = $user->getEmail();
    }

    $form['amount'] = [
      '#type' => 'number',
      '#step' => .01,
      '#min' => $this->config('give.settings')->get('min'),
      '#max' => 1000000,
      '#title' => $this->t('Amount to give'),
      '#field_prefix' => STRIPE_CURRENCY_SYMBOLS[$this->config('give.settings')->get('currency_symbol')],
      '#required' => TRUE,
    ];

    $give_form = $this->getBundleEntity();
    if ($give_form->getFrequencies()) {
      $options = [Donation::NOT_RECURRING => 'No, just once'];
      foreach ($give_form->getFrequencies() as $key => $option) {
        $options[$key] = $option['description'];
      }
      $form['recurring'] = [
        '#title' => $this->t('Make this a recurring donation:'),
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => Donation::NOT_RECURRING,
        '#required' => TRUE,
      ];
    }
    else {
      $form['recurring'] = [
        '#type' => 'value',
        '#value' => 0
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\Entity\Donation $donation */
    $donation = $this->entity;
    /** @var \Drupal\give\Entity\GiveForm $giveForm */
    $giveForm = $donation->referencedEntities()[0];
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t($giveForm->getSubmitText());
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = parent::buildEntity($form, $form_state);
    if (!$form_state->isValueEmpty('date') && $form_state->getValue('date') instanceof DrupalDateTime) {
      $donation->setCreatedTime($form_state->getValue('date')->getTimestamp());
    }
    else {
      $donation->setCreatedTime(\Drupal::time()->getRequestTime());
    }
    return $donation;
  }

  /**
   * Form submission handler for the 'preview' action.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $donation->preview = TRUE;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $donation = parent::validateForm($form, $form_state);

    // Check if flood control has been activated for sending donations.
    // If flood isn't configured at all, fall back on defaults.
    if (!$this->currentUser()->hasPermission('administer give forms')) {
      $limit = $this->config('give.settings')->get('flood.limit') ?: 50;
      $interval = $this->config('give.settings')->get('flood.interval') ?: 3600;

      if (!$this->flood->isAllowed('give', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You cannot send more than %limit donations in @interval. Try again later.', [
          '%limit' => $limit,
          '@interval' => $this->dateFormatter->formatInterval($interval),
        ]));
      }
    }

    return $donation;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;

    $this->flood->register('give', $this->config('give.settings')->get('flood.interval'));

    if ($donation->save() == SAVED_NEW) {
      // Redirect to the second step.
      $form_state->setRedirectUrl(
        Url::fromRoute(
          'entity.give_form.donate',
          [
            'give_form' => $donation->get('give_form')->target_id,
            'give_donation' => $donation->id()
          ]
        )
      );
    }
  }

}
