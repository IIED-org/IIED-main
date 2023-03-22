<?php

namespace Drupal\linkchecker\Plugin\LinkStatusHandler;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\linkchecker\Plugin\LinkStatusHandlerBase;
use Psr\Http\Message\ResponseInterface;

/**
 * Unpublish entities on 404 link response.
 *
 * @LinkStatusHandler(
 *   id = "unpublish_404",
 *   label = @Translation("Unpublish on 404"),
 *   status_codes = {
 *     404,
 *   }
 * )
 */
class Unpublish404 extends LinkStatusHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function getItems(LinkCheckerLinkInterface $link, ResponseInterface $response) {
    // If unpublishing limit is reached,
    // unpublish all entities having this link.
    $actionStatusCode404 = $this->linkcheckerSetting->get('error.action_status_code_404');

    if ($actionStatusCode404 && $actionStatusCode404 <= $link->getFailCount()) {
      return parent::getItems($link, $response);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doHandle(LinkCheckerLinkInterface $link, ResponseInterface $response, FieldableEntityInterface $entity) {
    // If unpublishing limit is reached, unpublish entity having this link.
    $actionStatusCode404 = $this->linkcheckerSetting->get('error.action_status_code_404');

    if ($actionStatusCode404
      && $actionStatusCode404 <= $link->getFailCount()
      && $link->isExists()) {

      if ($entity instanceof EntityPublishedInterface && $entity->isPublished()) {
        $entity->setUnpublished();
        $entity->save();
      }
    }
  }

}
