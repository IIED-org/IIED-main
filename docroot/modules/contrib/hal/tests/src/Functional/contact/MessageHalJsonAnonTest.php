<?php

namespace Drupal\Tests\hal\Functional\contact;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

use Drupal\Tests\contact\Functional\Rest\MessageResourceTestBase;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
#[Group('hal')]
#[RunTestsInSeparateProcesses]
class MessageHalJsonAnonTest extends MessageResourceTestBase {

  use HalEntityNormalizationTrait;
  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return parent::getNormalizedPostEntity() + [
      '_links' => [
        'type' => [
          'href' => $this->baseUrl . '/rest/type/contact_message/camelids',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testGet(?bool $run = NULL): void {
    if ($run !== TRUE && \version_compare(\Drupal::VERSION, '11.3.999', '>=')) {
      $this->markTestSkipped('This has been replaced with doTestGet.');
    }
    parent::testGet();
  }

  /**
   * {@inheritdoc}
   */
  public function doTestGet(): void {
    if (\method_exists($this, 'testGet')) {
      $this->testGet(TRUE);
    }
    $this->fail('Contact module has removed ::testGet()');
  }

}
