<?php

namespace Drupal\Tests\hal\Functional\taxonomy;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\taxonomy\Functional\Rest\VocabularyResourceTestBase;

/**
 * @group hal
 */
#[Group('hal')]
#[RunTestsInSeparateProcesses]
class VocabularyHalJsonCookieTest extends VocabularyResourceTestBase {

  use CookieResourceTestTrait;

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
  protected static $auth = 'cookie';

}
