<?php

declare(strict_types=1);

namespace Drupal\masquerade\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Hook implementations integrating masquerade with the user entity UI.
 */
class MasqueradeUserHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $fields['user']['user']['display']['masquerade'] = [
      'label' => $this->t('Masquerade'),
      'description' => $this->t('Masquerade as user link.'),
      'weight' => 50,
    ];
    return $fields;
  }

  /**
   * Implements hook_ENTITY_TYPE_view().
   */
  #[Hook('user_view')]
  public function userView(array &$build, UserInterface $account, EntityViewDisplayInterface $display, $view_mode): void {
    if ($display->getComponent('masquerade')) {
      // Use post render to allow caching.
      $build['masquerade'] = [
        '#lazy_builder' => [
          'masquerade.callbacks:renderCacheLink', [
            $account->id(),
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }
  }

  /**
   * Implements hook_entity_type_alter().
   *
   * Adds useful link template to user entity.
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['user']->setLinkTemplate('masquerade', '/user/{user}/masquerade');
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];
    if ($entity->getEntityTypeId() === 'user') {
      if (masquerade_target_user_access($entity)) {
        $operations['masquerade'] = [
          'title' => $this->t('Masquerade as'),
          'weight' => 100,
          'url' => $entity->toUrl('masquerade'),
        ];
      }
    }
    return $operations;
  }

}
