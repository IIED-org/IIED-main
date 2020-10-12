<?php

namespace Drupal\delete_all\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\delete_all\Controller\ContentDeleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a Form for deleting all content.
 */
class ContentDelete extends FormBase {

  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->nodeType = $entityTypeManager->getStorage('node_type');
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
    return 'content_delete_all';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete All Type'),
      '#description' => $this->t('Delete all content of all type'),
    ];
    $form['type_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Node types'),
      '#description' => $this->t('Select the types of node content to delete'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="select_all"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['type_details']['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select The Node Type'),
      '#options' => $this->getAvailableNodeType(),
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
   * It return the availble Node type present in the system in key
   * value format for the select element to render the type.
   */
  public function getAvailableNodeType() {
    $contentTypes = $this->nodeType->loadMultiple();
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    return $contentTypesList;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delete_all_checkbox = $form_state->getValue(['select_all']);
    $all_content_type = array_keys($this->getAvailableNodeType());
    $selected_node_type = $form_state->getValue('node_type');
    $contentDeleteController = new ContentDeleteController();
    if ($delete_all_checkbox == 1) {
      $nodes_to_delete = $contentDeleteController->getContentToDelete($all_content_type);
    }
    else {
      $nodes_to_delete = $contentDeleteController->getContentToDelete([$selected_node_type]);
    }

    if ($nodes_to_delete) {
      $batch = $contentDeleteController->getContentDeleteBatch($nodes_to_delete);
      batch_set($batch);
    }
    else {
      $this->messenger()->addMessage($this->t('No node found'));
    }
  }

}
