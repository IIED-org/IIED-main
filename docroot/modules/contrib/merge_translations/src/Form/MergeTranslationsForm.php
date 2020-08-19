<?php

namespace Drupal\merge_translations\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The merge translation form.
 */
class MergeTranslationsForm extends FormBase {

  /**
   * Action do nothing after importing the translation.
   */
  const ACTION_DONOTHING = '_none';

  /**
   * Action remove source node after importing the translation.
   */
  const ACTION_REMOVE = 'remove';

  /**
   * The langcode _auto.
   */
  const LANGCODE_AUTO = '_auto';

  /**
   * The Entity Type.
   */
  const ENTITYTYPE = 'node';

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * RouteMatchInterface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Node object.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $node;

  /**
   * LanguageManager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languages;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * MergeTranslationsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   RouteMatch.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languages
   *   Language.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   ModuleHandler.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $routeMatch, LanguageManagerInterface $languages, MessengerInterface $messenger, ModuleHandlerInterface $moduleHandler, AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->languages = $languages;
    $this->messenger = $messenger;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $currentUser;

    $this->node = $entityTypeManager->getStorage(self::ENTITYTYPE)->load($routeMatch->getParameter(self::ENTITYTYPE));
  }

  /**
   * Dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\Core\Form\FormBase|\Drupal\merge_translations\Form\mergeTranslationsForm
   *   MergeTranslation form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'merge_translations_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $isTranslationImportAvailable = $this->isTranslationImportAvailable();

    $form['node_translations'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Status'),
      ],
      '#rows' => [],
    ];
    foreach ($this->languages->getLanguages() as $key => $language) {
      $language_name = $language->getName();
      $source = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => [$this->node->getType()],
        ],
        '#disabled' => $isTranslationImportAvailable,
        '#maxlength' => 255,
      ];
      $status = '-';

      if ($this->node->getTranslationStatus($key)) {
        $translation = $this->node->getTranslation($key);
        $source = [
          '#markup' => $this->t('<a href="@href">@title</a>', [
            '@href' => $translation->toUrl()->toString(),
            '@title' => $translation->getTitle(),
          ]),
        ];
        $status = $translation->isPublished() ? $this->t('Published') : $this->t('Not published');

        if ($translation->isDefaultTranslation()) {
          $language_name = $this->t('<b>@language (Original language)</b>', ['@language' => $language_name]);
        }
      }
      $form['node_translations'][$key]['language_name'] = [
        '#markup' => $language_name,
      ];
      $form['node_translations'][$key]['node_source'] = $source;
      $form['node_translations'][$key]['status'] = [
        '#markup' => $status,
      ];
    }

    $actions = [
      self::ACTION_DONOTHING => $this->t('Do nothing'),
    ];
    $type = $this->node->getType();
    if ($this->currentUser->hasPermission("bypass node access") || $this->currentUser->hasPermission("delete any $type content") || $this->currentUser->hasPermission("delete own $type content")) {
      $actions[self::ACTION_REMOVE] = $this->t('Remove node');
    }

    $form['node_source_action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Action with source node after import'),
      '#options' => $actions,
      '#default_value' => self::ACTION_DONOTHING,
      '#disabled' => $isTranslationImportAvailable,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import translations'),
      '#button_type' => 'primary',
      '#disabled' => $isTranslationImportAvailable,
    ];

    return $form;
  }

  /**
   * SubmitForm.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $translations = $form_state->getValue('node_translations');
    $action = $form_state->getValue('node_source_action');

    foreach ($translations as $langcode => $source) {
      if (empty($source['node_source']) || (($entity = $this->entityTypeManager->getStorage(self::ENTITYTYPE)->load($source['node_source'])) === NULL)) {
        continue;
      }
      // Add translation.
      $this->mergeTranslations($entity, $langcode);

      if (self::ACTION_REMOVE === $action) {
        $this->removeNode($entity);
      }
    }

    $this->node->save();
  }

  /**
   * Validate the submitted values.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $translations = $form_state->getValue('node_translations');
    foreach ($translations as $langcode => $source) {
      if (empty($source['node_source'])) {
        continue;
      }
      if ($this->node->id() === $source['node_source']) {
        $form_state->setErrorByName("node_translations][{$langcode}", $this->t('Translation source and target can not be the same'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Remove node.
   *
   * @param \Drupal\node\NodeInterface $node_source
   *   Node_source.
   *
   * @return bool|\Exception
   *   Status of operation.
   */
  private function removeNode(NodeInterface $node_source) {
    if (!$node_source->access('delete')) {
      return FALSE;
    }

    try {
      $this->messenger->addStatus($this->t('Node @node has been removed.', ['@node' => $node_source->getTitle()]));
      $node_source->delete();

      return TRUE;
    }
    catch (\Exception $e) {
      return $e;
    }
  }

  /**
   * MergeTranslations.
   *
   * @param \Drupal\node\NodeInterface $node_source
   *   Node source.
   * @param string $langcode
   *   Langcode.
   */
  private function mergeTranslations(NodeInterface $node_source, $langcode) {
    $languages = $this->languages->getLanguages();

    if ($langcode != self::LANGCODE_AUTO) {
      $this->addTranslation($langcode, $node_source->toArray());
    }
    else {

      foreach ($languages as $key => $language) {
        if ($node_source->hasTranslation($key)) {
          $this->addTranslation($key, $node_source->getTranslation($key)->toArray());
        }
      }
    }
  }

  /**
   * AddTranslation.
   *
   * @param string $langcode
   *   Langcode.
   * @param array $node_array
   *   Node_array.
   *
   * @return bool
   *   True or false.
   */
  private function addTranslation($langcode, array $node_array) {
    $this->moduleHandler->invokeAll('merge_translations_prepare_alter', [&$node_array]);

    $node_target = $this->node;
    $message_argumens = [
      '@langcode' => $langcode,
      '@title' => $node_target->getTitle(),
    ];

    if (!$node_target->hasTranslation($langcode)) {
      $node_target->addTranslation($langcode, $node_array);
      $this->messenger->addStatus($this->t('Add @langcode translation to node @title.', $message_argumens));
      return TRUE;
    }

    $this->messenger->addWarning($this->t('Translation @langcode already exist in node @title.', $message_argumens));

    return FALSE;
  }

  /**
   * Check if translation import is possible.
   *
   * @return bool
   *   True or false.
   */
  private function isTranslationImportAvailable() {
    $languages = $this->languages->getLanguages();

    if (!$this->node->isTranslatable()) {
      $this->messenger->addWarning(
        $this->t('Translation for this content type is disabled now. Go to <a href="@link">Settings page</a>.',
          ['@link' => '/admin/structure/types/manage/' . $this->node->getType() . '#edit-language'])
      );

      return TRUE;
    }

    foreach ($languages as $key => $language) {
      if (!$this->node->getTranslationStatus($key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Returns a page title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function getTitle() {
    return $this->t('Merge translations of %label', ['%label' => $this->node->label()]);
  }

}
