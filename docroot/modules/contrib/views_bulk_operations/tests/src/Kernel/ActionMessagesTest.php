<?php

declare(strict_types=1);

namespace Drupal\Tests\views_bulk_operations\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Action messages test.
 */
#[CoversClass(ViewsBulkOperationsActionProcessor::class)]
#[Group('views_bulk_operations')]
final class ActionMessagesTest extends ViewsBulkOperationsKernelTestBase {

  /**
   * Tests messages displayed by different actions.
   *
   * @dataProvider actionDataProvider
   */
  #[DataProvider('actionDataProvider')]
  public function testViewsBulkOperationsActionMessages(int $nodes_count, string $action_id, array $result_messages): void {
    $this->createTestNodes([
      'page' => [
        'count' => $nodes_count,
      ],
    ]);

    $vbo_data = [
      'view_id' => 'views_bulk_operations_test_advanced',
      'action_id' => $action_id,
    ];

    // Test executing all view results first.
    $results = $this->executeAction($vbo_data);

    foreach ($result_messages as $index => $message) {
      static::assertEquals($message['type'], $results['finished_output'][$index]['type']);
      static::assertEquals((string) $message['message'], (string) $results['finished_output'][$index]['message']);
    }
  }

  /**
   * Data provider.
   *
   * @return mixed[]
   *   The test data.
   */
  public static function actionDataProvider(): array {
    return [
      [
        4,
        'views_bulk_operations_simple_test_action',
        [
          [
            'message' => new TranslatableMarkup('Test (3)'),
            'type' => 'status',
          ],
        ],
      ],
      [
        4,
        'views_bulk_operations_test_action_v2',
        [
          [
            'message' => new TranslatableMarkup('A warning message. (1)'),
            'type' => 'warning',
          ],
          [
            'message' => new TranslatableMarkup('A warning message with a "quote", an & ampersand, and <tag>. (1)'),
            'type' => 'warning',
          ],
          [
            'message' => new TranslatableMarkup('Standard output. (1)'),
            'type' => 'status',
          ],
        ],
      ],
    ];
  }

}
