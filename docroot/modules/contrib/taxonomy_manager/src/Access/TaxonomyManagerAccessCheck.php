<?php

declare(strict_types=1);

namespace Drupal\taxonomy_manager\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to taxonomy term routes.
 */
class TaxonomyManagerAccessCheck implements AccessInterface {

  /**
   * Check access to the routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Get the taxonomy vocabulary from the route. If there is not a taxonomy
    // vocabulary as a route param, get it from the taxonomy_term param.
    $taxonomyVocabulary = $route_match->getParameter('taxonomy_vocabulary')?->id() ?? $route_match->getParameter('taxonomy_term')?->bundle();

    $routeName = $route_match->getRouteName();
    switch ($routeName) {
      case "taxonomy_manager.admin_vocabulary.delete":
        if ($account->hasPermission('delete terms in ' . $taxonomyVocabulary)) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        break;

      case "taxonomy_manager.admin_vocabulary.move":
      case 'taxonomy_manager.taxonomy_term.edit':
        if ($account->hasPermission('edit terms in ' . $taxonomyVocabulary)) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        break;
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

}
