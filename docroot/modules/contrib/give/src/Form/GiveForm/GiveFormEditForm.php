<?php

namespace Drupal\give\Form\GiveForm;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Egulias\EmailValidator\EmailValidator;
use Drupal\Component\Utility\Tags;

/**
 * Base form for give form edit forms.
 */
class GiveFormEditForm extends EntityForm implements ContainerInjectionInterface {
  use ConfigFormBaseTrait;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new GiveFormEditForm.
   *
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(EmailValidator $email_validator) {
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['give.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\give\Entity\GiveForm $give_form */
    $give_form = $this->entity;
    // A more Drupalish way of doing this would be to assign weightings to
    // giveForms on the Entity collection page.
    $default_form = $this->config('give.settings')->get('default_form');
    $frequencies = ($give_form->isNew() and !$give_form->getFrequencies()) ? give_get_default_frequencies() : $give_form->getFrequencies();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $give_form->label(),
      '#description' => $this->t("Example: 'General donations', 'Renovation fund drive', or 'Annual appeal'."),
      '#required' => TRUE,
      '#weight' => 0,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $give_form->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => '\Drupal\give\Entity\GiveForm::load',
      ],
      '#disabled' => !$give_form->isNew(),
      '#weight' => 1,
    ];

    $form['selected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make this the default payment form'),
      '#default_value' => ($default_form && $default_form === $give_form->id()),
      '#weight' => 2,
    ];

    $form['form_layout'] = [
      '#title' => $this->t('Form layout'),
      '#type' => 'details',
      '#weight' => 3,
      'collect_address' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Collect address'),
        '#default_value' => $give_form->getCollectAddress(),
        '#description' => $this->t('Require the donor to provide their address information.  This is not needed for credit card or other processing.'),
      ],
      'check_or_other_text' => [
        '#type' => 'textarea',
        '#title' => $this->t('Text to show for check or other'),
        '#default_value' => $give_form->getCheckOrOtherText(),
        '#description' => $this->t('Optional message to show potential givers who select the "Check or other" donation method.'),
        '#rows' => 3,
        '#size' => 50
      ],
      'credit_card_extra_text' => [
        '#type' => 'textarea',
        '#title' => $this->t('Extra text to show above credit card form'),
        '#default_value' => $give_form->getCreditCardExtraText(),
        '#description' => $this->t('Optional message to show above credit card form for potential givers who select the "Credit card" donation method.'),
        '#rows' => 3,
        '#size' => 50
      ],
      'bankTransferDetails' => [
        '#type' => 'textarea',
        '#title' => $this->t('How to make a make a bank transfer'),
        '#description' => $this->t('Including IBAN etc.'),
        '#default_value' => $give_form->bankTransferDetails,
        '#description' => $this->t('Optional message to show above credit card form for potential givers who select the "Credit card" donation method.'),
        '#rows' => 3,
        '#size' => 50
      ],
      // Very unclear how this field is used.
      'donor_name_description' => [
        '#type' => 'textfield',
        '#title' => $this->t('Donor name description'),
        '#description' => $this->t("This text is shown under the donor name on the payment page (the second page in the donation process)."),
        '#default_value' => $give_form->getDonorNameDescription(),
      ],
      'payment_page_title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Payment page title'),
        '#description' => $this->t("This text is shown on the second page of the donation process, after an amount has been selected but before a payment method chosen and finalized.  The tokens :name and :sitename will be replaced with the name the donor entered on the first page and the name of the site, respectively"),
        '#default_value' => $give_form->getPaymentPageTitle(),
      ],
      // The difference between these next two is not clear.
      'submit_text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Submit button text'),
        '#description' => $this->t("Override the submit button's default <em>Give</em> text."),
        '#default_value' => $give_form->getSubmitText(),
      ],
      'payment_submit_text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Submit payment button text'),
        '#description' => $this->t("Override the payment page submit button's default <em>Give</em> text."),
        '#default_value' => $give_form->getPaymentSubmitText(),
      ]
    ];

    $form['submission'] = [
      '#title' => $this->t('Form submission'),
      '#type' => 'details',
      '#weight' => 4,
      'thanks_message' => [
        '#type' => 'textfield',
        '#title' => $this->t("Message of thanks"),
        '#description' => $this->t('Tokens are allowed...'),
        '#default_value' => $give_form->thanks_message,
        '#weight' => 4
      ],
      'redirect_uri' => [
        '#type' => 'textfield',
        '#title' => $this->t('Redirect Page'),
        '#placeholder' => '/next/page',
        '#required' => TRUE,
        '#description' => $this->t('The path to redirect the form after sumbission. Must start with /.'),
        '#default_value' => $give_form->getRedirectUri(),
        '#element_validate' => [[$this, 'validateUri']]
      ],
      //@todo URGENT separate this into 3 values, one for each mail
      'autoreply' => [
        '#type' => 'checkbox',
        '#title' => $this->t('<strong>Send automatic acknowledgements with receipts</strong>'),
        '#default_value' => $give_form->get('autoreply'),
        '#description' => $this->t('As soon as a donation is complete, send a reply by e-mail with a receipt (including amount and payment method).  Subject lines and messages for one-time donations, recurring donations, and check pledges are configurable below when this is enabled.'),
        '#ajax' => [
          'callback' => '::autoReplyCallback',
          'effect' => 'fade',
          'event' => 'change',
          'wrapper' => 'ajax-auto-reply-fields',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ]
        ],
      ],
      'autoreply_fields' => [
        '#type' => 'item',
        '#prefix' => '<div id="ajax-auto-reply-fields">',
        '#suffix' => '</div>'
      ]
    ];

    if (!$form_state->getValue('autoreply')) {
      $autoreply = ($give_form->isNew()) ? FALSE : $give_form->get('autoreply');
      $form_state->setValue('autoreply', $autoreply);
    }

    if ($form_state->getValue('autoreply')) {
      $form['submission']['autoreply_fields']['_available_tokens'] = [
        '#type' => 'item',
        '#title' => $this->t('Available tokens for automatic acknowledgements'),
        '#description' => $this->t('In addition to the receipt which is attached below each message (see the e-mail preview), the following tokens are available for all automatic reply messages and subjects: @tokens.', ['@tokens' => implode(', ', give_donation_tokens())]),
      ];

      $form['submission']['autoreply_fields']['onetime_mail'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('One-time donation reply'),
        '#open' => TRUE,
        '#tree' => TRUE
      ];
      $form['submission']['autoreply_fields']['onetime_mail']['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $give_form->get('onetime_mail')['subject'],
        '#description' => $this->t('Acknowledgement e-mail subject line for one-time donations.'),
        '#required' => TRUE,
      ];
      $form['submission']['autoreply_fields']['onetime_mail']['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Message'),
        '#default_value' => $give_form->get('onetime_mail')['body'],
        '#description' => $this->t('This should include your organization name and any relevant tax information.'),
        '#required' => TRUE,
      ];

      $form['submission']['autoreply_fields']['recurring_mail'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Recurring donation reply'),
        '#tree' => TRUE
      ];
      $form['submission']['autoreply_fields']['recurring_mail']['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $give_form->get('recurring_mail')['subject'],
        '#description' => $this->t('Acknowledgement e-mail subject line for recurring donations.'),
        '#required' => TRUE,
      ];
      $form['submission']['autoreply_fields']['recurring_mail']['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Auto-reply to recurring donation with receipt'),
        '#default_value' => $give_form->get('recurring_mail')['body'],
        '#description' => $this->t('This should include your organization name and any relevant tax information.'),
        '#required' => TRUE,
      ];

      $form['submission']['autoreply_fields']['pledge_mail'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Check (pledged) donation reply'),
        '#tree' => TRUE
      ];
      $form['submission']['autoreply_fields']['pledge_mail']['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $give_form->get('pledge_mail')['subject'],
        '#description' => $this->t('Acknowledgement e-mail subject line for a pledge to donate by check.'),
        '#required' => TRUE,
      ];
      $form['submission']['autoreply_fields']['pledge_mail']['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Auto-reply with receipt'),
        '#default_value' => $give_form->get('pledge_mail')['body'],
        '#description' => $this->t('This should include your organization name and any relevant tax information, and an indication of how you will follow up to help them complete the donation.'),
        '#required' => TRUE,
      ];
    }




    $form['submission']['recipients'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipients'),
      '#default_value' => $give_form->getRecipients(),
      '#description' => $this->t("Provide who should be notified when a donation is received. Example: 'donations@example.org' or 'fund@example.org,staff@example.org' . To specify multiple recipients, separate each email address with a comma."),
      '#required' => FALSE,
      '#element_validate' => [[$this, 'ValidateMails']]
    ];


    $name_field = $form_state->get('num_intervals');
    $form['frequency'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#title' => $this->t('Frequency Intervals (Plans)'),
      '#tree' => TRUE,
      '#weight' => 5,
    ];
    $form['frequency']['frequency_intervals_table'] = [
      '#type' => 'table',
      '#title' => $this->t('Frequency'),
      '#header' => [
        $this->t('Interval'),
        $this->t('Interval count'),
        $this->t('Description'),
      ],
      '#prefix' => '<div id="frequency-intervals-wrapper">',
      '#suffix' => '</div>',
    ];

    if (empty($name_field)) {
      $name_field = count($frequencies) ?: 1;
      $form_state->set('num_intervals', $name_field);
    }
    for ($i = 0; $i < $name_field; $i++) {
      $form['frequency']['frequency_intervals_table'][$i]['interval'] = [
        '#type' => 'select',
        '#title' => '',
        '#options' => [
          'day' => $this->t('day'),
          'week' => $this->t('week'),
          'month' => $this->t('month'),
          'year' => $this->t('year'),
        ],
        '#default_value' => (isset($frequencies[$i])) ? $frequencies[$i]['interval'] : 'month',
      ];
      $form['frequency']['frequency_intervals_table'][$i]['interval_count'] = [
        '#type' => 'number',
        '#title' => '',
        '#default_value' => (isset($frequencies[$i])) ? $frequencies[$i]['interval_count'] : 1,
      ];
      $form['frequency']['frequency_intervals_table'][$i]['description'] = [
        '#type' => 'textfield',
        '#title' => '',
        '#placeholder' => $this->t('Plans without descriptions will not be saved.'),
        '#default_value' => (isset($frequencies[$i])) ? $frequencies[$i]['description'] : '',
      ];
    }
    $form['frequency']['frequency_intervals_table']['actions'] = [
      '#type' => 'actions',
    ];
    $form['frequency']['frequency_intervals_table']['actions']['add_frequency'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'frequency-intervals-wrapper',
      ],
    ];

    if ($name_field > 1) {
      $form['frequency']['frequency_intervals_table']['actions']['remove_frequency'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'frequency-intervals-wrapper',
        ],
      ];
    }


    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Callback for return autoreply inputs.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function autoReplyCallback(array &$form, FormStateInterface $form_state) {
    return $form['submission']['autoreply_fields'];
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['frequency']['frequency_intervals_table'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_intervals');
    $add_button = $name_field + 1;
    $form_state->set('num_intervals', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_intervals');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_intervals', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $give_form = $this->entity;
    $give_form->save();
    $give_settings = $this->config('give.settings');

    // Update the default form.
    if ($form_state->getValue('selected')) {
      $give_settings
        ->set('default_form', $give_form->id())
        ->save();
    }
    // If it was the default form, empty out the setting.
    elseif ($give_settings->get('default_form') == $give_form->id()) {
      $give_settings
        ->set('default_form', NULL)
        ->save();
    }
    $form_state->setRedirectUrl($give_form->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\GiveFormInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    $frequency = $form_state->getValue('frequency'); // not an entity property, so has not been added.
    unset($frequency['frequency_intervals_table']['actions']);
    $entity->set(
      'frequencies',
      array_filter(
        $frequency['frequency_intervals_table'],
        function ($f) { return !empty($f['description']) and $f['interval']; }
      )
    );
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values['recipients'] = Tags::explode($form_state->getValue('recipients'));
    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }


  public function validateMails(array $element, FormStateInterface $form_state) {
    foreach (Tags::explode($element['#value']) as $mail) {
      if (!$this->emailValidator->isValid(trim($mail))) {
        $form_state->setError($element, $this->t('Invalid email :mail', [':mail' => $mail]));
      }
    }
  }

  function validateUri($element, $form_state) {
    if ($element['#value'][0] !== '/') {
      $form_state->setError($element, $this->t('Path must begin with /.'));
    }
    // @todo inject this
    if (!\Drupal::service('path.validator')->isValid($element['#value'])) {
      $form_state->setError($element, $this->t('Path does not exist.'));
    }
  }
}
