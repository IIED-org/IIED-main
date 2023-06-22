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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new HtmlRenderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();
    $content = $this->renderer->renderPlain($main_content);
    $response->setAttachments($main_content['#attached']);
    $response->addCommand(new OpenCommand($content));
    return $response;
  }

}
