<?php

namespace Drupal\form_mode_control;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
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
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * @return array
   */
  public function roleToFormMode() {
    //Initialising permissions.
    $permissions = [];
    //Load all form modes.
    $all_form_modes = $this->entityManager->getStorage('entity_form_display')
      ->loadMultiple();
    //Load configuration.
    $configuration = \Drupal::configFactory()
      ->getEditable('form_mode_control.settings');

    foreach ($all_form_modes as $id_form_mode => $form_mode) {
      $machine_name_form_mode = explode('.', $id_form_mode);
      $entity_type = $machine_name_form_mode[0];
      $bundle = $machine_name_form_mode[1];
      $form_mode_id = $machine_name_form_mode[2];
      // if the form mode is activated
      // TODO : ( && $form_mode_id != "default") voir si c'est possible
      if ($form_mode->status() == TRUE && $form_mode_id) {
        //If the form mode is activated, we add a permission linked to this form mode.
        $title = $this->t('Use  The form mode %label_form_mode linked to %entity_type_id ( %bundle ) ', array(
          '%label_form_mode' => $form_mode_id,
          '%entity_type_id' => getLabelEntityType($entity_type),
          '%bundle' => getLabelBundle($entity_type, $bundle),
        ));
        $permissions['use  The form mode ' . $form_mode_id . ' linked to  ' . $entity_type . ' entity( ' . $bundle . ' )'] = [
          'title' => $title,
        ];
        // Delete id
        if (EntityFormDisplay::load($id_form_mode)->status() == FALSE) {
          \Drupal::configFactory()
            ->getEditable('form_mode_control.settings')->clear($id_form_mode);
          //unset($permissions['use  The form mode ' . $form_mode_id . ' linked to  ' . $entity_type . ' entity( '.$bundle. ' )']);
        }
        $separate = ".";
        $id = "$entity_type$separate$bundle$separate$form_mode_id";
        if (EntityFormDisplay::load($id) == NULL) {
          \Drupal::configFactory()
            ->getEditable('form_mode_control.settings')->clear($id);
          unset($permissions['use  The form mode ' . $form_mode_id . ' linked to  ' . $entity_type . ' entity( ' . $bundle . ' )']);
        }
        $separate = ".";
        $id = "$entity_type$separate$bundle$separate$form_mode_id";
        //Saving configurations.
        $permissions_ = 'use  The form mode ' . $form_mode_id . ' linked to  ' . $entity_type . ' entity( ' . $bundle . ' )';
        $configuration->set($permissions_, $id)->save(TRUE);
      }
    }
    $permissions['access_all_form_modes'] = [
      'title' => $this->t('Access all form modes'),
      'description' => $this->t('To access to a form mode, you must add ?display=form_mode_searched,else a form mode default was launched by default.'),
    ];
    return $permissions;
  }

  /**
   * @param $data
   */
  protected function clearDataPermissions($data) {
    foreach ($data as $id => $permission) {
      if (!EntityFormDisplay::load($id)) {
        \Drupal::configFactory()
          ->getEditable('form_mode_control.settings')->clear($id);
      }
    }
  }
}
