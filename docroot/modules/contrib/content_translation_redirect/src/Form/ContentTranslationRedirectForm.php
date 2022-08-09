<?php

namespace Drupal\content_translation_redirect\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityForm;

/**
 * Form handler for the Content Translation Redirect add and edit forms.
 */
class ContentTranslationRedirectForm extends EntityForm {

  use ContentTranslationRedirectFormTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  const ALL_BUNDLES_KEY = '_all';

  /**
   * ContentTranslationRedirectForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $entity */
    $entity = $this->entity;

    // If this is a new entity, then list available bundles.
    if ($entity->isNew()) {
      $form['id'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#description' => $this->t('Select the entity type for which you want to add a redirect.'),
        '#options' => $this->getAvailableBundles(),
        '#required' => TRUE,
      ];
    }

    $settings = [
      'code' => $entity->getStatusCode(),
      'message' => $entity->getMessage(),
    ];
    $form += $this->redirectSettingsForm($settings);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $entity */
    $entity = $this->entity;

    // Set the label on new entity.
    if ($entity->isNew()) {
      $entity_id = $form_state->getValue('id');
      list($entity_type_id, $bundle_id) = explode('__', $entity_id);

      // Get the entity label.
      $entity_label = (string) $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
      // Get the bundle label.
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      $entity_label .= $bundle_id === static::ALL_BUNDLES_KEY
        ? ': ' . $this->t('All bundles')
        : ': ' . $bundle_info[$bundle_id]['label'];

      // Set the label to the config entity.
      $entity->set('label', $entity_label);
    }

    // Set code and message.
    $entity->setStatusCode($form_state->getValue('code'));
    $entity->setMessage($form_state->getValue('message'));
    // Save the entity.
    $status = $entity->save();
    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Created the %label Content Translation Redirect.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('Saved the %label Content Translation Redirect.', [
        '%label' => $entity->label(),
      ]));
    }
    $form_state->setRedirect('entity.content_translation_redirect.collection');
  }

  /**
   * Returns an array of available content entity bundles.
   *
   * @return array
   *   A list of available content entity bundles as $id => $label.
   */
  protected function getAvailableBundles() {
    $options = [];
    // Get entity type definitions with bundles.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();

    // Get entity types labels.
    $labels = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Check content entity type.
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      // Check unsupported entity types.
      if (in_array($entity_type_id, $this->getUnsupportedEntityTypes())) {
        continue;
      }
      // Check translatable entity type with bundles and canonical link.
      if (!$entity_type->isTranslatable() || !$entity_type->hasLinkTemplate('canonical') || !isset($bundles[$entity_type_id])) {
        continue;
      }
      // Get entity type label.
      $labels[$entity_type_id] = (string) $entity_type->getLabel() ?: $entity_type_id;
    }

    // Iterate content entity types.
    $storage = $this->entityTypeManager->getStorage('content_translation_redirect');
    foreach ($labels as $entity_type_id => $label) {
      $options[$label][$entity_type_id . '__' . static::ALL_BUNDLES_KEY] = $this->t('- All bundles -');
      foreach ($bundles[$entity_type_id] as $bundle_id => $bundle_info) {
        $entity_id = $entity_type_id . '__' . $bundle_id;
        if (!$storage->load($entity_id)) {
          $options[$label][$entity_id] = $bundle_info['label'];
        }
      }
    }
    return $options;
  }

  /**
   * Returns a list of entity types that are not supported.
   *
   * @return array
   *   A list of entity types that are not supported.
   */
  protected function getUnsupportedEntityTypes() {
    return [
      // Custom blocks.
      'block_content',
      // Comments.
      'comment',
      // Menu items.
      'menu_link_content',
      // Shortcut items.
      'shortcut',
    ];
  }

}
