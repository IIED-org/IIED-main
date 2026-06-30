<?php

namespace Drupal\Tests\hal\Functional\layout_builder;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

use Drupal\Tests\hal\Functional\Core\EntityViewDisplayHalJsonAnonTest;
use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group hal
 */
#[Group('hal')]
#[RunTestsInSeparateProcesses]
class LayoutBuilderEntityViewDisplayHalJsonBasicAuthTest extends EntityViewDisplayHalJsonAnonTest {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
