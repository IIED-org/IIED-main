<?php

namespace Drupal\iied_utils\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\ResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Converts machine names (node types + vocabularies) to labels.
 *
 * @FacetsProcessor(
 *   id = "type_label_processor",
 *   label = @Translation("Type label processor"),
 *   description = @Translation("Convert combined machine names to human-readable labels."),
 *   stages = {
 *     "build" = 50
 *   },
 *   types = {
 *     "string"
 *   }
 * )
 */
class TypeLabelProcessor extends ProcessorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();

    $map = [];
    foreach ($node_types as $id => $type) {
      $map[$id] = $type->label();
    }
    foreach ($vocabs as $id => $vocab) {
      $map[$id] = $vocab->label();
    }

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $result) {
      $value = $result->getRawValue();
      if (isset($map[$value])) {
        $result->setDisplayValue($map[$value]);
      }
    }

    return $results;
  }

}
