<?php

namespace Drupal\media_file_delete\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\media_file_delete\EntityHandler\MediaFileDeleteFileAccessControlHandler;
use Drupal\media_file_delete\Form\MediaDeleteForm;
use Drupal\media_file_delete\Form\MediaDeleteMultipleForm;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_file_delete.
 */
class MediaFileDeleteHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) {
    if (!empty($entity_types['media'])) {
      assert($entity_types['media'] instanceof EntityTypeInterface);
      $entity_types['media']->setFormClass('delete', MediaDeleteForm::class);
      $entity_types['media']->setFormClass('delete-multiple-confirm', MediaDeleteMultipleForm::class);
    }
    if (!empty($entity_types['file'])) {
      assert($entity_types['file'] instanceof EntityTypeInterface);
      $entity_types['file']->setAccessClass(MediaFileDeleteFileAccessControlHandler::class);
    }
  }

}
