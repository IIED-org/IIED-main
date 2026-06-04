<?php

namespace Drupal\layout_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;

/**
 * Layout Paragraphs field formatter.
 *
 * @FieldFormatter(
 *   id = "layout_paragraphs",
 *   label = @Translation("Layout Paragraphs"),
 *   description = @Translation("Renders paragraphs with layout."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class LayoutParagraphsFormatter extends EntityReferenceRevisionsEntityFormatter implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityDisplayRepositoryInterface $entity_display_repository, EntityRepositoryInterface $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_display_repository);
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_display.repository'),
      $container->get('entity.repository')
    );
  }

  /**
   * Returns the referenced entities for display.
   *
   * See \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase::getEntitiesToView().
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = [];

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $item->entity;
        $access = $this->checkAccess($entity);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          // Only include root level components. Nested components are rendered
          // by their parent respective containers.
          // @see Drupal\layout_paragraphs\LayoutParagraphsRendererService.
          if (LayoutParagraphsComponent::isRootComponent($item->entity)) {
            // Set the entity in the correct language for display.
            if ($entity instanceof TranslatableInterface) {
              $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);
            }
            $entities[$delta] = $entity;
          }
        }
      }
    }

    return $entities;
  }

}
