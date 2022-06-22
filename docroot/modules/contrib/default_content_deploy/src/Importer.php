<?php

namespace Drupal\default_content_deploy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Rogervila\ArrayDiffMultidimensional;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 *
 * The importContent() method is almost duplicate of
 *   \Drupal\default_content\Importer::importContent with injected code for
 *   content update. We are waiting for better DC code structure in a future.
 */
class Importer {

  /**
   * Deploy manager.
   *
   * @var \Drupal\default_content_deploy\DeployManager
   */
  protected $deployManager;

  /**
   * Scanned files.
   *
   * @var object[]
   */
  private $files;

  /**
   * Directory to import.
   *
   * @var string
   */
  private $folder;

  /**
   * Data to import.
   *
   * @var array
   */
  private $dataToImport = [];

  /**
   * Is remove changes of an old content.
   *
   * @var bool
   */
  protected $forceOverride;

  /**
   * The Entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The cache data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Memoization for references that have already been discovered.
   *
   * @var array
   */
  protected $discoveredReferences = [];

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The link manager service.
   *
   * @var \Drupal\hal\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * DCD Exporter.
   *
   * @var \Drupal\default_content_deploy\Exporter
   */
  protected $exporter;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var array
   */
  protected $entityLookup = [];

  /**
   * @var array
   */
  protected $entityIdLookup = [];

  /**
   * Constructs the default content deploy manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   * @param \Drupal\default_content_deploy\DeployManager $deploy_manager
   *   Deploy manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The Entity repository manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache data.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Serializer $serializer, EntityTypeManagerInterface $entity_type_manager, LinkManagerInterface $link_manager, AccountSwitcherInterface $account_switcher, DeployManager $deploy_manager, EntityRepositoryInterface $entity_repository, CacheBackendInterface $cache, Exporter $exporter, Connection $database) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->linkManager = $link_manager;
    $this->accountSwitcher = $account_switcher;
    $this->deployManager = $deploy_manager;
    $this->entityRepository = $entity_repository;
    $this->cache = $cache;
    $this->exporter = $exporter;
    $this->database = $database;
  }

  /**
   * Is remove changes of an old content.
   *
   * @param bool $is_override
   *
   * @return \Drupal\default_content_deploy\Importer
   */
  public function setForceOverride(bool $is_override) {
    $this->forceOverride = $is_override;
    return $this;
  }

  /**
   * Set directory to import.
   *
   * @param string $folder
   *   The content folder.
   *
   * @return \Drupal\default_content_deploy\Importer
   */
  public function setFolder(string $folder) {
    $this->folder = $folder;
    return $this;
  }

  /**
   * Get directory to import.
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
   * Get Imported data result.
   *
   * @return array
   */
  public function getResult() {
    return $this->dataToImport;
  }

  /**
   * Import data from JSON and create new entities, or update existing.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function prepareForImport() {
    // @todo remove because of changes in core >= 9.2
    $this->cache->delete('hal:links:relations');
    $this->files = $this->scan($this->getFolder());

    foreach ($this->files as $file) {
      $uuid = str_replace('.json', '', $file->name);

      if (!isset($this->dataToImport[$uuid])) {
        $this->decodeFile($file);
      }
    }

    return $this;
  }

  /**
   * Returns a list of file objects.
   *
   * @param string $directory
   *   Absolute path to the directory to search.
   *
   * @return object[]
   *   List of stdClass objects with name and uri properties.
   */
  public function scan($directory) {
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($directory, $flags);
    $iterator = new \RecursiveIteratorIterator($directory_iterator);
    $files = [];

    /* @var \SplFileInfo $file_info */
    foreach ($iterator as $file_info) {
      // Skip directories and non-json files.
      if ($file_info->isDir() || $file_info->getExtension() !== 'json') {
        continue;
      }

      $file = new \stdClass();
      $file->name = $file_info->getFilename();
      $file->uri = $file_info->getPathname();
      $files[$file->uri] = $file;
    }

    return $files;
  }

  /**
   * Import to entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import() {
    $files = $this->dataToImport;

    if (PHP_SAPI === 'cli') {
      $root_user = $this->entityTypeManager->getStorage('user')->load(1);
      $this->accountSwitcher->switchTo($root_user);
    }

    // All entities with entity references will be imported two times to ensure
    // that all entity references are present and valid. Path aliases will be
    // imported last to have a chance to rewrite them to the new ids of newly
    // created entities.
    for ($i = 0; $i <= 2; $i++) {
      foreach ($files as $uuid => &$file) {
        $entity_type = $file['entity_type_id'];
        if ($file['status'] !== 'skip') {
          if (
            ($i !== 2 && $entity_type === 'path_alias') ||
            ($i === 2 && $entity_type !== 'path_alias')
          ) {
            continue;
          }
          $this->linkManager->setLinkDomain($this->getLinkDomain($file));
          $class = $this->entityTypeManager->getDefinition($entity_type)->getClass();
          $this->preDenormalize($file, $entity_type);

          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $entity = $this->serializer->denormalize($file['data'], $class, 'hal_json', ['request_method' => 'POST']);
          $entity->enforceIsNew($file['is_new']);
          $entity->save();
          $this->entityIdLookup[$uuid] = $entity->id();

          if ($entity_type === 'user') {
            // Workaround: store the hashed password directly in the database
            // and avoid the entity API which doesn't provide support for
            // setting password hashes directly.
            $hashed_pass = $file['data']['pass'][0]['value'] ?? FALSE;
            if ($hashed_pass) {
              $this->database->update('users_field_data')
                ->fields([
                  'pass' => $hashed_pass,
                ])
                ->condition('uid', $entity->id(), '=')
                ->execute();
            }
          }

          if (empty($file['references']) || $i === 1) {
            // Don't handle entities without references twice. Don't handle
            // entities with references again in the third run for path aliases.
            unset($files[$uuid]);
          }
          else {
            // In the second run new entities should be updated.
            $file['status'] = 'update';
            $file['is_new'] = FALSE;
            $file['data'][$file['key_id']][0]['value'] = $entity->id();
          }
        }
        elseif ($i === 0) {
          // Get the entity ID of a skipped referenced item in the first run to
          // enable a target ID correction in referencing entities in the second
          // and third run.
          $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
          $this->entityIdLookup[$uuid] = $entity->id();
        }
      }
      unset($file);
    }

    // @todo is this still needed?
    $this->linkManager->setLinkDomain(FALSE);

    if (PHP_SAPI === 'cli') {
      $this->accountSwitcher->switchBack();
    }
  }

  /**
   * Gets url from file for set to Link manager.
   *
   * @param array $file
   */
  protected function getLinkDomain($file) {
    $link = $file['data']['_links']['type']['href'];
    $url_data = parse_url($link);
    $host = "{$url_data['scheme']}://{$url_data['host']}";
    return (!isset($url_data['port'])) ? $host : "{$host}:{$url_data['port']}";
  }

  /**
   * Prepare file to import.
   *
   * @param $file
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  protected function decodeFile($file) {
    // Check that this file has not been decoded already.
    if (array_key_exists($file->name, $this->discoveredReferences)) {
      return $this;
    }

    // Get parsed data.
    $parsed_data = file_get_contents($file->uri);

    // Decode.
    $decode = $this->serializer->decode($parsed_data, 'hal_json');
    $references = $this->getReferences($decode);

    // Record that we have checked references of current file.
    $this->discoveredReferences[$file->name] = $file;
    if ($references) {
      foreach ($references as $reference) {
        $this->decodeFile($reference);
      }
    }

    // Prepare data for import.
    $link = $decode['_links']['type']['href'];
    $data_to_import = [
      'data' => $decode,
      'entity_type_id' => $this->getEntityTypeByLink($link),
      'references' => $references,
    ];

    $this->preAddToImport($data_to_import);
    $this->addToImport($data_to_import);

    return $this;
  }

  /**
   * Here we can edit data`s value before importing.
   *
   * @param $data
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function preAddToImport(&$data) {
    $decode = $data['data'];
    $uuid = $decode['uuid'][0]['value'];
    $entity_type_id = $data['entity_type_id'];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
    $entity_type_object = $this->entityTypeManager->getDefinition($entity_type_id);

    // Keys of entity.
    $key_id = $entity_type_object->getKey('id');
    $key_revision_id = $entity_type_object->getKey('revision');

    // Some old exports don't have the entity ID.
    if (isset($decode[$key_id][0]['value'])) {
      $this->entityLookup[$entity_type_id][$decode[$key_id][0]['value']] = $uuid;
    }

    if ($entity) {
      $is_new = FALSE;
      $status = 'update';

      // Replace entity ID.
      $decode[$key_id][0]['value'] = $entity->id();

      // Skip if the Changed time the same or less in the file.
      if ($entity instanceof EntityChangedInterface) {
        // If an entity was refactored to implement the EntityChangedInterface,
        // older exports don't contain the changed field.
        if (isset($decode['changed'])) {
          $changed_time_file = 0;
          foreach ($decode['changed'] as $changed) {
            $changed_time = strtotime($changed['value']);
            if ($changed_time > $changed_time_file) {
              $changed_time_file = $changed_time;
            }
          }

          if (!$this->forceOverride && $changed_time_file <= $entity->getChangedTimeAcrossTranslations()) {
            $status = 'skip';
          }
        }
      }
      elseif (!$this->forceOverride) {
        $this->linkManager->setLinkDomain($this->getLinkDomain($data));
        $current_entity_decoded = $this->serializer->decode($this->exporter->getSerializedContent($entity), 'hal_json');
        $diff = ArrayDiffMultidimensional::looseComparison($decode, $current_entity_decoded);
        if (!$diff) {
          $status = 'skip';
        }
        // @todo is this still needed?
        $this->linkManager->setLinkDomain(FALSE);
      }
    }
    else {
      $status = 'create';
      $is_new = TRUE;

      // Ignore ID for creating a new entity.
      unset($decode[$key_id]);
    }

    // @see path_entity_base_field_info().
    // @todo offer an event to let third party modules register their content
    //       types.
    if (in_array($entity_type_id, ['taxonomy_term', 'node', 'media'])) {
      unset($decode['path']);
    }

    // Ignore revision and id of entity.
    unset($decode[$key_revision_id]);

    $data['is_new'] = $is_new;
    $data['status'] = $status;
    $data['data'] = $decode;
    $data['key_id'] = $key_id;

    return $this;
  }

  /**
   * This event is triggered before decoding to an entity.
   *
   * @param $file
   *
   * @return $this
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function preDenormalize(&$file, $entity_type) {
    $this->updateTargetRevisionId($file['data']);

    if ($entity_type === 'path_alias') {
      $this->updatePathAliasTargetId($file['data']);
    }

    return $this;
  }

  /**
   * Adding prepared data for import.
   *
   * @param $data
   *
   * @return $this
   */
  protected function addToImport($data) {
    $uuid = $data['data']['uuid'][0]['value'];
    $this->dataToImport[$uuid] = $data;

    return $this;
  }

  /**
   * Get all reference by entity array content.
   *
   * @param array $content
   *
   * @return array
   */
  private function getReferences(array $content) {
    $references = [];

    if (isset($content['_embedded'])) {
      foreach ($content['_embedded'] as $link) {
        foreach ($link as $reference) {
          if ($reference) {
            $uuid = $reference['uuid'][0]['value'];
            $path = $this->getPathToFileByName($uuid);

            if ($path) {
              $references[$uuid] = $this->files[$path];
            }
          }
        }
      }
    }

    return $references;
  }

  /**
   * Get path to file by Name.
   *
   * @param $name
   *
   * @return false|int|string
   */
  private function getPathToFileByName($name) {
    $array_column = array_column($this->files, 'name', 'uri');
    return array_search($name . '.json', $array_column);
  }

  /**
   * Get Entity type ID by link.
   *
   * @param $link
   *
   * @return string|string[]
   */
  private function getEntityTypeByLink($link) {
    $type = $this->linkManager->getTypeInternalIds($link);

    if ($type) {
      $entity_type_id = $type['entity_type'];
    }
    else {
      $components = array_reverse(explode('/', $link));
      $entity_type_id = $components[1];
      // @todo remove this line when core is >= 9.2
      $this->cache->invalidate('hal:links:types');
    }

    return $entity_type_id;
  }

  /**
   * If this entity contains a reference field with target revision is value,
   * we should to update it.
   *
   * @param $decode
   *
   * @return $this
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function updateTargetRevisionId(&$decode) {
    if (isset($decode['_embedded'])) {
      foreach ($decode['_embedded'] as $link_key => $link) {
        if (array_column($link, 'target_revision_id')) {
          foreach ($link as $ref_key => $reference) {
            $url = $reference['_links']['type']['href'];
            $uuid = $reference['uuid'][0]['value'];
            $entity_type = $this->getEntityTypeByLink($url);
            $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);

            // Update the Target revision id if child entity exist on this site.
            if ($entity) {
              $revision_id = $entity->getRevisionId();
              $decode['_embedded'][$link_key][$ref_key]['target_revision_id'] = $revision_id;
            }
          }
        }
      }
    }

    return $this;
  }

  /**
   * Rewrite path aliases to target entity IDs that were assigned during import.
   *
   * @param $decode
   *
   * @return $this
   */
  private function updatePathAliasTargetId(&$decode) {
    if ($alias = $decode['path'][0]['value'] ?? NULL) {
      if (preg_match('@^/(\w+)/(\d+)([/?#].*|)$@', $alias, $matches)) {
        $entity_type_id = str_replace('_', '/', $matches[1]);
        if ($uuid = $this->entityLookup[$entity_type_id][$matches[2]] ?? NULL) {
          if ($id = $this->entityIdLookup[$uuid] ?? NULL) {
            $decode['path'][0]['value'] = '/' . $matches[1] . '/' . $id . $matches[3];
          }
        }
      }
    }

    return $this;
  }
}
