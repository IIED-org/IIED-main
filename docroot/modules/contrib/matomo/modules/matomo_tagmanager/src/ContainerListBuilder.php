<?php

declare(strict_types=1);

namespace Drupal\matomo_tagmanager;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\matomo_tagmanager\Entity\ContainerInterface;

/**
 * Defines a listing of Matomo Tag Manager container configuration entities.
 *
 * @see \Drupal\matomo_tagmanager\Entity\Container
 */
class ContainerListBuilder extends DraggableListBuilder {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'containers';

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Label');
    $header['entity_id'] = $this->t('Machine name');
    $header['container_url'] = $this->t('Container URL');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    \assert($entity instanceof ContainerInterface);
    $row = [];
    $row['label'] = $entity->label();
    $row['entity_id'] = ['#markup' => $entity->id()];
    $row['container_url'] = ['#markup' => $entity->containerUrl()];
    $row['status'] = ['#markup' => $entity->status() ? $this->t('enabled') : $this->t('disabled')];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'matomo_tagmanager_container_list';
  }

}
