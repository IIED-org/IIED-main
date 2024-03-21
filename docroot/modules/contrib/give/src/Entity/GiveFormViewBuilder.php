<?php

namespace Drupal\give\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a give form view builder.
 *
 * @see \Drupal\give\Entity\GiveForm
 */
class GiveFormViewBuilder implements EntityViewBuilderInterface, EntityHandlerInterface {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The give settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The give donation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $giveDonationStorage;

  /**
   * Constructs a new give form view builder.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\Config $config
   *   The give settings config object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $give_donation_storage
   *   The give donation storage.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer, Config $config, EntityStorageInterface $give_donation_storage) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
    $this->config = $config;
    $this->giveDonationStorage = $give_donation_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('renderer'),
      $container->get('config.factory')->get('give.settings'),
      $container->get('entity_type.manager')->getStorage('give_donation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $donation = $this->giveDonationStorage->create([
      'give_form' => $entity->id(),
    ]);

    $form = $this->entityFormBuilder->getForm($donation);
    $form['#title'] = $entity->label();
    $form['#cache']['contexts'][] = 'user.permissions';

    $this->renderer->addCacheableDependency($form, $this->config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = []) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = []) {
    throw new \LogicException();
  }

}
