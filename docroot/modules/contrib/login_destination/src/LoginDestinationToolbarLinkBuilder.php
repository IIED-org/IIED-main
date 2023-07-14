<?php

namespace Drupal\login_destination;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\ToolbarLinkBuilder;

/**
 * ToolbarLinkBuilder fills out the placeholders generated in user_toolbar().
 */
class LoginDestinationToolbarLinkBuilder extends ToolbarLinkBuilder {

  /**
   * The decorated service.
   *
   * @var \Drupal\user\ToolbarLinkBuilder
   */
  protected $innerService;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\user\ToolbarLinkBuilder $inner_service
   *   The decorated service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(ToolbarLinkBuilder $inner_service, CurrentPathStack $current_path, AccountProxyInterface $account) {
    $this->innerService = $inner_service;
    $this->currentPath = $current_path;
    parent::__construct($account);
  }

  /**
   * Pass any undefined method calls onto the inner service.
   *
   * @param string $method
   *   The method being called.
   * @param array $args
   *   The arguments passed to the method.
   *
   * @return mixed
   *   The inner services response.
   */
  public function __call($method, array $args = []) {
    return call_user_func_array([$this->innerService, $method], $args);
  }

  /**
   * Lazy builder callback for rendering toolbar links.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderToolbarLinks() {
    $build = $this->innerService->renderToolbarLinks();

    if ($this->account->getAccount()->isAuthenticated()) {
      $url = &$build['#links']['logout']['url'];

      $current = $this->currentPath->getPath();

      // Add current param to be able to evaluate previous page.
      $url->setOptions(['query' => ['current' => $current]]);
    }

    return $build;
  }

}
