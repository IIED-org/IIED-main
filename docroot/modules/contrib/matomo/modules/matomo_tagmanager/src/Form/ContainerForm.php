<?php

declare(strict_types=1);

namespace Drupal\matomo_tagmanager\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Matomo Tag Manager container settings form.
 */
class ContainerForm extends EntityForm {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The container.
   *
   * @var \Drupal\matomo_tagmanager\Entity\ContainerInterface
   */
  protected $container;

  /**
   * The entity.
   *
   * @var \Drupal\matomo_tagmanager\Entity\ContainerInterface
   */
  protected $entity;

  /**
   * The prefix.
   *
   * @var string
   */
  protected $prefix;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matomo_tagmanager_container';
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The ConditionManager for building the insertion conditions.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(ExecutableManagerInterface $condition_manager, ContextRepositoryInterface $context_repository) {
    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\matomo_tagmanager\Entity\ContainerInterface $container */
    $container = $this->container = $this->entity;
    $this->prefix = '';

    // Store the contexts for other objects to use during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    // Build form elements.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Label',
      '#default_value' => $container->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $container->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'containerExists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ],
    ];

    $form['container_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Container URL'),
      '#description' => $this->t('The URL assigned by Matomo Tag Manager for this website container. To get a container, <a href="https://matomo.org/">sign up for Matomo</a> and create a Tag Manager container for your website or configure your self-hosted instance of Matomo accordingly.'),
      '#default_value' => $container->containerUrl(),
      '#required' => TRUE,
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => 'Weight',
      '#default_value' => $container->weight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => 'Delete',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    parent::save($form, $form_state);

    // Redirect to collection page.
    $form_state->setRedirect('entity.matomo_tagmanager_container.collection');
  }

  /**
   * Checks if a container machine name is taken.
   *
   * @param string $value
   *   The machine name.
   * @param array $element
   *   An array containing the structure of the 'id' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the container machine name is taken.
   */
  public function containerExists($value, array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $container */
    $container = $form_state->getFormObject()->getEntity();
    return (bool) $this->entityTypeManager->getStorage($container->getEntityTypeId())
      ->getQuery()
      ->condition($container->getEntityType()->getKey('id'), $value)
      ->accessCheck(TRUE)
      ->execute();
  }

}
