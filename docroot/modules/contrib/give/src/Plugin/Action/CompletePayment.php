<?php

namespace Drupal\give\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Flag a payment (Check or bank transfer) as complete
 *
 * @Action(
 *   id = "give_donation_completed",
 *   label = @Translation("Complete a payment"),
 *   type = "give_donation"
 * )
 */
class CompletePayment extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->completed->value == TRUE;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);
    $result->andIf(AccessResult::allowedIf($object->completed->value == 0));
    return $return_as_object ? $result : $result->isAllowed();
  }
}
