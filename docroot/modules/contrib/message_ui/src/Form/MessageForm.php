<?php

namespace Drupal\message_ui\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the message_ui entity edit forms.
 *
 * @ingroup message_ui
 */
class MessageForm extends ContentEntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->languageManager = $container->get('language_manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\message\Entity\Message $message */
    $message = $this->entity;

    $template = $this->entityTypeManager->getStorage('message_template')->load($this->entity->bundle());

    if ($this->config('message_ui.settings')->get('show_preview')) {
      $form['text'] = [
        '#type' => 'item',
        '#title' => t('Message template'),
        '#markup' => implode("\n", $template->getText()),
      ];
    }

    // Create the advanced vertical tabs "group".
    $form['advanced'] = [
      '#type' => 'details',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];

    $form['owner'] = [
      '#type' => 'fieldset',
      '#title' => t('Owner information'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'advanced',
      '#weight' => 90,
      '#attributes' => ['class' => ['message-form-owner']],
      '#attached' => [
        'library' => ['message_ui/message_ui.message'],
        'drupalSettings' => [
          'message_ui' => [
            'anonymous' => \Drupal::config('message_ui.settings')->get('anonymous'),
          ],
        ],
      ],
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'owner';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'owner';
    }

    // @todo: assess the best way to access and create tokens tab from D7.
    $tokens = $message->getArguments();

    $access = $this->account->hasPermission('update tokens') || $this->account->hasPermission('bypass message access control');
    if (!empty($tokens) && ($access)) {
      $form['tokens'] = [
        '#type' => 'fieldset',
        '#title' => t('Tokens and arguments'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#group' => 'advanced',
        '#weight' => 110,
      ];

      // Give the user an option to update the har coded tokens.
      $form['tokens']['replace_tokens'] = [
        '#type' => 'select',
        '#title' => t('Update tokens value automatically'),
        '#description' => t('By default, the hard coded values will be replaced automatically. If unchecked - you can update their value manually.'),
        '#default_value' => 'no_update',
        '#options' => [
          'no_update' => t("Don't update"),
          'update' => t('Update automatically'),
          'update_manually' => t('Update manually'),
        ],
      ];

      $form['tokens']['values'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="replace_tokens"]' => ['value' => 'update_manually'],
          ],
        ],
      ];

      // Build list of fields to update the tokens manually.
      foreach ($message->getArguments() as $name => $value) {
        $form['tokens']['values'][$name] = [
          '#type' => 'textfield',
          '#title' => t("@name's value", ['@name' => $name]),
          '#default_value' => $value,
        ];
      }
    }

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $message->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
      '#access' => $this->languageManager->isMultilingual(),
    ];

    // @todo : add similar to node/from library, adding css for
    // 'message-form-owner' class.
    // $form['#attached']['library'][] = 'node/form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $message = $this->entity;

    // @todo : check if we need access control here on form submit.
    // Create custom save button with conditional label / value.
    $element['save'] = $element['submit'];
    if ($message->isNew()) {
      $element['save']['#value'] = t('Create');
    }
    else {
      $element['save']['#value'] = t('Update');
    }
    $element['save']['#weight'] = 0;

    $mid = $message->id();
    $url = is_object($message) && !empty($mid) ? Url::fromRoute('entity.message.canonical', ['message' => $mid]) : Url::fromRoute('message.overview_templates');
    $link = Link::fromTextAndUrl(t('Cancel'), $url)->toString();

    // Add a cancel link to the message form actions.
    $element['cancel'] = [
      '#type' => 'markup',
      '#markup' => $link,
    ];

    // Remove the default "Save" button.
    $element['submit']['#access'] = FALSE;

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Updates the message object by processing the submitted values.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the node object from the submitted values.
    parent::submitForm($form, $form_state);

    /* @var $message Message */
    $message = $this->entity;

    // Set message owner.
    $uid = $form_state->getValue('uid');
    if (is_array($uid) && !empty($uid[0]['target_id'])) {
      $message->setOwnerId($uid[0]['target_id']);
    }

    // Get the tokens to be replaced and prepare for replacing.
    $replace_tokens = $form_state->getValue('replace_tokens');
    $token_actions = empty($replace_tokens) ? [] : $replace_tokens;

    // Get the message args and replace tokens.
    if ($args = $message->getArguments()) {

      if (!empty($token_actions) && $token_actions != 'no_update') {

        // Loop through the arguments of the message.
        foreach (array_keys($args) as $token) {

          if ($token_actions == 'update') {
            // Get the hard coded value of the message and him in the message.
            $token_name = str_replace(['@{', '}'], ['[', ']'], $token);
            $token_service = \Drupal::token();
            $value = $token_service->replace($token_name, ['message' => $message]);
          }
          else {
            // Hard coded value given from the user.
            $value = $form_state->getValue($token);
          }

          $args[$token] = $value;
        }
      }
    }

    $this->entity->setArguments($args);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $message Message */
    $message = $this->entity;
    $insert = $message->isNew();

    $message->save();

    // Set up message link and status message contexts.
    $message_link = $message->toLink($this->t('View'))->toString();
    $context = [
      '@type' => $message->getTemplate()->id(),
      '%title' => 'Message:' . $message->id(),
      'link' => $message_link,
    ];
    $t_args = [
      '@type' => $message->getEntityType()->getLabel(),
      '%title' => 'Message:' . $message->id(),
    ];

    // Display newly created or updated message depending on if new entity.
    if ($insert) {
      $this->logger('content')->notice('@type: added %title.', $context);
      $this->messenger()->addMessage(t('@type %title has been created.', $t_args));
    }
    else {
      $this->logger('content')->notice('@type: updated %title.', $context);
      $this->messenger()->addMessage(t('@type %title has been updated.', $t_args));
    }

    // Redirect to message view display if user has access.
    if ($message->id()) {
      $form_state->setValue('mid', $message->id());
      $form_state->set('mid', $message->id());
      if ($message->access('view')) {
        $form_state->setRedirect('entity.message.canonical', ['message' => $message->id()]);
      }
      else {
        $form_state->setRedirect('<front>');
      }
      // @todo : for node they clear temp store here, but perhaps unused with
      // message.
    }
    else {
      // In the unlikely case something went wrong on save, the message will be
      // rebuilt and message form redisplayed.
      $this->messenger()->addMessage(t('The message could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
