<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Kernel\EventSubscriber;

use Drupal\Tests\acquia_search\Kernel\AcquiaSearchTestBase;

/**
 * Tests the `acquia_search` data is added to subscription data.
 *
 * @group acquia_search
 * @group orca_public
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\AcquiaSubscriptionData\AcquiaSearchData
 */
final class AcquiaSearchDataTest extends AcquiaSearchTestBase {

  /**
   * Tests the subscriber.
   */
  public function testSubscriptionDataSubscriber(): void {
    $this->populateSubscriptionSettings();
    self::assertEquals(
      [
        'active' => TRUE,
        'href' => '',
        'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
        'subscription_name' => '',
        'expiration_date' => '',
        'product' => [
          'view' => 'Acquia Network',
        ],
        'search_service_enabled' => 1,
        'acquia_search' => [
          'api_host' => 'https://api.sr-prod02.acquia.com',
          'extract_query_handler_option' => 'update/extract',
          'read_only' => FALSE,
          'override_search_core' => NULL,
          'module_version' => \Drupal::VERSION,
        ],
        'gratis' => FALSE,
      ],
      $this->container->get('acquia_connector.subscription')->getSubscription()
    );
  }

}
