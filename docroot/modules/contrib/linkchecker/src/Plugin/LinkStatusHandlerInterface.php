<?php

namespace Drupal\linkchecker\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines an interface for Link status handler plugins.
 */
interface LinkStatusHandlerInterface extends PluginInspectionInterface {

  /**
   * Handles a status code of link.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response of link checking.
   */
  public function handle(LinkCheckerLinkInterface $link, ResponseInterface $response);

  /**
   * Creates a queue for handling.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response of link checking.
   */
  public function queueItems(LinkCheckerLinkInterface $link, ResponseInterface $response);

}
