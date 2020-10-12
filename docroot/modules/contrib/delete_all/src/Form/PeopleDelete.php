<?php

namespace Drupal\delete_all\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\delete_all\Controller\UserDeleteController;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for user deleting option.
 */
class PeopleDelete extends FormBase {

  protected $roleType;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->roleType = $entityTypeManager->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_people_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all Users (Authenticated User)'),
      '#description' => $this->t('Delete all Users with any type of Role (except the uid = 1)'),
    ];
    $form['role_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Role types'),
      '#description' => $this->t('Select the types of role user to delete'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="select_all"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['role_details']['role_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select The Role Type'),
      '#options' => $this->getAvailableRoleType(),
      '#states' => [
        'visible' => [
          ':input[name="select_all"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
    ];
    return $form;
  }

  /**
   * It return the availble role type, into an associative array form.
   */
  public function getAvailableRoleType() {
    $userTypes = $this->roleType->loadMultiple();
    $userTypeList = [];
    foreach (array_slice($userTypes, 2) as $userType) {
      $userTypeList[$userType->id()] = $userType->label();
    }
    return $userTypeList;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delete_all_checkbox = $form_state->getValue(['select_all']);
    $selected_role = $form_state->getValue('role_type');
    $userDeleteController = new UserDeleteController();
    if ($delete_all_checkbox == 1) {
      $users_to_delete = $userDeleteController->getUserToDelete();
    }
    else {
      $users_to_delete = $userDeleteController->getUserToDelete([$selected_role]);
    }

    $batch = $userDeleteController->getUserDeleteBatch($users_to_delete);
    batch_set($batch);
  }

}
