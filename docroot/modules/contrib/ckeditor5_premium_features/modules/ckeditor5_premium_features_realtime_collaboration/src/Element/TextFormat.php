<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features_realtime_collaboration\Element;

use Drupal\ckeditor5_premium_features\CKeditorFieldKeyHelper;
use Drupal\ckeditor5_premium_features\Element\Ckeditor5TextFormatInterface;
use Drupal\ckeditor5_premium_features\Element\Ckeditor5TextFormatTrait;
use Drupal\ckeditor5_premium_features\Storage\EditorStorageHandlerInterface;
use Drupal\ckeditor5_premium_features\Utility\ApiAdapter;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Ckeditor5ChannelHandlingException;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Entity\Channel;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Entity\ChannelInterface;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Entity\ChannelStorage;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Utility\CollaborationSettings;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Utility\NotificationDocumentHelper;
use Drupal\ckeditor5_premium_features_realtime_collaboration\Utility\NotificationIntegrator;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Text Format utility class for handling the collaboration data.
 */
class TextFormat implements Ckeditor5TextFormatInterface {

  use Ckeditor5TextFormatTrait;

  /**
   * Channel storage.
   *
   * @var \Drupal\ckeditor5_premium_features_realtime_collaboration\Entity\ChannelStorage
   */
  protected ChannelStorage $channelStorage;

  /**
   * Creates the text format element instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ckeditor5_premium_features_realtime_collaboration\Utility\CollaborationSettings $collaborationSettings
   *   The settings service.
   * @param \Drupal\ckeditor5_premium_features\Utility\ApiAdapter $apiAdapter
   *   The api adapter.
   * @param \Drupal\ckeditor5_premium_features\Storage\EditorStorageHandlerInterface $editorStorageHandler
   *   The editor storage handler.
   * @param \Drupal\ckeditor5_premium_features_realtime_collaboration\Utility\NotificationIntegrator $notificationIntegrator
   *   The notifications integrator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CollaborationSettings $collaborationSettings,
    protected ApiAdapter $apiAdapter,
    protected EditorStorageHandlerInterface $editorStorageHandler,
    protected NotificationIntegrator $notificationIntegrator,
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack
  ) {
    $this->channelStorage = $this->entityTypeManager->getStorage(ChannelInterface::ENTITY_TYPE_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function processElement(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    if (!$this->editorStorageHandler->hasCollaborationFeaturesEnabled($element)) {
      // Don't process as the editor does not have
      // any collaboration features enabled.
      return $element;
    }

    $this->generalProcessElement($element, $form_state, $complete_form, $this->collaborationSettings);
    $element_unique_id = CKeditorFieldKeyHelper::getElementUniqueId($element['#id']);
    $element_drupal_id = CKeditorFieldKeyHelper::cleanElementDrupalId($element['#id']);
    $id_attribute = 'data-' . static::STORAGE_KEY . '-element-id';

    if ($this->collaborationSettings->isPresenceListEnabled()) {
      $element['presence_list'] = [
        '#type' => 'container',
        '#weight' => -5,
        '#attributes' => [
          'class' => [
            'ck-presence-list-container',
          ],
          'id' => $element_drupal_id . '-value-presence-list-container',
        ],
      ];

      $element['#attached']['drupalSettings']['presenceListCollapseAt'] = $this->collaborationSettings->getPresenceListCollapseAt();
    }

    $isNotificationEnabled = FALSE;
    if ($this->moduleHandler->moduleExists('ckeditor5_premium_features_notifications')) {
      $isNotificationEnabled = TRUE;
      $default_element_keys = [
        '#type' => 'textarea',
        '#attributes' => [
        // The admin theme may vary, so this is the safest solution.
          'style' => 'display: none;',
          $id_attribute => $element_unique_id,
        ],
        '#theme_wrappers' => [],
      ];
      $element['track_changes'] = [
        '#default_value' => [],
      ] + $default_element_keys;
      $element['track_changes']['#attributes']['class'] = ['track-changes-data'];

      $element['comments'] = [
        '#default_value' => [],
      ] + $default_element_keys;
      $element['comments']['#attributes']['class'] = ['comments-data'];
    }
    $element['#attached']['drupalSettings']['ckeditor5Premium']['notificationsEnabled'] = $isNotificationEnabled;

    $form_object = $form_state->getFormObject();

    if ($this->isFormTypeSupported($form_object)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->getRelatedEntity($form_object);
      if ($entity) {
        $entity_language = $entity->language()->getId();

        $channel_id = NestedArray::getValue(
          $form_state->getUserInput(),
          [...$element['#parents'], 'entity_channel']
        ) ?? $this->getChannelId($entity->uuid(), $element_unique_id, $entity_language);

        if (!$entity->isNew()) {
          $channel = $this->channelStorage->loadByEntity($entity, $element_unique_id);
          if (!$channel) {
            $channel = $this->handleEntityChannel($entity, $channel_id, $element_unique_id);
          }
          if ($channel instanceof ChannelInterface) {
            $channel_id = $channel->id();
          }
          else {
            throw new Ckeditor5ChannelHandlingException("Problem occurred while creating Ckeditor5 Channel Entity");
          }
        }

        // Do not validate bundle on form submit to prevent error when text format has been modified during node edition.
        $request_method = $this->requestStack->getCurrentRequest()->getMethod();
        if ($request_method == 'GET') {
          $this->apiAdapter->validateBundleVersion($channel_id, $element['#format']);
        }

        $element['entity_channel'] = [
          '#type' => 'hidden',
          '#value' => $channel_id,
        ];
      }
    }
    else {
      $channel_id = $this->getChannelId(uniqid(), $element_drupal_id);
    }

    $items = $form_state->get(static::STORAGE_KEY) ?? [];
    $items[$element_unique_id] = [
      'parents' => $element['#parents'],
      'array_parents' => $element['#array_parents'],
      'changed' => $form_state->getValue('changed'),
    ];
    $form_state->set(static::STORAGE_KEY, $items);

    $element['#attached']['drupalSettings']['ckeditor5ChannelId'][$element_drupal_id] = $channel_id;

    $track_changes_states = $this->editorStorageHandler->getTrackChangesStates($element, TRUE);
    $element['#attached']['drupalSettings']['ckeditor5Premium']['tracking_changes']['default_state'] = $track_changes_states;
    $element['value']['#theme'] = 'ckeditor5_textarea';

    // Remove element containing the document id before editing and set the callback to add it again after submit.
    $pattern = '/<div data-document-id="[^"]+"><\/div>/';
    $value = $element['value']['#default_value'] ?? '';
    $element['value']['#default_value'] = preg_replace($pattern, '', $value);
    self::addCallback('rtcPreSaveSubmit', [['actions', 'submit', '#submit']], $complete_form, 0, TRUE);
    self::addCallback('previewAction', [['actions', 'preview', '#submit']], $complete_form, 0, TRUE);


    $realtimeConfig = $this->configFactory->get('ckeditor5_premium_features_realtime_collaboration.settings');
    if ($form_object instanceof EntityFormInterface) {
      $realtimePermissionsEnabled = $realtimeConfig->get('realtime_permissions');
      $textFormatChangeAllowed = $realtimeConfig->get('allow_text_format_change');
      if (!$form_object->getEntity()->isNew() && ($realtimePermissionsEnabled || !$textFormatChangeAllowed)) {
        $element['format']['format']['#attributes']['disabled'] = 'disabled';
      }
    }

    $element['#element_validate'] = [[static::class, 'validateElement']];
    return $element;
  }

  /**
   * Validation function for text fields with realtime collaboration enabled.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   * @param array $form
   *   The form structure.
   */
  public static function validateElement(array $element, FormStateInterface $form_state, array $form): void {
    /** @var \Drupal\ckeditor5_premium_features_realtime_collaboration\Element\TextFormat $service */
    $service = \Drupal::service('ckeditor5_premium_features_realtime_collaboration.element.text_format');

    if (!$service->configFactory->get('ckeditor5_premium_features_realtime_collaboration.settings')->get('realtime_permissions')) {
      return;
    }
    if (!$service->editorStorageHandler->hasCollaborationFeaturesEnabled($element, FALSE)) {
      return;
    }

    $channelId = $form_state->getValue([...$element["#parents"], 'entity_channel']);

    if (!$channelId) {
      return;
    }

    $response = $service->apiAdapter->exportDocument($channelId);

    if(isset($response['code'])) {
      $form_state->setError($element, 'An error occurred during document export for validation. Please check details in Drupal watchdog and contact support in case you need assistance solving the issue.');
      return;
    }

    // Set the value retrieved from cloud. This is done instead validation in
    // case other user with broader permissions made changes that are not allowed
    // for user that is saving the entity.
    $form_state->setValue([...$element["#parents"], 'value'], array_shift($response));
  }

  /**
   * Loads the service in static call and executes pre preview action.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function previewAction(array &$form, FormStateInterface $form_state): void {
    $service = \Drupal::service('ckeditor5_premium_features_realtime_collaboration.element.text_format');
    $service->preparePreview($form, $form_state);
  }

  /**
   * Custom action for previewing content. Newly added suggestions won't be added to database yes, so we're storing
   * attribute suggestion data in temp storage for text filter processing.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function preparePreview(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!$this->isFormTypeSupported($form_object)) {
      // Do not process anything, the entity is missing.
      return;
    }
    $items = $form_state->get(static::STORAGE_KEY) ?? [];
    $storageData = [];
    foreach ($items as $item_key => $item_parents) {
      $documentId = $form_state->getValue([...$item_parents['parents'], 'entity_channel']) ?? '';
      if (empty($documentId)) {
        continue;
      }
      $suggestions = $this->apiAdapter->getDocumentSuggestions(
        $documentId, [
          'sort_by' => 'updated_at',
          'order' => 'desc',
          'limit' => 1000,
        ]
      );
      foreach ($suggestions as $suggestion) {
        if (!str_contains($suggestion['type'], 'attribute')) {
          continue;
        }
        $storageData[$suggestion['id']] = $suggestion;
      }
    }

    $store = \Drupal::service('tempstore.private')->get('ckeditor5_premium_features_collaboration');
    $store->set($form_object->getEntity()->uuid(), $storageData);
  }

  /**
   * {@inheritdoc}
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    /** @var \Drupal\ckeditor5_premium_features_realtime_collaboration\Element\TextFormat $service */
    $service = \Drupal::service('ckeditor5_premium_features_realtime_collaboration.element.text_format');
    return $service->processElement($element, $form_state, $complete_form);
  }

  /**
   * Callback for operations that should be handled before entity is saved.
   * It adds an empty element with document ID stored as an attribute value, which is required for collaboration tags
   * filter in order to be able to get suggestion data from cloud.
   *
   * @param array $form
   *  The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *  The form state object.
   */
  public static function rtcPreSaveSubmit(array &$form, FormStateInterface $form_state): void {
    $items = $form_state->get(static::STORAGE_KEY) ?? [];
    foreach ($items as $element_data) {
      $channelId = $form_state->getValue([
        ...$element_data['parents'],
        'entity_channel',
      ]);

      $value = $form_state->getValue([
        ...$element_data['parents'],
        'value',
      ]);

      $value .= '<div data-document-id="' . $channelId . '"></div>';

      $form_state->setValue([
        ...$element_data['parents'],
        'value',
      ], $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function onCompleteFormSubmit(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\ckeditor5_premium_features_realtime_collaboration\Element\TextFormat $service */
    $service = \Drupal::service('ckeditor5_premium_features_realtime_collaboration.element.text_format');
    $service->completeFormSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function completeFormSubmit(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!$this->isFormTypeSupported($form_object) || $form_state->isRebuilding()) {
      // Do not process anything, the entity is missing or form is rebuilding.
      return;
    }
    $items = $form_state->get(static::STORAGE_KEY) ?? [];

    $order_switch = $this->detectOrderChange($form_state, $items);

    $entity = $this->getRelatedEntity($form_object);

    foreach ($items as $element_key => $element_data) {
      $entity_channel = $form_state->getValue([
        ...$element_data['parents'],
        'entity_channel',
      ]);

      if (!$entity_channel || isset($order_switch[$element_key]) && $order_switch[$element_key] === FALSE) {
        if (!$entity_channel && isset($order_switch[$element_key]) && $order_switch[$element_key] !== FALSE) {
          $order_switch[$order_switch[$element_key]] = $element_key;
          unset($order_switch[$element_key]);
        }
        $channel = $this->channelStorage->loadByEntity($entity, $element_key);
        if ($channel instanceof Channel) {
          $channel->delete();
        }
      }
    }

    foreach ($items as $element_key => $element_data) {
      $entity_channel = $form_state->getValue([
        ...$element_data['parents'],
        'entity_channel',
      ]);

      if (!$entity_channel || isset($order_switch[$element_key]) && $order_switch[$element_key] === FALSE) {
        $this->channelStorage->deleteChannels($entity, $element_key);
        continue;
      }
      if ($this->moduleHandler->moduleExists('ckeditor5_premium_features_notifications')) {
        $array_parents = $element_data['array_parents'] ?? [];

        $source_original_data = $this->getFormElementOriginalValue($form, $array_parents);
        $source_new_data = $form_state->getValue(
          [...$element_data['parents'],
            'value',
          ]
        ) ?? '';
        $changed = $element_data['changed'] ?? FALSE;

        if ($changed) {
          $commentsData = $this->getFormElementSourceData($form_state, $element_data['parents'], 'comments', $element_key);
          $this->notificationIntegrator->transformCommentsData($commentsData);

          $documentHelper = new NotificationDocumentHelper($element_key, $source_original_data, $source_new_data);

          $suggestionData = $this->apiAdapter->getDocumentSuggestions(
            $entity_channel, [
              'include_deleted' => 'true',
              'sort_by' => 'updated_at',
              'order' => 'desc',
            ]
          );

          $this->notificationIntegrator->processSuggestionGroups($suggestionData);
          $chainedSuggestions = $this->notificationIntegrator->chainSuggestion($suggestionData);

          $this->notificationIntegrator->handleDocumentUpdateEvent($entity, $documentHelper);
          $this->notificationIntegrator->handleSuggestionsEvent($entity, $documentHelper, $changed, $chainedSuggestions, $commentsData);
          $this->notificationIntegrator->handleCommentsEvent($entity, $documentHelper, $changed, $commentsData, $chainedSuggestions);
        }
      }

      $this->handleEntityChannel($entity, $entity_channel, $element_key, $order_switch[$element_key] ?? NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function onValidateForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\ckeditor5_premium_features_realtime_collaboration\Element\TextFormat $service */
    $service = \Drupal::service('ckeditor5_premium_features_realtime_collaboration.element.text_format');
    $service->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Validation is performed on single elements separately.
  }

  /**
   * Handles creating new Channel entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Referenced entity.
   * @param string $entity_channel
   *   Desired entity channel ID.
   * @param string $element_id
   *   ID of the field element.
   * @param string|null $new_element_id
   *   New element ID to overwrite the existing one.
   *
   * @return \Drupal\ckeditor5_premium_features_realtime_collaboration\Entity\ChannelInterface|null
   *   Channel entity if exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function handleEntityChannel(EntityInterface $entity, string $entity_channel, string $element_id, string $new_element_id = NULL): ?ChannelInterface {
    $channel = $this->channelStorage->load($entity_channel);

    if (!$channel) {
      $channel = $this->channelStorage->loadByEntity($entity, $new_element_id ?? $element_id);
    }
    elseif ($channel->getKeyId() != $element_id) {
      $entity_language = $entity->language()->getId();
      $entity_channel = $this->getChannelId($entity->uuid(), $element_id, $entity_language);
      $channel = NULL;
    }

    if ($channel instanceof Channel && !empty($new_element_id) && $channel->getKeyId() !== $new_element_id) {
      $channel->setKeyId($new_element_id)->save();
    }

    if ($channel) {
      return $channel;
    }

    try {
      return $this->channelStorage->createChannel($entity, $entity_channel, $new_element_id ?? $element_id);
    }
    catch (EntityStorageException $e) {
      return $this->channelStorage->loadByEntity($entity, $element_id);
    }
  }

  /**
   * Generate unique channel ID value.
   *
   * @param string $uuid
   *   The node uuid.
   * @param string $key_id
   *   Key id of the field.
   * @param string $langcode
   *   The langcode of the entity.
   *
   * @return string
   *   The channelID.
   */
  private function getChannelId(string $uuid, string $key_id, string $langcode = ''): string {
    $base_str = $uuid . $key_id . time() . $langcode;
    return substr(Crypt::hashBase64($base_str), 0, 36);
  }

  /**
   * Returns a list of element IDs that was reordered.
   *
   * @param \Drupal\Core\Form\FormState $form_state
   *   Form state object.
   * @param array $items
   *   An array with element IDs and their parent paths.
   *
   * @return array
   *   Array containing pairs of element IDs: "before" => "after" order change.
   */
  private function detectOrderChange(FormState $form_state, array $items): array {
    $field_storage = $form_state->get('field_storage');
    $field_storage_parents = $field_storage['#parents'] ?? [];

    $change_order = [];

    foreach ($items as $item_key => $item_data) {
      $new_element_id = $this->getElementIdAfterOrderChanging($item_data['parents'], $field_storage_parents);

      if ($new_element_id === FALSE) {
        if (empty($change_order[$item_key])) {
          $change_order[$item_key] = FALSE;
        }
        else {
          $change_order[$change_order[$item_key]] = FALSE;
        }
        continue;
      }

      if ($new_element_id !== NULL && $new_element_id != $item_key && empty($change_order[$new_element_id])) {
        $change_order[$new_element_id] = $item_key;
      }
    }

    foreach ($items as $item_key => $item_data) {
      if (in_array($item_key, $change_order) && !isset($change_order[$item_key])) {
        $change_order[$item_key] = FALSE;
      }
    }

    return $change_order;
  }

  /**
   * Detects and return elements' new ID if order was changed or NULL otherwise.
   *
   * @param array $parents_path
   *   Element parents path.
   * @param array $fields_storage
   *   Form storage #fields value.
   */
  private function getElementIdAfterOrderChanging(array $parents_path, array $fields_storage): string|null|bool {
    $processed_parents = [];
    $was_modified_delta = FALSE;
    for ($current_key = 0; $current_key < count($parents_path); $current_key++) {
      $parent = $parents_path[$current_key];
      if (!isset($parents_path[$current_key + 1]) || ($parents_path[$current_key + 1] !== 0 && (int) $parents_path[$current_key + 1] == 0)) {
        $processed_parents[] = $parent;
        continue;
      }

      $current_delta = $parents_path[$current_key + 1];

      $elementParent = NestedArray::getValue($fields_storage, [
        ...$processed_parents,
        '#fields',
        $parent,
      ]);

      if (!empty($elementParent)) {
        $old_delta = $elementParent['original_deltas'][$current_delta] ?? $current_delta;
      } else {
        $old_delta = NULL;
      }

      $processed_parents[] = $parent;
      if ($old_delta === NULL) {
        if (!$was_modified_delta) {
          return FALSE;
        }
        continue;
      }

      if ($old_delta === $current_delta) {
        continue;
      }
      $was_modified_delta = TRUE;
      $processed_parents[] = $old_delta;
      ++$current_key;
    }

    if ($was_modified_delta) {
      $new_element_id = 'edit-' . implode('-', $processed_parents);
      return CKeditorFieldKeyHelper::getElementUniqueId($new_element_id);
    }

    return NULL;
  }

}
