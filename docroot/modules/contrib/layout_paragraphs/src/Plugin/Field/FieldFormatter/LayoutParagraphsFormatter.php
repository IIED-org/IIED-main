<?php

namespace Drupal\layout_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Cache\CacheableMetadata;

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
              $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
            }
            $entities[$delta] = $entity;
          }
        }
      }
    }

    return $entities;
  }

}
