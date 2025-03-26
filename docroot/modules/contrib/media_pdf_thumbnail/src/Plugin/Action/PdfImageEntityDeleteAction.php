<?php

namespace Drupal\media_pdf_thumbnail\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Push term in front.
 *
 * @Action(
 *   id = "pdf_image_entity",
 *   label = @Translation("Delete PDF image entities"),
 *   type = "pdf_image_entity"
 * )
 */
class PdfImageEntityDeleteAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('delete', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
