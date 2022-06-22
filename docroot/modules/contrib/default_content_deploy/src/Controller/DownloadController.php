<?php

namespace Drupal\default_content_deploy\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\default_content_deploy\DeployManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Returns responses for config module routes.
 */
class DownloadController implements ContainerInjectionInterface {

  /**
   * The DCD manager.
   *
   * @var \Drupal\default_content_deploy\DeployManager
   */
  protected $deployManager;

  /**
   * The File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * DownloadController constructor.
   *
   * @param \Drupal\default_content_deploy\DeployManager $deploy_manager
   *   The DCD manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The File system.
   */
  public function __construct(DeployManager $deploy_manager, FileSystemInterface $file_system) {
    $this->deployManager = $deploy_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('default_content_deploy.manager'),
      $container->get('file_system')
    );
  }

  /**
   * Return binary archive file for download.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function downloadCompressedContent() {
    $this->deployManager->compressContent();
    $path = $this->fileSystem->getTempDirectory() . '/dcd/content.tar.gz';

    $headers = [
      'Content-Type' => 'application/tar+gzip',
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=content.tar.gz'
    ];

    return new BinaryFileResponse($path, 200, $headers);
  }

}
