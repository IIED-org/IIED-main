<?php

namespace Drupal\form_mode_control;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for the form_mode_control module.
 */
class FormModePermission implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->configFactory = $container->get('config.factory');

    return $instance;
  }

  /**
   * Determine all permissions that should be shown.
   *
   * @return array
   *   The permissions to show in the permissions form.
   */
  public function getPermissions(): array {
    $permissions = [];
    $formDisplays = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->loadMultiple();

    foreach ($formDisplays as $formDisplay) {
      assert($formDisplay instanceof EntityFormDisplayInterface);

      if (!$formDisplay->status()) {
        continue;
      }

      $entityTypeId = $formDisplay->getTargetEntityTypeId();
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
      $bundleId = $formDisplay->getTargetBundle();
      $formModeId = $formDisplay->getMode();
      $permission = form_mode_control_get_permission_by_mode_and_role($entityTypeId, $bundleId, $formModeId);

      $title = $this->t('Use the form mode %label_form_mode linked to %entity_type_id ( %bundle )', [
        '%label_form_mode' => $formModeId,
        '%entity_type_id' => $entityType->getLabel(),
        '%bundle' => $this->entityTypeBundleInfo->getBundleInfo($entityTypeId)[$bundleId]['label'] ?? $bundleId,
      ]);

      $dependencies = [];
      if ($bundleEntityTypeId = $entityType->getBundleEntityType()) {
        $bundle = $this->entityTypeManager->getStorage($bundleEntityTypeId)->load($bundleId);
        $dependencies['config'][] = $bundle->getConfigDependencyName();
      }
      else {
        $dependencies['config'][] = $formDisplay->getConfigDependencyName();
      }

      $permissions[$permission] = [
        'title' => $title,
        'dependencies' => $dependencies,
      ];
    }

    $permissions['access_all_form_modes'] = [
      'title' => $this->t('Use all form modes'),
    ];

    return $permissions;
  }

}
