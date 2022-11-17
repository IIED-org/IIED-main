<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PostCreateIndexDocument;

use Drupal\acquia_search\EventSubscriber\PostCreateIndexDocument\RemoveBoost;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentEvent;
use Drupal\Tests\acquia_search\Unit\AcquiaSearchTestCase;
use Solarium\QueryType\Update\Query\Document;

/**
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\PostCreateIndexDocument\RemoveBoost
 * @group acquia_search
 */
final class RemoveBoostTest extends AcquiaSearchTestCase {

  /**
   * @testWith [1.0, true]
   *           [1.1, false]
   */
  public function testRemoveBoostIfDefault(float $boost, bool $removed): void {
    $document = $this->createMock(Document::class);
    $document
      ->method('getFields')
      ->willReturn([
        'boost_document' => $boost,
      ]);
    $document->expects($removed ? $this->once() : $this->never())
      ->method('removeField')
      ->with('boost_document');
    $event = new PostCreateIndexDocumentEvent(
      $this->createMock(ItemInterface::class),
      $document
    );
    $sut = new RemoveBoost();
    $sut->postDocumentIndex($event);
  }

}
