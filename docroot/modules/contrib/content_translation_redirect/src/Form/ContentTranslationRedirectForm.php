<?php

namespace Drupal\content_translation_redirect\Form;

use Drupal\content_translation_redirect\Entity\ContentTranslationRedirect;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\PathElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for Content Translation Redirect add and edit forms.
 */
class ContentTranslationRedirectForm extends EntityForm {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * ContentTranslationRedirectForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $redirect */
    $redirect = $this->entity;

    if ($redirect->isNew()) {
      $form['id'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#description' => $this->t('Select the type of redirect you would like to add.'),
        '#options' => $this->getAvailableBundles(),
        '#required' => TRUE,
      ];
    }

    $form['code'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect status'),
      '#description' => $this->t('You can find more information about HTTP redirect status codes <a href="@status-codes" target="_blank">here</a>.', [
        '@status-codes' => 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes',
      ]),
      '#options' => ContentTranslationRedirect::getStatusCodes(),
      '#default_value' => $redirect->getStatusCode(),
      '#empty_option' => $this->t('- Not specified -'),
    ];
    $form['path'] = [
      '#type' => 'path',
      '#title' => $this->t('Redirect path'),
      '#convert_path' => PathElement::CONVERT_NONE,
      '#description' => $this->t('Path to redirect. Leave blank to redirect to original content.'),
      '#default_value' => $redirect->getPath(),
    ];
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Act on'),
      '#options' => ContentTranslationRedirect::getTranslationModes(),
      '#default_value' => $redirect->getTranslationMode(),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (($path = $form_state->getValue('path')) && $path[0] !== '/') {
      $form_state->setErrorByName('path', $this->t("The path '%path' has to start with a slash.", ['%path' => $path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Created the content translation redirect of type %label.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('Saved the content translation redirect of type %label.', [
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.content_translation_redirect.collection');
  }

  /**
   * Returns an array of available bundles.
   *
   * @return array
   *   A list of available bundles.
   */
  protected function getAvailableBundles(): array {
    $options = [];

    $entity_types = $this->getSupportedEntityTypes();
    $storage = $this->entityTypeManager->getStorage('content_translation_redirect');

    foreach ($entity_types as $entity_type_id => $entity_type_label) {
      if (!$storage->load($entity_type_id)) {
        $options[$entity_type_label][$entity_type_id] = $this->t('@label (Default)', [
          '@label' => $entity_type_label,
        ]);
      }

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle_info) {
        $redirect_id = $entity_type_id . '__' . $bundle_id;

        if (!$storage->load($redirect_id)) {
          $options[$entity_type_label][$redirect_id] = $bundle_info['label'];
        }
      }
    }
    return $options;
  }

  /**
   * Returns a list of supported entity types.
   *
   * @return array
   *   A list of available entity types.
   */
  protected function getSupportedEntityTypes(): array {
    $entity_types = [];

    // A list of entity types that are not supported.
    $unsupported_types = [
      // Custom blocks.
      'block_content',
      // Comments.
      'comment',
      // Contact messages.
      'contact_message',
      // Menu items.
      'menu_link_content',
      // Shortcut items.
      'shortcut',
    ];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Check for a content entity type.
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      // Check for a supported entity type.
      if (in_array($entity_type_id, $unsupported_types)) {
        continue;
      }

      // Check for a translatable entity type with a canonical link.
      if ($entity_type->isTranslatable() && $entity_type->hasLinkTemplate('canonical')) {
        $entity_types[$entity_type_id] = (string) $entity_type->getLabel();
      }
    }
    return $entity_types;
  }

}
