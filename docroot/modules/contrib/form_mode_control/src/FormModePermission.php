<?php

namespace Drupal\form_mode_control;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for the form_mode_control module.
 */
class FormModePermission implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Constructs a new FormModePermission instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Determine all permissions that should be shown, and update config.
   *
   * @return array
   *   The permissions to show in the permissions form.
   */
  public function roleToFormMode() {
    // Initialising permissions.
    $permissions = [];

    // Load all form modes.
    /* @var \Drupal\Core\Entity\Entity\EntityFormDisplay[] $all_form_modes */
    $all_form_modes = $this->entityManager->getStorage('entity_form_display')
      ->loadMultiple();

    $to_add = [];
    $to_delete = [];

    foreach ($all_form_modes as $id_form_mode => $form_mode) {
      $machine_name_form_mode = explode('.', $id_form_mode);
      $entity_type = $machine_name_form_mode[0];
      $bundle = $machine_name_form_mode[1];
      $form_mode_id = $machine_name_form_mode[2];
      $permissions_key = 'use  The form mode ' . $form_mode_id . ' linked to  ' . $entity_type . ' entity( ' . $bundle . ' )';

      // If the form mode is disabled don't add it to the list and make sure it
      // is cleared from configuration.
      // TODO : ( && $form_mode_id != "default") voir si c'est possible.
      if ($form_mode->status() == FALSE || !$form_mode_id) {
        $to_delete[] = $id_form_mode;
      }
      else {
        // If the form mode is activated, we add a permission linked to this
        // form mode.
        $title = $this->t('Use the form mode %label_form_mode linked to %entity_type_id ( %bundle )', [
          '%label_form_mode' => $form_mode_id,
          '%entity_type_id' => form_mode_control_get_entity_type_label($entity_type),
          '%bundle' => form_mode_control_get_bundle_label($entity_type, $bundle),
        ]);

        $permissions[$permissions_key] = ['title' => $title];
        $to_add[$permissions_key] = $id_form_mode;
      }
    }

    if ($to_delete || $to_add) {
      // Load configuration.
      $configuration = \Drupal::configFactory()
        ->getEditable('form_mode_control.settings');

      foreach ($to_delete as $key) {
        $configuration->clear($key);
      }
      foreach ($to_add as $key => $value) {
        $configuration->set($key, $value);
      }

      $configuration->save(TRUE);
    }

    $permissions['access_all_form_modes'] = [
      'title' => $this->t('Access all form modes'),
      'description' => $this->t('To access to a form mode, you must add ?display=form_mode_searched,else a form mode default was launched by default.'),
    ];

    return $permissions;
  }

  /**
   * Clear a piece of data from this module's settings.
   *
   * @param string $data
   *   The parameter to clear.
   */
  protected function clearDataPermissions($data) {
    $configuration = \Drupal::configFactory()
      ->getEditable('form_mode_control.settings');

    foreach ($data as $id => $permission) {
      if (!EntityFormDisplay::load($id)) {
        $configuration->clear($id);
      }
    }
  }

}
