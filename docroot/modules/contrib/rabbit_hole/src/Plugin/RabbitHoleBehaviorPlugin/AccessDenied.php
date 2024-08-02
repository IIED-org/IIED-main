<?php

namespace Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Denies access to a page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "access_denied",
 *   label = @Translation("Access denied")
 * )
 */
class AccessDenied extends RabbitHoleBehaviorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity, Response $current_response = NULL) {
    throw new AccessDeniedHttpException();
  }

}
