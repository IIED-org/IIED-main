<?php

declare(strict_types=1);

namespace Drupal\Tests\pdfpreview\Kernel;

use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Site\Settings;
use Drupal\Core\Test\TestDatabase;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\imagemagick\Kernel\ToolkitSetupTrait;
use Psr\Log\LoggerInterface;

/**
 * Test actual PDF generation.
 *
 * @coversDefaultClass \Drupal\pdfpreview\PdfPreviewGenerator
 * @group pdfpreview
 */
final class PdfPreviewGeneratorTest extends KernelTestBase implements LoggerInterface {

  use RfcLoggerTrait;
  use ToolkitSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'pdfpreview',
    'file',
    'file_mdm',
    'imagemagick',
    'sophron',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['imagemagick', 'pdfpreview', 'system']);
    $this->installEntitySchema('user_role');
    $this->setUpToolkit('imagemagick', 'imagemagick.settings', [
      'binaries' => 'imagemagick',
      'quality' => 100,
      'debug' => TRUE,
    ]);
    $this->container->get('logger.factory')->addLogger($this);
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * @covers ::getPdfPreview
   */
  public function testGetPdfPreview(): void {
    $source = 'public://lorem-ipsum.pdf';
    $path = $this->getModulePath('pdfpreview');
    $this->fileSystem->copy($path . '/tests/fixtures/files/lorem-ipsum.pdf', $source);
    $file = File::create(['fid' => 1, 'uri' => $source]);
    $this->assertFileExists($this->fileSystem->realpath($source));

    $expected = 'public://pdfpreview/1-lorem-ipsum.png';
    $this->assertFileDoesNotExist($expected);

    $destination = \Drupal::service('pdfpreview.generator')->getPdfPreview($file);
    $this->assertEquals($expected, $destination);
    $this->assertFileExists($expected);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, \Stringable|string $message, array $context = []): void {
    if ($level !== RfcLogLevel::DEBUG) {
      return;
    }
    if (isset($context['@return_code']) && $context['@return_code'] !== 0) {
      $this->fail(strtr($message, $context));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem(): void {
    parent::setUpFilesystem();

    // Create a test-specific but real (no vfs://) files directory.
    $test_db = new TestDatabase($this->databasePrefix);
    $files_directory = $test_db->getTestSitePath() . '/files';
    mkdir($files_directory, 0775, TRUE);

    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['file_public_path'] = $files_directory;
    new Settings($settings);
  }

}
