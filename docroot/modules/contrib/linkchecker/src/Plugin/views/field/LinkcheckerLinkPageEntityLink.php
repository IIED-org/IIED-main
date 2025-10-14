<?php

namespace Drupal\linkchecker\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler that builds the page entity link for the linkchecker_link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("linkcheckerlink_page_entity_link")
 */
class LinkcheckerLinkPageEntityLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResultRow $values) {
    $linkchecker_link = parent::getEntity($values);
    if (!$linkchecker_link instanceof LinkCheckerLinkInterface) {
      return NULL;
    }

    if (!$linkchecker_link->hasField('entity_id')) {
      return NULL;
    }

    if ($linkchecker_link->get('entity_id')->isEmpty()) {
      return NULL;
    }

    $linked_entity = $linkchecker_link->get('entity_id')->entity;

    if (!$linked_entity instanceof EntityInterface) {
      return NULL;
    }

    while ($linked_entity instanceof ParagraphInterface && $linked_entity->getParentEntity() !== NULL) {
      $linked_entity = $linked_entity->getParentEntity();
    }

    try {
      $url = $linked_entity->toUrl();
    }
    catch (UndefinedLinkTemplateException | EntityMalformedException $e) {
      return NULL;
    }

    return $linked_entity;
  }

}
