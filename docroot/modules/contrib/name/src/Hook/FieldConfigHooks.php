<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\field\FieldConfigInterface;

/**
 * Hook implementations that track the preferred user name field in config.
 *
 * @internal
 */
final class FieldConfigHooks {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_create() for field_config entities.
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('field_config_create')] // @phpstan-ignore attribute.notFound
  public function fieldConfigCreate(FieldConfigInterface $entity): void {
    if ($entity->isSyncing()
      || $entity->getTargetEntityTypeId() !== 'user'
      || $entity->getTargetBundle() !== 'user'
      || $entity->getType() !== 'name'
    ) {
      return;
    }
    if ($this->configFactory->get('name.settings')->get('user_preferred') !== '') {
      return;
    }
    $this->configFactory
      ->getEditable('name.settings')
      ->set('user_preferred', $entity->getName())
      ->save();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for field_config entities.
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('field_config_delete')] // @phpstan-ignore attribute.notFound
  public function fieldConfigDelete(FieldConfigInterface $entity): void {
    if ($entity->isSyncing()
      || $entity->getTargetEntityTypeId() !== 'user'
      || $entity->getTargetBundle() !== 'user'
    ) {
      return;
    }
    if ($this->configFactory->get('name.settings')->get('user_preferred') !== $entity->getName()) {
      return;
    }
    $this->configFactory
      ->getEditable('name.settings')
      ->set('user_preferred', '')
      ->save();
  }

}
