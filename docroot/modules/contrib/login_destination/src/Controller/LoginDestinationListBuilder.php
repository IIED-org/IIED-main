<?php

namespace Drupal\login_destination\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Login Destinations rules.
 */
class LoginDestinationListBuilder extends DraggableListBuilder {

  /**
   * The key to use for the form element containing the entities.
   *
   * @var string
   */
  protected $entitiesKey = 'login_destination';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_destination_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'destination' => $this->t('Destination'),
      'triggers' => $this->t('Triggers'),
      'pages' => $this->t('Pages'),
      'language' => $this->t('Language'),
      'roles' => $this->t('Roles'),
      'enabled' => $this->t('Enabled'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\login_destination\Entity\LoginDestination $entity */
    $row['label'] = $entity->label();

    if ($entity->isDestinationCurrent()) {
      $row['destination'] = [
        '#markup' => $entity->viewDestination(),
      ];
    }
    else {
      $row['destination'] = [
        '#type' => 'link',
        '#title' => $entity->viewDestination(),
        '#url' => Url::fromUri($entity->getDestination()),
      ];
    }
    $row['triggers'] = [
      '#markup' => $entity->viewTriggers(),
    ];
    $row['pages'] = [
      '#markup' => $entity->viewPages(),
    ];
    $row['language'] = [
      '#markup' => $entity->getLanguage() != "" ? $entity->getLanguage() : $this->t('All languages'),
    ];
    $row['roles'] = [
      '#markup' => $entity->viewRoles(),
    ];
    $row['enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity->isEnabled(),
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    foreach ($form_state->getValue($this->entitiesKey) as $id => $value) {
      // Save entity only when its weight was changed.
      $this->entities[$id]->set('enabled', $value['enabled']);
      $this->entities[$id]->save();
    }
  }

}
