<?php

namespace Drupal\form_mode_control\Form;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a form to configure which form modes to use, by bundle.
 */
class FormModeConfigForm extends ConfigFormBase {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');

    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'form_mode_config';
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
    $defaults = $this->config('form_mode_control.settings')->get('defaults');
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $entityTypeIds = $this->getEntityTypesWithFormModes();

    $form['switch'] = [
      '#type' => 'item',
      '#title' => $this->t('Switch form mode'),
      '#description' => $this->t('The form mode can be switched by adding a <code>?display=form_mode_id</code> query parameter to the URL of the create or edit form. For example, to use the form mode with the ID <code>compact</code>, add <code>?display=compact</code> to the URL. Make sure to grant permission to the appropriate roles to allow them to switch form modes using this method.'),
    ];

    try {
      $permissionsUrl = Url::fromRoute('user.admin_permissions.module', ['modules' => 'form_mode_control']);
    }
    catch (RouteNotFoundException $exception) {
      $permissionsUrl = Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-form_mode_control']);
    }

    $form['permissions_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Manage form mode permissions'),
      '#url' => $permissionsUrl,
      '#attributes' => [
        'class' => ['button', 'button--secondary'],
      ],
    ];

    $form['defaults'] = [
      '#type' => 'item',
      '#title' => $this->t('Default form modes'),
      '#description' => $this->t('Choose the default form modes to be used for each bundle and role. These defaults will be used every time a create or edit form is requested without <code>display</code> query parameter, regardless of permissions.'),
      '#description_display' => 'before',
    ];

    foreach ($entityTypeIds as $entityTypeId) {
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
      $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);
      $bundles = $this->getBundlesWithFormModes($entityTypeId);

      $form[$entityTypeId] = [
        '#type' => 'vertical_tabs',
        '#title' => $entityType->getLabel(),
        '#tree' => TRUE,
      ];

      foreach ($bundles as $bundle) {
        $form[$entityTypeId][$bundle] = [
          '#type' => 'details',
          '#title' => $bundleInfo[$bundle]['label'] . ' (' . $entityType->getLabel() . ') ',
          '#group' => $entityTypeId,
        ];
        $form[$entityTypeId][$bundle]['create'] = [
          '#type' => 'details',
          '#title' => $this->t('Create'),
          '#open' => TRUE,
        ];
        $form[$entityTypeId][$bundle]['update'] = [
          '#type' => 'details',
          '#title' => $this->t('Edit'),
          '#open' => TRUE,
        ];

        foreach ($roles as $role) {
          assert($role instanceof RoleInterface);

          $options = $this->entityDisplayRepository
            ->getFormModeOptionsByBundle($entityTypeId, $bundle);
          if ($options === []) {
            continue;
          }

          $form[$entityTypeId][$bundle]['create'][$role->id()] = [
            '#type' => 'select',
            "#options" => $options,
            '#title' => $role->label(),
            '#default_value' => $defaults[$entityTypeId][$bundle]['create'][$role->id()] ?? key($options),
          ];

          $form[$entityTypeId][$bundle]['update'][$role->id()] = [
            '#type' => 'select',
            "#options" => $options,
            '#title' => $role->label(),
            '#default_value' => $defaults[$entityTypeId][$bundle]['update'][$role->id()] ?? key($options),
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $formModes = $form_state->getValues();
    unset($formModes['switch']);
    unset($formModes['defaults']);

    foreach ($formModes as $entityTypeId => $bundles) {
      foreach ($bundles as $bundle => $operations) {
        if (!is_array($operations)) {
          unset($formModes[$entityTypeId][$bundle]);
          continue;
        }

        foreach ($operations as $operation => $roles) {
          foreach ($roles as $role => $formModeId) {
            if ($formModeId === 'default') {
              unset($formModes[$entityTypeId][$bundle][$operation][$role]);
            }
          }

          if ($formModes[$entityTypeId][$bundle][$operation] === []) {
            unset($formModes[$entityTypeId][$bundle][$operation]);
          }
        }

        if ($formModes[$entityTypeId][$bundle] === []) {
          unset($formModes[$entityTypeId][$bundle]);
        }
      }

      if ($formModes[$entityTypeId] === []) {
        unset($formModes[$entityTypeId]);
      }
    }

    $configuration = $this->config('form_mode_control.settings');
    $configuration->set('defaults', $formModes);
    $configuration->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['form_mode_control.settings'];
  }

  /**
   * Listing entities which have form mode.
   *
   * @return array
   *   An array of entity types with form modes.
   */
  protected function getEntityTypesWithFormModes(): array {
    $entityTypes = $this->entityTypeManager->getDefinitions();
    $results = [];

    foreach ($entityTypes as $entityTypeId => $entityType) {
      if (!$entityType->get('field_ui_base_route')) {
        continue;
      }
      if (!$entityType->hasFormClasses()) {
        continue;
      }

      $formModes = $this->entityDisplayRepository->getFormModes($entityTypeId);
      if ($formModes === []) {
        continue;
      }

      $results[] = $entityTypeId;
    }

    return $results;
  }

  /**
   * Get an array of all active form displays.
   *
   * @return array
   *   An array of entity type IDs, keyed by form display ID.
   */
  protected function getActiveFormDisplays(string $entityTypeId): array {
    $storage = $this->entityTypeManager
      ->getStorage('entity_form_display');
    $formDisplayIds = $storage->getQuery()
      ->condition('status', TRUE)
      ->condition('mode', 'default', '<>')
      ->condition('targetEntityType', $entityTypeId)
      ->execute();
    $formDisplays = $storage->loadMultiple($formDisplayIds);

    $results = [];
    foreach ($formDisplays as $formDisplayId => $formDisplay) {
      assert($formDisplay instanceof EntityFormDisplayInterface);
      $results[$formDisplayId] = $formDisplay->getTargetEntityTypeId();
    }

    return $results;
  }

  /**
   * Return bundles which have a form mode activated.
   *
   * @return array
   *   An array of bundles with non-default form modes.
   */
  public function getBundlesWithFormModes(string $entityTypeId): array {
    $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);
    $bundles = [];
    $formDisplays = $this->getActiveFormDisplays($entityTypeId);

    foreach ($formDisplays as $formDisplayId => $formDisplayEntityTypeId) {
      $bundle = explode('.', $formDisplayId)[1];
      if (!isset($bundleInfo[$bundle])) {
        continue;
      }

      $bundles[] = $bundle;
    }

    return array_unique($bundles);
  }

}
