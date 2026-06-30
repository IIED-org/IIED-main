<?php

namespace Drupal\Tests\hal\Functional\system;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\system\Functional\Rest\MenuResourceTestBase;

/**
 * @group hal
 */
#[Group('hal')]
#[RunTestsInSeparateProcesses]
class MenuHalJsonAnonTest extends MenuResourceTestBase {

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

}
