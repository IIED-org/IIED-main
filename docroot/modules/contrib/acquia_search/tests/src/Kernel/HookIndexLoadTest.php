<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Kernel;

use Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\ServerInterface;

/**
 * @group acquia_search
 */
final class HookIndexLoadTest extends AcquiaSearchTestBase {

  /**
   * Test that indexes are always processed for read-only on load.
   */
  public function testIndexLoad(): void {
    $backend = $this->createMock(AcquiaSearchSolrBackend::class);
    $backend->method('isReadOnly')->willReturn(TRUE);
    $server = $this->createMock(ServerInterface::class);
    $server->method('status')->willReturn(TRUE);
    $server->method('getBackend')->willReturn($backend);

    $index = Index::create()->setServer($server);
    acquia_search_search_api_index_load([$index]);
    self::assertTrue($index->isReadOnly());

    $index = Index::create()->setServer($server);
    acquia_search_search_api_index_load([$index]);
    self::assertTrue($index->isReadOnly());
  }

}
