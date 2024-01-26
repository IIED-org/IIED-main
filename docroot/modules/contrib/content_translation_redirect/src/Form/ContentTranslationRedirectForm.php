<?php

namespace Drupal\content_translation_redirect\Form;

use Drupal\content_translation_redirect\ContentTranslationRedirectManager;
use Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface;
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
   * The content translation redirect manager.
   *
   * @var \Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface
   */
  protected $manager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * ContentTranslationRedirectForm constructor.
   *
   * @param \Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface $manager
   *   The content translation redirect manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(ContentTranslationRedirectManagerInterface $manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('content_translation_redirect.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
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
      '#options' => ContentTranslationRedirectManager::getStatusCodes(),
      '#default_value' => $redirect->getStatusCode(),
      '#empty_option' => $this->t('- Disabled -'),
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
      '#options' => ContentTranslationRedirectManager::getTranslationModes(),
      '#default_value' => $redirect->getTranslationMode(),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (($path = $form_state->getValue('path')) && $path[0] !== '/') {
      $form_state->setErrorByName('path', $this->t("The path '%path' has to start with a slash.", ['%path' => $path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
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
    return $status;
  }

  /**
   * Returns an array of available bundles.
   *
   * @return array
   *   A list of available bundles.
   */
  protected function getAvailableBundles(): array {
    $options = [];

    $entity_types = $this->manager->getSupportedEntityTypes();
    $storage = $this->entityTypeManager->getStorage('content_translation_redirect');

    foreach ($entity_types as $entity_type_id => $entity_type) {
      $label = (string) $entity_type->getLabel();

      if ($storage->load($entity_type_id) === NULL) {
        $options[$label][$entity_type_id] = "$label (Default)";
      }

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_id => $bundle_info) {
        $redirect_id = $entity_type_id . '__' . $bundle_id;

        if ($storage->load($redirect_id) === NULL) {
          $options[$label][$redirect_id] = $bundle_info['label'];
        }
      }
    }
    return $options;
  }

}
