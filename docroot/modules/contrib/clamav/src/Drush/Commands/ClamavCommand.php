<?php

declare(strict_types=1);

namespace Drupal\Clamav\Drush\Commands;

use Drupal\clamav\Scanner;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * Drush commands for ClamAV file scanning.
 */
class ClamavCommand extends DrushCommands {

  use DependencySerializationTrait;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructs a new ClamavCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\clamav\Scanner $scanner
   *   The ClamAV scanner service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entity_type_manager,
    protected Scanner $scanner,
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
    $this->fileStorage = $entity_type_manager->getStorage('file');
  }

  /**
   * Scan existing managed permanent files.
   */
  #[CLI\Command(name: 'clamav:scan-files', aliases: ['cav-sf'])]
  #[CLI\Option(name: 'batch-size', description: 'Batch size, optional. Default: 50')]
  #[CLI\Usage(name: 'drush clamav:scan-files', description: 'Scans all managed files for viruses using ClamAV')]
  #[CLI\Usage(name: 'drush clamav:scan-files --batch-size=5', description: 'Scans all managed files using ClamAV with batch size of 5')]
  public function scanFiles($options = ['batch-size' => 50]) {
    $this->scanManagedFiles($options);
  }

  /**
   * Scans all managed files in the system.
   */
  protected function scanManagedFiles(array $options) {
    // Query to get all permanent file IDs.
    $file_ids = $this->fileStorage->getQuery()
      ->condition('status', FileInterface::STATUS_PERMANENT)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($file_ids)) {
      $this->logger()->notice('No managed files found to scan.');
      return;
    }

    $total = count($file_ids);

    $this->logger()->notice("Starting scan of {$total} managed files...");

    // Set up the batch.
    $batch = [
      'title' => dt('Processing managed files'),
      'operations' => [],
      'finished' => [$this, 'batchFinished'],
      'progressive' => TRUE,
      'init_message' => dt('Initiating scanning process...'),
      'progress_message' => dt('Processed @current out of @total files.'),
      'error_message' => dt('An error occurred during processing'),
    ];

    // Create batch operations.
    for ($offset = 0; $offset < $total; $offset += $options['batch-size']) {
      $batch['operations'][] = [
        [$this, 'processScannerBatch'],
        [
          $options['batch-size'],
          $offset,
          $options,
        ],
      ];
    }

    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Batch operation callback.
   */
  public function processScannerBatch(
    int $limit,
    int $offset,
    array $options,
    array &$context,
  ): void {
    // Initialize batch context if needed.
    if (!isset($context['results']['processed'])) {
      $context['results']['clean'] = 0;
      $context['results']['infected'] = 0;
      $context['results']['unchecked'] = 0;
    }

    // Query files for this batch.
    $file_ids = $this->fileStorage->getQuery()
      ->condition('status', FileInterface::STATUS_PERMANENT)
      ->accessCheck(FALSE)
      ->range($offset, $limit)
      ->execute();

    $files = $this->fileStorage->loadMultiple($file_ids);

    foreach ($files as $file) {
      /** @var \Drupal\file\FileInterface $file */
      $uri = $file->getFileUri();
      $real_path = $this->fileSystem->realpath($uri);

      if (!$real_path || !file_exists($real_path)) {
        $context['results']['unchecked']++;
        Drush::logger()->notice("Could not access file: {$uri}");
        continue;
      }

      $result = $this->scanner->scan($file);
      switch ($result) {
        case Scanner::FILE_IS_CLEAN:
          $context['results']['clean']++;
          break;

        case Scanner::FILE_IS_INFECTED:
          $context['results']['infected']++;
          break;

        case Scanner::FILE_IS_UNCHECKED:
          $context['results']['unchecked']++;
          break;
      }
    }
  }

  /**
   * Batch finished callback.
   */
  public function batchFinished($success, $results, $operations, $elapsed): void {
    if ($success) {
      Drush::logger()->notice(dt('Finished processing {count} files in {elapsed}', ['count' => ($results['clean'] + $results['infected'] + $results['unchecked']), 'elapsed' => $elapsed]));
      Drush::logger()->notice(dt('There were:'));
      Drush::logger()->notice(dt(' - {count} clean files', ['count' => $results['clean']]));
      Drush::logger()->notice(dt(' - {count} infected files', ['count' => $results['infected']]));
      Drush::logger()->notice(dt(' - {count} unchecked files', ['count' => $results['unchecked']]));
    }
    else {
      Drush::logger()->error(dt('Batch failed to process {count} operations...', ['count' => count($operations)]));
    }
  }

}
