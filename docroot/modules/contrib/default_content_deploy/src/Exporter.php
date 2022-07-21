<?php

namespace Drupal\default_content_deploy;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\default_content_deploy\Event\PreSerializeEvent;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\Plugin\DataType\SectionData;
use Drupal\layout_builder\SectionComponent;

/**
 * A service for handling export of default content.
 */
class Exporter {

  /**
   * DCD Manager.
   *
   * @var \Drupal\default_content_deploy\DeployManager
   */
  protected $deployManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * DB connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Entity type ID.
   *
   * @var string
   */
  private $entityTypeId;

  /**
   * Type of a entity content.
   *
   * @var string
   */
  private $bundle;

  /**
   * Entity IDs for export.
   *
   * @var array
   */
  private $entityIds;

  /**
   * Directory to export.
   *
   * @var string
   */
  private $folder;

  /**
   * Entity IDs which needs skip.
   *
   * @var array
   */
  private $skipEntityIds;

  /**
   * Array of entity types and with there values for export.
   *
   * @var array
   */
  private $exportedEntities = [];

  /**
   * Type of export.
   *
   * @var string
   */
  private $mode;

  /**
   * Is remove old content.
   *
   * @var bool
   */
  private $forceUpdate;

  /**
   * @var \DateTimeInterface
   */
  private $dateTime;

  /**
   * The link manager service.
   *
   * @var \Drupal\hal\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Exporter constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   DB connection.
   * @param \Drupal\default_content_deploy\DeployManager $deploy_manager
   *   DCD Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   Serializer.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(Connection $database, DeployManager $deploy_manager, EntityTypeManagerInterface $entityTypeManager, Serializer $serializer, AccountSwitcherInterface $account_switcher, FileSystemInterface $file_system, LinkManagerInterface $link_manager, ContainerAwareEventDispatcher $eventDispatcher, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->serializer = $serializer;
    $this->accountSwitcher = $account_switcher;
    $this->deployManager = $deploy_manager;
    $this->fileSystem = $file_system;
    $this->linkManager = $link_manager;
    $this->eventDispatcher = $eventDispatcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Set entity type ID.
   *
   * @param string $entity_type
   *   Entity Type.
   *
   * @return \Drupal\default_content_deploy\Exporter
   */
  public function setEntityTypeId($entity_type) {
    $content_entity_types = $this->deployManager->getContentEntityTypes();

    if (!array_key_exists($entity_type, $content_entity_types)) {
      throw new \InvalidArgumentException(sprintf('Entity type "%s" does not exist', $entity_type));
    }

    $this->entityTypeId = (string) $entity_type;

    return $this;
  }

  /**
   * Set type of a entity content.
   *
   * @param string $bundle
   *  Bundle of the entity type.
   *
   * @return \Drupal\default_content_deploy\Exporter
   */
  public function setEntityBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * Set entity IDs for export.
   *
   * @param array $entity_ids
   *   The IDs of entity.
   *
   * @return \Drupal\default_content_deploy\Exporter
   */
  public function setEntityIds(array $entity_ids) {
    $this->entityIds = $entity_ids;
    return $this;
  }

  /**
   * Set entity IDs which needs skip.
   *
   * @param array $skip_entity_ids
   *   The IDs of entity for skip.
   *
   * @return $this
   */
  public function setSkipEntityIds(array $skip_entity_ids) {
    $this->skipEntityIds = $skip_entity_ids;
    return $this;
  }

  /**
   * Set type of export.
   *
   * @param string $mode
   *  Value type of export.
   *
   * @return \Drupal\default_content_deploy\Exporter
   *
   * @throws \Exception
   */
  public function setMode($mode) {
    $available_modes = ['all', 'reference', 'default'];

    if (in_array($mode, $available_modes)) {
      $this->mode = $mode;
    }
    else {
      throw new \Exception('The selected mode is not available');
    }

    return $this;
  }

  /**
   * Is remove old content.
   *
   * @param bool $is_update
   *
   * @return \Drupal\default_content_deploy\Exporter
   */
  public function setForceUpdate(bool $is_update) {
    $this->forceUpdate = $is_update;
    return $this;
  }

  /**
   * @param \DateTimeInterface $date_time
   *
   * @return \Drupal\default_content_deploy\Exporter
   */
  public function setDateTime(\DateTimeInterface $date_time) {
    $this->dateTime = $date_time;
    return $this;
  }

  /**
   * @return \DateTimeInterface|null
   */
  public function getDateTime() {
    return $this->dateTime;
  }

  /**
   * @return int
   */
  public function getTime() {
    return $this->dateTime ? $this->dateTime->getTimestamp() : 0;
  }

  /**
   * Set directory to export.
   *
   * @param string $folder
   *   The content folder.
   *
   * @return \Drupal\default_content_deploy\Exporter
   */
  public function setFolder(string $folder) {
    $this->folder = $folder;
    return $this;
  }

  /**
   * Get directory to export.
   *
   * @return string
   *   The content folder.
   *
   * @throws \Exception
   */
  protected function getFolder() {
    return $this->folder ?: $this->deployManager->getContentFolder();
  }

  /**
   * Array with entity types for display result.
   *
   * @return array
   *   Array with entity types.
   */
  public function getResult() {
    return $this->exportedEntities;
  }

  /**
   * Export entities by entity type, id or bundle.
   *
   * @return \Drupal\default_content_deploy\Exporter
   *
   * @throws \Exception
   */
  public function export() {
    switch ($this->mode) {
      case 'default':
        $this->prepareToExport();
        break;

      case 'reference':
        $this->prepareToExportWithReference();
        break;

      case 'all':
        $this->prepareToExportAllContent();
        break;
    }

    // Edit and export all entities to folder.
    $this->editEntityData();
    $this->writeConfigsToFolder();

    return $this;
  }

  /**
   * Prepare content to export.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function prepareToExport() {
    $entity_type = $this->entityTypeId;
    $exported_entity_ids = $this->getEntityIdsForExport();

    if ($this->forceUpdate) {
      $this->fileSystem->deleteRecursive($this->getFolder());
    }

    foreach ($exported_entity_ids as $entity_id) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      $exported_entity = $this->getSerializedContent($entity);
      $this->addExportedEntity($exported_entity);
    }
  }

  /**
   * Prepare content with reference to export.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function prepareToExportWithReference() {
    $entity_type = $this->entityTypeId;
    $exported_entity_ids = $this->getEntityIdsForExport();

    if ($this->forceUpdate) {
      $this->fileSystem->deleteRecursive($this->getFolder());
    }

    foreach ($exported_entity_ids as $entity_id) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      $exported_entities = $this->getSerializedContentWithReferences($entity);
      $this->addExportedEntity($exported_entities);
    }
  }

  /**
   * Prepare all content on the site to export.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function prepareToExportAllContent() {
    $content_entity_types = $this->deployManager->getContentEntityTypes();

    if ($this->forceUpdate) {
      $this->fileSystem->deleteRecursive($this->getFolder());
    }

    $time = $this->getTime();

    foreach ($content_entity_types as $entity_type => $label) {
      // Skip specified entities in --skip_entity_type option.
      if (!$this->skipEntityIds || !in_array($entity_type, $this->skipEntityIds)) {
        $this->setEntityTypeId($entity_type);
        $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
        $query->accessCheck(FALSE);
        $entity_ids = array_values($query->execute());

        foreach ($entity_ids as $entity_id) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);

          if ($time && $entity instanceof EntityChangedInterface && $entity->getChangedTimeAcrossTranslations() < $time) {
            continue;
          }

          $exported_entity = $this->getSerializedContent($entity);
          $this->addExportedEntity($exported_entity);
        }
      }
    }
  }

  /**
   * Get all entity IDs for export.
   *
   * @return array
   *   Return array of entity ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getEntityIdsForExport() {
    $skip_entities = $this->skipEntityIds;
    $entity_ids = $this->entityIds;
    $entity_type = $this->entityTypeId;
    $entity_bundle = $this->bundle;
    $key_bundle = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');

    // If the Entity IDs option is null then load all IDs.
    if (empty($entity_ids)) {
      $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
      $query->accessCheck(FALSE);

      if ($entity_bundle) {
        $query->condition($key_bundle, $entity_bundle);
      }

      $entity_ids = $query->execute();
    }

    // Remove skipped entities from $exported_entity_ids.
    if (!empty($skip_entities)) {
      $entity_ids = array_diff($entity_ids, $skip_entities);
    }

    return $entity_ids;
  }

  /**
   * Add array with entity info for export.
   *
   * @param $exported_entity
   *   Entity info.
   *
   * @return $this
   */
  private function addExportedEntity($exported_entity) {
    if ($exported_entity) {
      $exported_entity_array = [];

      if (is_string($exported_entity)) {
        $entity = $this->serializer->decode($exported_entity, 'hal_json');
        $uuid = $entity['uuid'][0]['value'];

        $exported_entity_array[$this->entityTypeId][$uuid] = $exported_entity;
      }
      elseif (is_array($exported_entity)) {
        $exported_entity_array = $exported_entity;
      }

      if ($exported_entity_array) {
        $this->exportedEntities = array_replace_recursive($this->exportedEntities, $exported_entity_array);
      }
    }

    return $this;
  }

  /**
   * Writes an array of serialized entities to a given folder.
   *
   * @return $this
   *
   * @throws \Exception
   */
  private function writeConfigsToFolder() {
    foreach ($this->exportedEntities as $entity_type => $serialized_entities) {
      // Ensure that the folder per entity type exists.
      $entity_type_folder = "{$this->getFolder()}/{$entity_type}";
      $this->fileSystem->prepareDirectory($entity_type_folder, FileSystemInterface::CREATE_DIRECTORY);

      foreach ($serialized_entities as $uuid => $serialized_entity) {
        file_put_contents("{$entity_type_folder}/{$uuid}.json", $serialized_entity);
      }
    }

    return $this;
  }

  /**
   * Remove or add a new fields to serialize entities data.
   */
  private function editEntityData() {
    foreach ($this->exportedEntities as $entity_type => $uuids) {
      foreach ($uuids as $uuid => $serialisation_entity) {
        $entity_array = $this->serializer->decode($serialisation_entity, 'hal_json');
        $entity_type_object = $this->entityTypeManager->getDefinition($entity_type);
        $id_key = $entity_type_object->getKey('id');
        $entity_id = $entity_array[$id_key][0]['value'];
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);

        // Removed data.
        unset($entity_array[$entity_type_object->getKey('revision')]);

        // Add data.
        if ($entity_type === 'user') {
          $entity_array['pass'][0]['value'] = $entity->getPassword();
        }

        $data = $this->serializer->serialize($entity_array, 'hal_json', [
          'json_encode_options' => JSON_PRETTY_PRINT
        ]);

        $this->exportedEntities[$entity_type][$uuid] = $data;
      }
    }
  }

  /**
   * Exports a single entity as importContent expects it.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return string
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSerializedContent(ContentEntityInterface $entity) {
    $content = '';

    $event = new PreSerializeEvent($entity, $this->mode);
    $this->eventDispatcher->dispatch($event);
    $entity = $event->getEntity();

    if ($entity) {
      if (PHP_SAPI === 'cli') {
        $root_user = $this->entityTypeManager->getStorage('user')->load(1);
        $this->accountSwitcher->switchTo($root_user);
      }

      $host = $this->deployManager->getCurrentHost();
      $this->linkManager->setLinkDomain($host);
      $content = $this->serializer->serialize($entity, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);
      $this->linkManager->setLinkDomain(FALSE);

      if (PHP_SAPI === 'cli') {
        $this->accountSwitcher->switchBack();
      }
    }

    return $content;
  }

  /**
   * Exports a single entity and all its referenced entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSerializedContentWithReferences(ContentEntityInterface $entity) {
    $indexed_dependencies = [$entity->uuid() => $entity];
    $entities = $this->getEntityReferencesRecursive($entity, 0, $indexed_dependencies);
    $serialized_entities = [];

    // Serialize all entities and key them by entity TYPE and uuid.
    foreach ($entities as $referenced_entity) {
      $serialize = $this->serializer->serialize($entity, 'hal_json', [
        'json_encode_options' => JSON_PRETTY_PRINT,
      ]);

      $serialized_entities[$entity->getEntityTypeId()][$entity->uuid()] = $this->getSerializedContent($referenced_entity);
    }

    return $serialized_entities;
  }

  /**
   * Returns all layout builder referenced blocks of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Keyed array of entities indexed by entity type and ID.
   */
  private function getEntityLayoutBuilderDependencies(ContentEntityInterface $entity) {
    $entity_dependencies = [];

    if ($this->moduleHandler->moduleExists('layout_builder')) {
      // Gather a list of referenced entities, modeled after ContentEntityBase::referencedEntities().
      foreach ($entity->getFields() as $field_key => $field_items) {
        foreach ($field_items as $field_item) {
          // Loop over all properties of a field item.
          foreach ($field_item->getProperties(TRUE) as $property) {
            // Look only at LayoutBuilder SectionData fields.
            if ($property instanceof SectionData) {
              $section = $property->getValue();
              if ($section instanceof Section) {
                // Get list of components inside the LayoutBuilder Section.
                $components = $section->getComponents();
                foreach ($components as $component_uuid => $component) {
                  // Gather components of type "inline_block:html_block", by block revision_id.
                  if ($component instanceof SectionComponent) {
                    $configuration = $component->get('configuration');
                    if ($configuration['id'] === 'inline_block:html_block' && !empty($configuration['block_revision_id'])) {
                      $block_revision_id = $configuration['block_revision_id'];
                      $block_revision = \Drupal::entityTypeManager()
                        ->getStorage('block_content')
                        ->loadRevision($block_revision_id);
                      $entity_dependencies[] = $block_revision;
                    }
                    // Gather components of type 'block_content:*', by uuid.
                    else {
                      if (substr($configuration['id'], 0, 14) === 'block_content:') {
                        if ($block_uuid = substr($configuration['id'], 14)) {
                          $block_loaded_by_uuid = \Drupal::entityTypeManager()
                            ->getStorage('block_content')
                            ->loadByProperties(['uuid' => $block_uuid]);
                          $entity_dependencies[] = reset($block_loaded_by_uuid);
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return $entity_dependencies;
  }

  /**
   * Returns all referenced entities of an entity.
   *
   * This method is also recursive to support use-cases like a node -> media
   * -> file.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param int $depth
   *   Guard against infinite recursion.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $indexed_dependencies
   *   Previously discovered dependencies.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Keyed array of entities indexed by entity type and ID.
   */
  private function getEntityReferencesRecursive(ContentEntityInterface $entity, $depth = 0, array &$indexed_dependencies = []) {
    $entity_dependencies = $entity->referencedEntities();
    $entity_layout_builder_dependencies = $this->getEntityLayoutBuilderDependencies($entity);
    $entity_dependencies = array_merge($entity_dependencies, $entity_layout_builder_dependencies);

    foreach ($entity_dependencies as $dependent_entity) {
      // Config entities should not be exported but rather provided by default
      // config.
      if (!($dependent_entity instanceof ContentEntityInterface)) {
        continue;
      }

      // Using UUID to keep dependencies unique to prevent recursion.
      $key = $dependent_entity->uuid();
      if (isset($indexed_dependencies[$key])) {
        // Do not add already indexed dependencies.
        continue;
      }

      $indexed_dependencies[$key] = $dependent_entity;
      // Build in some support against infinite recursion.
      if ($depth < 6) {
        $indexed_dependencies += $this->getEntityReferencesRecursive($dependent_entity, $depth + 1, $indexed_dependencies);
      }
    }

    return $indexed_dependencies;
  }

}
