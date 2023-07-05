<?php

namespace Drupal\Tests\isbn\Kernel\Feeds\Target;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\isbn\Feeds\Target\Isbn
 * @group isbn
 */
class IsbnTest extends FeedsKernelTestBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'isbn',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->createFieldWithStorage('field_isbn', [
      'type' => 'isbn',
    ]);

    // Create a feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'isbn' => 'isbn',
    ]);

    $this->feedType->addMapping([
      'target' => 'field_isbn',
      'map' => ['value' => 'isbn'],
    ]);
    $this->feedType->save();
  }

  /**
   * Tests importing isbn codes.
   */
  public function testImport() {
    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->getModulePath('isbn') . '/tests/resources/isbn-feed.csv',
    ]);
    $feed->import();

    // Assert that two nodes got imported.
    $this->assertNodeCount(4);
    $expected = [
      1 => '0123456789',
      2 => '9780123456786',
      3 => '0001234560',
      4 => '9780001234567',
    ];
    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->field_isbn->value);
    }

    // Assert that some entries failed to validate.
    $messages = \Drupal::messenger()->messagesByType('warning');
    $this->assertCount(3, $messages);
    $this->assertStringContainsString('The content <em class="placeholder">Invalid 10-digit</em> failed to validate', (string) $messages[0]);
    $this->assertStringContainsString('"<em class="placeholder">012345678X</em>" isn\'t a valid ISBN number.', (string) $messages[0]);
    $this->assertStringContainsString('The content <em class="placeholder">Invalid 13-digit</em> failed to validate', (string) $messages[1]);
    $this->assertStringContainsString('"<em class="placeholder">9780123456787</em>" isn\'t a valid ISBN number.', (string) $messages[1]);
    $this->assertStringContainsString('The content <em class="placeholder">Invalid</em> failed to validate', (string) $messages[2]);
    $this->assertStringContainsString('"<em class="placeholder">Lorem ipsum</em>" isn\'t a valid ISBN number.', (string) $messages[2]);

    // Clear the logged messages so no failure is reported on tear down.
    $this->logger->clearMessages();
  }

}
