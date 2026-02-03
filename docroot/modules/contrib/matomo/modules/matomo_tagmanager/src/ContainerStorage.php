<?php

declare(strict_types=1);

namespace Drupal\matomo_tagmanager;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines the Matomo Tag Manager container storage handler class.
 */
class ContainerStorage extends ConfigEntityStorage implements ContainerStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadAll(): array {
    $query = $this->getQuery()
      ->sort('weight')
      ->sort('id');

    $ids = $query->execute();
    return $this->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadEnabled(): array {
    $query = $this->getQuery()
      ->condition('status', TRUE)
      ->sort('weight')
      ->sort('id');

    $ids = $query->execute();
    return $this->loadMultiple($ids);
  }

}
