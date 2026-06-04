<?php

namespace Drupal\colorbox_load;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Render content in a colorbox.
 */
class Renderer implements MainContentRendererInterface {

  /**
   * Constructs a new Renderer.
   */
  public function __construct(
    protected RendererInterface $renderer,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();
    $content = $this->renderer->renderInIsolation($main_content);
    $response->setAttachments($main_content['#attached']);
    $response->addCommand(new OpenCommand($content));
    return $response;
  }

}
