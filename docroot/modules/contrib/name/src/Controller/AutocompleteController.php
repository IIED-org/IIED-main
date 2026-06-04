<?php

namespace Drupal\name\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\name\Service\AutocompleteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for name autocompletion routes.
 */
class AutocompleteController implements ContainerInjectionInterface {

  /**
   * The name autocomplete helper class to find matching name values.
   *
   * @var \Drupal\name\Service\AutocompleteInterface
   */
  protected AutocompleteInterface $nameAutocomplete;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an AutocompleteController object.
   *
   * @param \Drupal\name\Service\AutocompleteInterface $name_autocomplete
   *   The name autocomplete helper class to find matching name values.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity field manager.
   */
  public function __construct(AutocompleteInterface $name_autocomplete, EntityFieldManager $entityFieldManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->nameAutocomplete = $name_autocomplete;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('name.autocomplete'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns response for the name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $component
   *   The name component.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   *
   * @see \Drupal\name\Service\AutocompleteInterface::getMatches()
   */
  public function autocomplete(Request $request, $field_name, $entity_type, $bundle, $component) {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    if (!isset($definitions[$field_name])) {
      throw new AccessDeniedHttpException();
    }

    $field_definition = $definitions[$field_name];
    if ($field_definition->getType() != 'name') {
      throw new AccessDeniedHttpException();
    }

    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type);
    // A bare fieldAccess('edit') call without an entity falls back to "allow"
    // for most content entity field access handlers, which would leak stored
    // field values to any authenticated user with 'access content'. Gate the
    // endpoint on an actual ability to use the field: the user must be able
    // to create a new entity of this bundle (or edit any of them via bypass
    // permissions) before we enumerate stored values.
    if (!$access_control_handler->createAccess($bundle)) {
      throw new AccessDeniedHttpException();
    }
    if (!$access_control_handler->fieldAccess('edit', $field_definition)) {
      throw new AccessDeniedHttpException();
    }

    $matches = $this->nameAutocomplete->getMatches($field_definition, (string) $component, (string) $request->query->get('q'));
    // Core's autocomplete JS (jQuery UI) expects a list of {value, label}
    // objects, not an associative value => label map.
    $results = [];
    foreach ($matches as $value => $label) {
      $results[] = [
        'value' => (string) $value,
        'label' => (string) $label,
      ];
    }
    $response = new JsonResponse($results);
    // Results vary per user (entity view access) and must not be cached by
    // intermediaries or browsers.
    $response->setPrivate();
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->set('Vary', 'Cookie');
    return $response;
  }

}
