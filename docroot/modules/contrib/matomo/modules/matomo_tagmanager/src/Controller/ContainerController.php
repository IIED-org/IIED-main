<?php

declare(strict_types=1);

namespace Drupal\matomo_tagmanager\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\matomo_tagmanager\Entity\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the Matomo Tag Manager container configuration entity type.
 */
class ContainerController extends EntityController {

  /**
   * Route title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the add entity page.
   */
  public function addTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Add @entity-type', ['@entity-type' => $entity_type->getSingularLabel()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface|null $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity edit page, if an entity was found.
   */
  public function editTitle(RouteMatchInterface $route_match, ?EntityInterface $_entity = NULL) {
    $entity = $this->doGetEntity($route_match, $_entity);
    if ($entity) {
      return $this->t('Edit %label container', ['%label' => $entity->label()]);
    }
  }

  /**
   * Enables a Container object.
   *
   * @param \Drupal\matomo_tagmanager\Entity\ContainerInterface $matomo_tagmanager_container
   *   The Container object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the matomo_tagmanager_container listing page.
   *
   * @todo The parameter name must match that used in routing.yml although the
   *   documentation suggests otherwise.
   */
  public function enable(ContainerInterface $matomo_tagmanager_container) {
    $matomo_tagmanager_container->enable()->save();
    return new RedirectResponse($matomo_tagmanager_container->toUrl('collection', ['absolute' => TRUE])->toString());
  }

  /**
   * Disables a Container object.
   *
   * @param \Drupal\matomo_tagmanager\Entity\ContainerInterface $matomo_tagmanager_container
   *   The Container object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the matomo_tagmanager_container listing page.
   */
  public function disable(ContainerInterface $matomo_tagmanager_container) {
    $matomo_tagmanager_container->disable()->save();
    return new RedirectResponse($matomo_tagmanager_container->toUrl('collection', ['absolute' => TRUE])->toString());
  }

}
