<?php

namespace Drupal\Tests\linkchecker\Functional;

use Drupal\node\Entity\Node;

/**
 * Test Link checker module link extraction status functionality.
 *
 * @group linkchecker
 */
class LinkCheckerLinkExtractionStatusTest extends LinkCheckerBaseTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // User to set up link checker.
    $this->adminUser = $this->drupalCreateUser([
      'administer linkchecker',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that the status is correct on the status page.
   */
  public function testLinkCheckerStatusCorrect() {
    // Just to make sure, let's consult the service directly.
    /** @var \Drupal\linkchecker\LinkExtractorBatch $batch */
    $batch = $this->container->get('linkchecker.extractor_batch');
    self::assertEquals(0, $batch->getTotalEntitiesToProcess());
    self::assertEquals(0, $batch->getNumberOfProcessedEntities());
    // Create page nodes that are processable by linkchecker.
    Node::create([
      'title' => 'node 1',
      'type' => 'page',
      'body' => [
        'value' => '<a href="https://httpbin.org/status/200">I should give a 200 OK response.</a>',
        'format' => 'filtered_html',
      ],
    ])->save();
    Node::create([
      'title' => 'node 2',
      'type' => 'page',
      'body' => [
        'value' => 'I contain no links, but I shall still be processed.',
        'format' => 'filtered_html',
      ],
    ])->save();
    $key = $this->container->get('state')->get('system.cron_key');
    $this->drupalGet('cron/' . $key);
    $this->assertSession()->statusCodeEquals(204);
    // The service should have updated its numbers.
    self::assertEquals(2, $batch->getTotalEntitiesToProcess());
    self::assertEquals(2, $batch->getNumberOfProcessedEntities());
    // Now get the status.
    $this->drupalGet('/admin/config/content/linkchecker');
    $this->assertSession()->pageTextContains('2 out of 2 items have been processed');
    $this->assertSession()->pageTextContains('100%');
    // Now create a node of the article type, which should not be processed by
    // linkchecker.
    Node::create([
      'title' => 'node 3',
      'type' => 'article',
    ])->save();
    // There should be no change in status.
    self::assertEquals(2, $batch->getTotalEntitiesToProcess());
    self::assertEquals(2, $batch->getNumberOfProcessedEntities());
    $this->assertSession()->pageTextContains('2 out of 2 items have been processed');
    $this->assertSession()->pageTextContains('100%');
  }

}
