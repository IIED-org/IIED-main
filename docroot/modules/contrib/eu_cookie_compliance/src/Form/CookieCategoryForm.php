<?php

namespace Drupal\eu_cookie_compliance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CookieCategoryForm.
 */
class CookieCategoryForm extends EntityForm {

  /**
   * The Cookie Category Storage Manager.
   *
   * @var \Drupal\eu_cookie_compliance\CategoryStorageManager
   */
  protected $categoryStorageManager;

  /**
   * Constructs a CookieCategoryForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->categoryStorageManager = $entityTypeManager->getStorage('cookie_category');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $cookie_category = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $cookie_category->label(),
      '#description' => $this->t("The name that will be shown to the website visitor."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $cookie_category->id(),
      '#machine_name' => [
        'exists' => '\Drupal\eu_cookie_compliance\Entity\CookieCategory::load',
      ],
      '#changeable_state' => !$cookie_category->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $cookie_category->get('description'),
      '#description' => $this->t("The description that will be shown to the website visitor."),
      '#required' => FALSE,
    ];

    $form['checkbox_default_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox default state'),
      '#description' => $this->t("Determines the default state of this category's selection checkbox on the cookie consent popup."),
      '#default_value' => $cookie_category->get('checkbox_default_state') ?: 'unchecked',
      '#options' => $this->categoryStorageManager->getCheckboxDefaultStateOptionsList(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $cookie_category = $this->entity;
    $status = $cookie_category->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addMessage($this->t('Created the %label Cookie category.', [
            '%label' => $cookie_category->label(),
          ]));
        break;

      default:
        $this->messenger()
          ->addMessage($this->t('Saved the %label Cookie category.', [
            '%label' => $cookie_category->label(),
          ]));
    }
    $form_state->setRedirectUrl($cookie_category->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    // There is no weight on the edit form. Fetch all configurable cookie
    // categories ordered by weight and set the new cookie to be placed
    // after them.
    if (empty($entity->getWeight())) {
      $entity->setWeight($this->categoryStorageManager->getCookieCategoryNextWeight());
    }
  }

}
