<?php

namespace Drupal\Tests\hal\Functional\search;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\search\Functional\Rest\SearchPageResourceTestBase;

/**
 * @group hal
 */
#[Group('hal')]
#[RunTestsInSeparateProcesses]
class SearchPageHalJsonAnonTest extends SearchPageResourceTestBase {

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
