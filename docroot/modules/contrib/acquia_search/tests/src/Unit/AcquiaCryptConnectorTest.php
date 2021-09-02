<?php

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\AcquiaCryptConnector;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\acquia_search\AcquiaCryptConnector
 * @group Acquia Search Solr
 */
class AcquiaCryptConnectorTest extends UnitTestCase {

  /**
   * Test AcquiaCryptConnector::createDerivedKey().
   */
  public function testAcquiaCryptConnector() {

    $salt = $this->randomMachineName(20);
    $id = 'ABC-12345.env.db';
    $key = $this->randomMachineName(20);
    $derivation_string = $id . 'solr' . $salt;
    $derivedKey = hash_hmac('sha1', str_pad($derivation_string, 80, $derivation_string), $key);

    $this->assertEquals($derivedKey, AcquiaCryptConnector::createDerivedKey($salt, $id, $key));

  }

}
