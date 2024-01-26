<?php

namespace Drupal\ds\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current node as a context on entity routes.
 */
class DsBlockFieldEntityContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeRouteContext.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // We set these values at runtime in
    // \Drupal\ds\Plugin\DsField\BlockBase::build(),
    // so just create empty contexts.
    $result = [];
    $entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());
    foreach ($entity_type_ids as $entity_type_id) {
      $empty_entity = NULL;
      $context_definition = EntityContextDefinition::create($entity_type_id)->setRequired(FALSE);
      $context = new Context($context_definition, $empty_entity);

      $cacheability = new CacheableMetadata();
      // Not cacheable.
      $cacheability->setCacheMaxAge(0);
      $context->addCacheableDependency($cacheability);

      $result[$entity_type_id] = $context;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $contexts = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $contexts[$entity_type_id] = EntityContext::fromEntityTypeId($entity_type_id, $this->t('@type set by DS Block Field', ['@type' => $entity_type->getLabel()]));
    }
    return $contexts;
  }

}
