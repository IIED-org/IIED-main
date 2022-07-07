<?php

namespace Drupal\media_pdf_thumbnail;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Pdf image entity entities.
 *
 * @ingroup media_pdf_thumbnail
 */
class PdfImageEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Pdf image entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\media_pdf_thumbnail\Entity\PdfImageEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.pdf_image_entity.edit_form',
      ['pdf_image_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
