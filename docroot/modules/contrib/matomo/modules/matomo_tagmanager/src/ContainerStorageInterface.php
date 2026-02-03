<?php

declare(strict_types=1);

namespace Drupal\matomo_tagmanager;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Interface for Matomo Tag Manager container storage handler.
 */
interface ContainerStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Load all defined containers.
   *
   * @return \Drupal\matomo_tagmanager\Entity\ContainerInterface[]
   *   List of all containers ordered by weight, id.
   */
  public function loadAll(): array;

  /**
   * Load all enabled containers.
   *
   * @return \Drupal\matomo_tagmanager\Entity\ContainerInterface[]
   *   List of enabled containers ordered by weight, id.
   */
  public function loadEnabled(): array;

}
