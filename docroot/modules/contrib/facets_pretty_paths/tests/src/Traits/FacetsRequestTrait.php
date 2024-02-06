<?php

namespace Drupal\Tests\facets_pretty_paths\Traits;

use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * A trait for facets Kernel tests to generate requests with facets in them.
 */
trait FacetsRequestTrait {

  /**
   * Adds a dummy request and route to the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param string $route_title
   *   The title for the route.
   * @param string $filters
   *   The filters on the route.
   */
  public function pushRequest(RequestStack $request_stack, $route_title, $filters) {
    // We need to use a dummy, albeit existing route (in this case system.admin)
    // so that Url objects can be built for this route. Otherwise, calls to Url
    // will not work as the route does not exist.
    $route = new Route('admin/{facets_query}/{f0}/{f1}/{f2}/{f3}/{f4}/{f5}/{f6}/{f7}/{f8}/{f9}/{f10}/{f11}/{f12}/{f13}/{f14}/{f15}/{f16}/{f17}/{f18}/{f19}/{f20}/{f21}/{f22}/{f23}/{f24}/{f25}/{f26}/{f27}/{f28}/{f29}/{f30}/{f31}/{f32}/{f33}/{f34}/{f35}/{f36}/{f37}/{f38}');
    $route->setDefault('_title', $route_title);
    $route->setRequirement('facets_query', '.*');
    $route->setDefault('facets_query', '');

    $request = Request::create("admin/$filters");
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'system.admin');
    $request->attributes->set('facets_query', $filters);

    $request_stack->push($request);
  }

}
