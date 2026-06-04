<?php

namespace Drupal\taxonomy_menu\Controller;

use Drupal\taxonomy_menu\TaxonomyMenuInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of TaxonomyMenu.
 */
class TaxonomyMenuListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Taxonomy Menu');
    $header['id'] = $this->t('Machine name');
    $header['expanded'] = $this->t('Expanded');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $expanded = FALSE;
    if ($entity instanceof TaxonomyMenuInterface) {
      $expanded = $entity->get('expanded');
    }
    $row['expanded'] = $expanded ? $this->t('Yes') : $this->t('No');
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
