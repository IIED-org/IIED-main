<?php

namespace Drupal\jsonapi_views\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_views\Resource\ViewsResource;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * Each Views view and display comnbination will result in
 * a jsonapi resource at: /{jsonapi_namespace}/views/{view_id}/{display_id}
 */
class Routes implements ContainerInjectionInterface {

  const RESOURCE_NAME = ViewsResource::class;

  const JSONAPI_RESOURCE_KEY = '_jsonapi_resource';
  const JSONAPI_RESOURCE_TYPES_KEY = '_jsonapi_resource_types';
  const VIEW_KEY = 'view';
  const DISPLAY_KEY = 'display';

  /**
   * Resource type bundle repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Entity type bundle info interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $jsonapi_views_routes = new RouteCollection();
    $base_path = '/%jsonapi%/views';
    $views = Views::getEnabledViews();

    foreach ($views as $view) {
      $view_name = $view->id();

      $entity_type = $view->getExecutable()->getBaseEntityType();

      if (!$entity_type) {
        continue;
      }
      $entity_type = $entity_type->id();
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      $bundles = array_keys($bundle_info);
      $resource_types = array_map(function ($bundle) use ($entity_type) {
        return $this->resourceTypeRepository->get($entity_type, $bundle)->getTypeName();
      }, $bundles);

      if (empty($resource_types)) {
        continue;
      }

      // Create routes for each display.
      foreach ($view->get('display') as $display) {
        $display_id = $display['id'];

        $views_display_route = new Route(implode('/', [
          $base_path,
          $view_name,
          $display_id,
        ]));
        $views_display_route->addDefaults([
          static::JSONAPI_RESOURCE_KEY => static::RESOURCE_NAME,
          static::JSONAPI_RESOURCE_TYPES_KEY => $resource_types,
          static::VIEW_KEY => $view->id(),
          static::DISPLAY_KEY => $display_id,
        ]);

        $jsonapi_views_routes->add("jsonapi_views.$view_name.$display_id", $views_display_route);
      }
    }

    $jsonapi_views_routes->addRequirements(['_access' => 'TRUE']);
    return $jsonapi_views_routes;
  }

}
