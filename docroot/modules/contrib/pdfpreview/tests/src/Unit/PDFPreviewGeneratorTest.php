<?php

namespace Drupal\Tests\pdfpreview\Unit;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\pdfpreview\PDFPreviewGenerator;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\pdfpreview\PDFPreviewGenerator
 *
 * @group pdfpreview
 */
class PDFPreviewGeneratorTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
  }

  /**
   * @covers ::getDestinationURI
   */
  public function testGetDestinationUri() {
    $filename = 'Test File.pdf';
    $id = '1234';
    $md5 = '117aa294817277a5c090bf18d515d885';

    $file = $this->getFileMock($filename, $id);
    $generator = $this->getPdfPreviewGeneratorMock();

    $reflection = new \ReflectionClass(PDFPreviewGenerator::class);
    $method = $reflection->getMethod('getDestinationURI');
    $method->setAccessible(TRUE);

    $file_config = $this->prophesize(ImmutableConfig::class);
    $this->configFactory->get('system.file')->willReturn($file_config->reveal());
    $file_config->get('default_scheme')->willReturn('public');

    $config = $this->prophesize(ImmutableConfig::class);
    $this->configFactory->get('pdfpreview.settings')->willReturn($config->reveal());
    $config->get('path')->willReturn('pdfpreview');

    // MD5 filename, JPG.
    $config->get('filenames')->willReturn('md5');
    $config->get('type')->willReturn('jpg');
    $filename_md5_jpg = 'public://pdfpreview/' . $md5 . '.jpg';
    $this->assertEquals($filename_md5_jpg, $method->invoke($generator, $file));

    // MD5 filename, PNG.
    $config->get('type')->willReturn('png');
    $filename_md5_png = 'public://pdfpreview/' . $md5 . '.png';
    $this->assertEquals($filename_md5_png, $method->invoke($generator, $file));

    // Human filename, PNG.
    $config->get('filenames')->willReturn('human');
    $filename_human_png = 'public://pdfpreview/1234-test-file.png';
    $this->assertEquals($filename_human_png, $method->invoke($generator, $file));
  }

  /**
   * Gets a mocked PDF Preview Generator for testing.
   *
   * @return \Drupal\pdfpreview\PDFPreviewGenerator
   *   Mocked PDF Preview Generator.
   */
  protected function getPdfPreviewGeneratorMock() {
    $file_system = $this->createMock('\Drupal\Core\File\FileSystem');
    $file_system
      ->expects($this->any())
      ->method('basename')
      ->with('public://Test File.pdf', '.pdf')
      ->willReturn('Test File');
    $transliteration = $this->createMock(TransliterationInterface::class);
    $transliteration
      ->expects($this->any())
      ->method('transliterate')
      ->with('Test File', 'en')
      ->willReturn('test-file');

    $image_toolkit_manager = $this->createMock('\Drupal\Core\ImageToolkit\ImageToolkitManager');

    $language = $this->createMock('Drupal\Core\Language\Language');
    $language
      ->expects($this->any())
      ->method('getId')
      ->willReturn('en');

    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManager');
    $language_manager
      ->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    $generator = $this->getMockBuilder('\Drupal\pdfpreview\PDFPreviewGenerator')
      ->setConstructorArgs([
        $this->configFactory->reveal(),
        $file_system,
        $transliteration,
        $image_toolkit_manager,
        $language_manager,
      ])
      ->getMock();

    return $generator;
  }

  /**
   * Gets a mocked file for testing.
   *
   * @param string $filename
   *   The filename.
   * @param int $id
   *   The file id.
   *
   * @return \Drupal\file\FileInterface
   *   The mocked file.
   */
  protected function getFileMock($filename, $id) {
    $methods = ['id', 'getFileUri'];
    $file = $this->createMock('\Drupal\file\Entity\File');
    $file->expects($this->any())
      ->method('id')
      ->willReturn($id);
    $file->expects($this->any())
      ->method('getFileUri')
      ->willReturn('public://' . $filename);
    return $file;
  }

}
