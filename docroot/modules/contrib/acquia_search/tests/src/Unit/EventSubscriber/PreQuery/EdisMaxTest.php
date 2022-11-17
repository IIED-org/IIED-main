<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PreQuery;

use Drupal\acquia_search\EventSubscriber\PreQuery\EdisMax;
use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface as SearchApiQueryInterface;
use Drupal\search_api\ServerInterface;
use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\Tests\acquia_search\Unit\AcquiaSearchTestCase;
use Solarium\Core\Query\QueryInterface as SolariumQueryInterface;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\PreQuery\EdisMax
 */
final class EdisMaxTest extends AcquiaSearchTestCase {

  /**
   * @testWith ["update/extract", false]
   *           ["mlt", false]
   *           ["select", true]
   */
  public function testOnlySelectHandler(string $handler, bool $called): void {
    $search_api_query = $this->createMock(SearchApiQueryInterface::class);
    $solarium_query = $this->createMock(SolariumQueryInterface::class);
    $solarium_query->method('getHandler')->willReturn($handler);

    $index = $this->createMock(IndexInterface::class);

    $search_api_query->expects($called ? $this->once() : $this->never())
      ->method('getIndex')
      ->willReturn($index);

    $event = new PreQueryEvent(
      $search_api_query,
      $solarium_query
    );

    $sut = new EdisMax();
    $sut->preQuery($event);
  }

  /**
   * @phpcs:disable Drupal.Files.LineLength.TooLong
   * @phpcs:disable Drupal.Commenting.DocComment.SpacingAfter
   *
   * @testWith ["\\Drupal\\acquia_search\\Plugin\\SolrConnector\\SearchApiSolrAcquiaConnector", true]
   *           ["\\Drupal\\search_api_solr\\Plugin\\SolrConnector\\StandardSolrCloudConnector", false]
   *
   */
  public function testOnlyOurSolrConnector(string $solr_connector, bool $called): void {
    $search_api_query = $this->createMock(SearchApiQueryInterface::class);
    $solarium_query = $this->createMock(SolariumQueryInterface::class);
    $solarium_query->method('getHandler')->willReturn('select');

    $index = $this->createMock(IndexInterface::class);
    $index->expects($called ? $this->once() : $this->never())
      ->method('getThirdPartySetting');

    $server = $this->createMock(ServerInterface::class);
    $backend = $this->createMock(SolrBackendInterface::class);
    $backend
      ->method('getSolrConnector')
      ->willReturn($this->createMock($solr_connector));

    $server->method('getBackend')->willReturn($backend);
    $index->method('getServerInstance')->willReturn($server);
    $search_api_query->method('getIndex')->willReturn($index);

    $event = new PreQueryEvent(
      $search_api_query,
      $solarium_query
    );

    $sut = new EdisMax();
    $sut->preQuery($event);
  }

  /**
   * @phpcs:disable Drupal.Commenting.DocComment.SpacingAfter
   *
   * @testWith [true]
   *           [false]
   *
   */
  public function testSetEdismaxFromSettings(bool $use_edismax): void {
    $search_api_query = $this->createMock(SearchApiQueryInterface::class);
    $solarium_query = $this->createMock(SolariumQueryInterface::class);
    $solarium_query->method('getHandler')->willReturn('select');
    $solarium_query
      ->expects($use_edismax ? $this->once() : $this->never())
      ->method('addParam')
      ->with('defType', 'edismax');

    $index = $this->createMock(IndexInterface::class);
    $index
      ->method('getThirdPartySetting')
      ->with('acquia_search', 'use_edismax', FALSE)
      ->willReturn($use_edismax);

    $server = $this->createMock(ServerInterface::class);
    $backend = $this->createMock(SolrBackendInterface::class);
    $backend
      ->method('getSolrConnector')
      ->willReturn($this->createMock(SearchApiSolrAcquiaConnector::class));

    $server->method('getBackend')->willReturn($backend);
    $index->method('getServerInstance')->willReturn($server);
    $search_api_query->method('getIndex')->willReturn($index);

    $event = new PreQueryEvent(
      $search_api_query,
      $solarium_query
    );

    $sut = new EdisMax();
    $sut->preQuery($event);
  }

}
