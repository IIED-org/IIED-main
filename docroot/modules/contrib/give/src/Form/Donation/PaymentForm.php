<?php

namespace Drupal\give\Form\Donation;

use Drupal\give\Plugin\Field\FieldType\PaymentMethod;
use Drupal\give\MailHandlerInterface;
use Drupal\give\GiveStripeInterface;
use Drupal\give\ProblemLog;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for users to donate.
 * @todo Inject Token, extension.path.resolver, time services.
 */
class PaymentForm extends ContentEntityForm {

  /**
   * The give mail handler service.
   *
   * @var \Drupal\give\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The Stripe Service.
   *
   * @var \Drupal\give\GiveStripeInterface
   */
  protected $giveStripe;

  /**
   * The Problem Log.
   *
   * @var \Drupal\give\ProblemLog
   */

  protected $problemLog;

  /**
   * Constructs a PaymentForm object.
   *
   * @param EntityRepositoryInterface $entity_repository
   * @param EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param TimeInterface $time
   * @param MailHandlerInterface $mail_handler
   * @param GiveStripeInterface $give_stripe
   * @param ProblemLog $problem_log
   */

  public function __construct($entity_repository, $entity_type_bundle_info, $time, MailHandlerInterface $mail_handler, GiveStripeInterface $give_stripe, ProblemLog $problem_log) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->mailHandler = $mail_handler;
    $this->giveStripe = $give_stripe;
    $this->problemLog = $problem_log;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('give.mail_handler'),
      $container->get('give.stripe'),
      $container->get('give.problem_log')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $give_settings = $this->config('give.settings');
    $form_settings = $donation->getGiveForm();

    $form = parent::form($form, $form_state, $donation);
    $form['#prefix'] = '<div class="flow">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'give-form give-form-payment flow-middle';

    $form['#title'] = $form_settings->getPaymentPageTitle($donation);
    if ($donation->amount->isEmpty()) {
      $form['amount'] = [
        '#title' => $this->t('Amount'),
        '#field_prefix' => STRIPE_CURRENCY_SYMBOLS[$this->config('give.settings')->get('currency_symbol')],
        '#type' => 'number',
        '#step' => 0.01,
        '#default_value' => 0,
        '#weight' => 0,
      ];
    }
    else {
      $form['show_amount'] = [
        '#type' => 'item',
        '#title' => $this->t('Amount'),
        '#value' => format_stripe_currency($donation->amount->value ?? 0),
        '#plain_text' => $donation->isRecurring() ?
          $this->t(':plan', [':plan' => $donation->getRecurringProductName()]) :
          $donation->getFormattedAmount(),
        '#weight' => 0,
      ];
    }
    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose donation method'),
      '#options' => payment_method_names(),
      '#required' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['method']['#options'][PaymentMethod::GIVE_VIA_STRIPE])) {
      $help = Markup::create("Stripe is in test mode, use one of the following card numbers with any future date:
  <br />4242424242424242 Visa + 3 digits
  <br />4000056655665556 Visa (debit) + 3 digits
  <br />5555555555554444 Mastercard + 3 digits
  <br />2223003122003222 Mastercard (2-series) + 3 digits
  <br />5200828282828210 Mastercard (debit) + 3 digits
  <br />5105105105105100 Mastercard (prepaid) + 3 digits
  <br />378282246310005 American Express + 4 digits
  <br />6011111111111117 Discover + 3 digits
  <br />3056930009020004 Diners Club + 3 digits
  <br />3566002020360505 JCB + 3 digits
  <br />6200000000000005 UnionPay"
      );
      if (substr($give_settings->get('stripe_publishable_key'), 0, 7) == 'pk_test') {
        $form['stripe_help'] = [
          '#type' => 'item',
          '#markup' => $help,
          '#weight' => 2,
          '#states' => [
            'visible' => [
              ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_STRIPE],
            ]
          ]
        ];
      }

      $form['#attached'] = [
        'library' => ['give/give-stripe-helper'],
        'drupalSettings' => [
          'give' => [
            'stripe_publishable_key' => $give_settings->get('stripe_publishable_key'),
          ],
          'http_header' => [
            ['Content-Security-Policy' => "connect-src 'https://api.stripe.com'"],
            ['Content-Security-Policy' => "child-src 'https://js.stripe.com'"],
            ['Content-Security-Policy' => "script-src 'https://js.stripe.com'"],
          ],
        ],
      ];
      if ($give_settings->get('log_problems')) {
        // Use the ModuleHandler service to get the module path.
        $mod_path = \Drupal::service('module_handler')->getModule('give')->getPath();
        $form['#attached']['drupalSettings']['give']['problem_log'] = [
          'donation_uuid' => $donation->uuid(),
          'url' => Url::fromUri("base:$mod_path/give_problem_log.php")->toString(),
        ];
      }
    }

    if (isset($form['method']['#options'][PaymentMethod::GIVE_VIA_BANK])) {
      $form['bank_help'] = [
        '#type' => 'item',
        '#markup' => $form_settings->get('bankTransferDetails'),
        '#weight' => 2,
        '#states' => [
          'visible' => [
            ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_BANK],
          ]
        ]
      ];
    }

    $form['stripe_errors'] = [
      '#markup' => '<span class="payment-errors"></span>',
      '#weight' => 10,
    ];

    $form['stripe_token'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];

    // Custom radar rules can't use name but Stripe's risk assessment does.
    // Therefore this should default to the entered name but be editable.
    // Matslats says: What is Radar? Are we even using it?
    // Matslats says: Why should data entered in stage 1 of the form be editable?
    $form['donor_name_for_stripe'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $donation->getDonorName(),
      '#description' => $form_settings->getDonorNameDescription()
    ];

    if ($form_settings->getCollectAddress()) {
      $form['address_line1'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Billing address'),
        '#required' => TRUE,
        '#weight' => 4,
      ];
      $form['address_line2'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Apt or unit #'),
        '#weight' => 5,
      ];
      $form['address_city'] = [
        '#type' => 'textfield',
        '#title' => $this->t('City or district'),
        // TODO add '#default_value' (for everything) so form repopulates after errors
        '#required' => TRUE,
        '#weight' => 6
      ];
      $form['address_zip'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Postal code / ZIP'),
        '#required' => TRUE,
        '#weight' => 7
      ];
      $form['address_state'] = [
        '#type' => 'textfield',
        '#title' => $this->t('State or province'),
        '#required' => TRUE,
        '#weight' => 8
      ];
      $form['address_country'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Country'),
        // @todo Default to the country of the site.
        '#default_value' => '',
        '#required' => TRUE,
        '#weight' => 9,
      ];
    }

    $form['credit_card_extra_text'] = [
      '#type' => 'item',
      '#description' => $form_settings->getCreditCardExtraText(),
      '#weight' => 12,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_STRIPE],
        ],
      ],
    ];

    $form['card'] = [
      '#type' => 'item',
      '#title' => $this->t('Credit or debit card'),
      '#required' => TRUE,
       //For 'item' elements, 'required' is supposed to only show the asterisk,
       //but Drupal is broken.
      '#value' => TRUE,
      '#markup' => '<div id="stripe-card-element" class="give-card-element"></div><div class="form--inline-feedback form--inline-feedback--success" id="stripe-card-errors"></div><div class="form--inline-feedback form--inline-feedback--error" id="stripe-card-success"></div>',
      '#allowed_tags' => ['div'],
      '#weight' => 13,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_STRIPE],
        ],
      ],
    ];

    $form['check_or_other_text'] = [
      '#type' => 'item',
      '#description' => $form_settings->getCheckOrOtherText(),
      '#maxlength' => 2000,
      '#weight' => 13,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_CHECK],
        ],
      ],
    ];

    $form['telephone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telephone number'),
      '#maxlength' => 15,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_CHECK],
        ],
      ],
    ];

    $form['check_or_other_information'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Further information'),
      '#description' => $this->t('Please ask any questions or explain anything needed to arrange for giving your donation.'),
      '#weight' => 16,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => PaymentMethod::GIVE_VIA_CHECK],
        ]
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t($donation->getGiveForm()->getPaymentSubmitText());
    $elements['delete']['#title'] = $this->t('Cancel');
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = parent::buildEntity($form, $form_state);
    $donation->setChangedTime(\Drupal::time()->getRequestTime());
    return $donation;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('method') == PaymentMethod::GIVE_VIA_STRIPE and empty($form_state->getValue('stripe_token'))) {
      $form_state->setErrorByName('stripe_errors', $this->t("Could not retrieve token from Stripe."));
      $this->logger('give')->error("Could not retrieve token from Stripe.");
      $this->problemLog->log($this->entity->uuid(), 'Stripe server-side', 'Could not retrieve token from Stripe');
      return;
      // @todo I think the stripe_token should not be saved as part of the donation entity because it is temporary.
    }
    /** @var \Drupal\give\Entity\Donation $donation */
    $donation = parent::validateForm($form, $form_state);
    $give_config = $this->config('give.settings');
    $errors = $form_state->getErrors();
    if ($errors) {
      foreach ($errors as $error) {
        // Drupal\Core\StringTranslation implements magic __toString() method.
        $this->logger('give')->error('Server-side form validation: %err_msg.', ['%err_msg' => $error]);
        $this->problemLog->log($donation->uuid(), 'Server-side form validation', $error);
      }
      // We must return here or we will process Stripe despite errors in the form!
      return;
    }
    if ($donation->isCompleted()) {
      $donate_path = Url::fromRoute(
        'entity.give_form.canonical',
        ['give_form' => $donation->getGiveForm()->id()]
      )->toString();
      $form_state->setErrorByName(
        'stripe_errors',
        $this->t(
          'You have already completed this donation. Thank you! Please <a href=":donate_path">donate again</a> if you wish to give more.',
          [':donate_path' => $donate_path]
        )
      );
      $this->problemLog->log($donation->uuid(), 'Resubmission error', 'Gave "You have already completed this donation" message.');
      // We must return here or we will process Stripe despite errors in the form!
      return;
    }

    if ($form_state->getValue('method') != PaymentMethod::GIVE_VIA_STRIPE) {
      return;
    }
    $this->giveStripe->setApiKey($give_config->get('stripe_secret_key'));
    // If the donation is recurring, we create a plan and a customer.
    if ($donation->isRecurring()) {
      // See https://stripe.com/docs/api/plans/object
      $plan_data = [
        "amount" => (int)($donation->amount->value * 100),
        "currency" => $give_config->get('currency_symbol'),
        "interval" => $donation->getRecurrenceIntervalUnit(),
        "interval_count" => $donation->getRecurrenceIntervalCount(),
        "product" => [
          "name" => $donation->getRecurringProductName(),
          "statement_descriptor" => $donation->getStatementDescriptor(),
        ],
      ];
      try {
        $plan = $this->giveStripe->createPlan($plan_data);
      }
      catch (\Exception $e) {
        $this->processStripeErrors($e);
        return;
      }

      // Create the customer with subscription plan on Stripe's servers - this
      // will charge the user's card.
      $customer_data = [
        "plan" => $plan->id,
        "source" => $donation->stripe_token->value,
        "email" => $donation->mail->value,
        "metadata" => [
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->mail->value,
          "name" => $donation->getDonorName(),
        ],
      ];

      try {
        if ($this->giveStripe->createCustomer($customer_data)) {
          $this->entity->setCompleted();  // But not saved yet.
        }
      }
      catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $e->getMessage());
        $this->logger('give')->error('Stripe error: %msg.', ['%msg' => $e->getMessage()]);
        $this->problemLog->log($donation->uuid(), 'Stripe server-side', $e->getMessage());
      }
    }
    else {
      // If the donation is *not* recurring, only in this case do we create a charge ourselves.
      // Create the charge on Stripe's servers - this will charge the user's card.
      $donation_data = [
        "amount" => (int)($donation->amount->value * 100), // amount in cents
        "currency" => $give_config->get('currency_symbol'),
        "source" => $donation->stripe_token->value,
        "description" => $donation->label(),
        "metadata" => [
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->mail->value,
          "name" => $donation->getDonorName(),
        ],
      ];
      try {
        if ($this->giveStripe->createCharge($donation_data)) {
          $this->entity->setCardInfo($this->giveStripe);
          $this->entity->setCompleted();  // But not saved yet.
        }
      }
      catch (\Exception $e) {
        $this->processStripeErrors($e);
      }
    }
  }

  /**
   * Helper method to process stripe errors.
   *
   * There are three places they can happen, and we do a lot, so abstracting this out.
   */
  public function processStripeErrors(\Exception $e) {
    $form_state->setErrorByName('stripe_errors', $e->getMessage());
    $this->logger('give')->error('Stripe error: %msg.', ['%msg' => $e->getMessage()]);
    $this->problemLog->log($donation->uuid(), 'Stripe server-side', $e->getMessage());
    // trying to decide between simply adding e-mail (and maybe name) to log so it can be queried on error/detail
    // or starting a different listing just for 'suspicious' items like your card was declined (or if Stripe can give more feedback)
    // Stripe server-side          | Your card was declined.
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;

    // Redirect the user.
    $give_form = $donation->getGiveForm();
    $thanks_message = \Drupal::Token()->replace(
      $give_form->get('thanks_message'),
      ['give_donation' => $donation]
    );
    $this->messenger()->addStatus($thanks_message);

    if ($give_form->getRecipients()) {
      $this->mailHandler->sendDonationNotices($donation, User::load($this->currentUser()->id()));
    }
    $donation->save();
    $url = Url::fromUserInput($give_form->getRedirectUri());
    $form_state->setRedirectUrl($url);
  }

}
