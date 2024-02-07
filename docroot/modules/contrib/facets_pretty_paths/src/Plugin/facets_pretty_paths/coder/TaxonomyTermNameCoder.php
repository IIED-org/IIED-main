<?php

namespace Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets_pretty_paths\Coder\CoderPluginBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Taxonomy term name facets pretty paths coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "taxonomy_term_name_coder",
 *   label = @Translation("Taxonomy term name"),
 *   description = @Translation("Use term name, e.g. /color/<strong>blue</strong>")
 * )
 */
class TaxonomyTermNameCoder extends CoderPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a TaxonomyTermNameCoder object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\pathauto\AliasCleanerInterface $pathauto_alias_cleaner
   *   Provides an alias cleaner.
   * @param \Drupal\Core\Entity\EntityStorageInterface $term_storage
   *   The term storage.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasCleanerInterface $pathauto_alias_cleaner, EntityStorageInterface $term_storage, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasCleaner = $pathauto_alias_cleaner;
    $this->termStorage = $term_storage;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pathauto.alias_cleaner'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('entity.repository'),
    );
  }

  /**
   * Encode an id into an alias.
   *
   * @param string $id
   *   An entity id.
   *
   * @return string
   *   An alias.
   */
  public function encode($id) {
    $term = $this->termStorage->load($id);
    return $term instanceof TermInterface ? $this->aliasCleaner->cleanString($this->entityRepository->getTranslationFromContext($term)->label()) : $id;
  }

  /**
   * Decodes an alias back to an id.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   An id.
   */
  public function decode($alias) {
    $id_from_alias = '';
    // Get all terms from selected vocabularies when possible.
    $query = $this->termStorage->getQuery()->accessCheck(FALSE);
    $vids = $this->configuration['facet']->getDataDefinition()->getSetting('handler_settings')['target_bundles'] ?? NULL;
    if (!empty($vids)) {
      $query->condition('vid', $vids, 'IN');
    }
    $term_ids = $query->execute();

    foreach ($term_ids as $tid) {
      $term = $this->entityRepository->getTranslationFromContext($this->termStorage->load($tid));
      $encoded = $this->aliasCleaner->cleanString($term->label());
      if ($alias === $encoded) {
        $id_from_alias = $term->id();
        break;
      }
    }
    return $id_from_alias;
  }

}

