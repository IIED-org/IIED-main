<?php

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber;

use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\Client\Solarium\Endpoint;
use Drupal\acquia_search\EventSubscriber\SearchSubscriber;
use Drupal\acquia_search\Helper\Flood;
use Drupal\Component\Datetime\Time;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Solarium\Core\Client\Request;
use Solarium\Core\Event\PreExecuteRequest;
use Solarium\Plugin\NoWaitForResponseRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test search susbcriber for missing cookie.
 *
 * @group acquia_search
 */
class SearchSubscriberTest extends UnitTestCase {

  /**
   * Response message.
   */
  protected const MESSAGE = 'Could not build authentication cookie due to missing derived key for HMAC values.';

  /**
   * SUT.
   *
   * @var \Drupal\acquia_search\EventSubscriber\SearchSubscriber
   */
  protected $searchSubscriber;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    $subscription = $this->createMock(Subscription::class);
    $settings = $this->createMock(Settings::class);
    $settings->method('getIdentifier')->willReturn('abc123');
    $settings->method('getSecretKey')->willReturn('FooBar');
    $subscription->method('isActive')->willReturn(TRUE);
    $subscription->method('getSettings')->willReturn($settings);
    $subscription->method('getSubscription')->willReturn([
      'active' => TRUE,
      'uuid' => '',
      'subscription_name' => '',
      'expiration_date' => '',
      'acquia_search' => array_filter([
        'api_host' => 'https://api.sr-prod02.acquia.com',
        'extract_query_handler_option' => 'update/extract',
        'read_only' => FALSE,
        'override_search_core' => NULL,
        'module_version' => \Drupal::VERSION,
      ]),
    ]);

    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')->willReturn($this->createMock(Client::class));
    $cache_default = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')->disableOriginalConstructor()->getMock();
    $datetime_time = new Time(new RequestStack());
    $lock = $this->createMock(LockBackendInterface::class);

    $api_client = new AcquiaSearchApiClient(
      $this->createMock(LoggerChannelInterface::class),
      $subscription,
      $client_factory,
      $cache_default,
      $datetime_time,
      $lock
    );

    $flood = $this->createMock(Flood::class);
    $flood->method('isAllowed')
      ->willReturn(TRUE);

    $string_translator = $this->createMock(TranslationInterface::class);
    $string_translator
      ->method('translateString')
      ->willReturn(self::MESSAGE);
    $container = new ContainerBuilder();
    $container
      ->set('acquia_search.logger_channel', $this->createMock(LoggerInterface::class));
    $container
      ->set('string_translation', $string_translator);
    \Drupal::setContainer($container);

    $this->searchSubscriber = new SearchSubscriber($subscription, $api_client, $flood);
  }

  /**
   * Tests that search subscriber returns response if cookie is missing.
   */
  public function testMissingCookie(): void {
    $request = $this->createMock(Request::class);
    $event_name = PreExecuteRequest::class;
    $request
      ->method('getHandler')
      ->willReturn('handler');

    if (class_exists(NoWaitForResponseRequest::class)) {
      $search_subscriber = $this->createMock(NoWaitForResponseRequest::class);
    }
    else {
      $search_subscriber = $this->searchSubscriber;
    }
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher
      ->expects($this->any())
      ->method('getListeners')
      ->willReturn([
        [$search_subscriber, 'preExecuteRequest'],
      ]);
    $event = new PreExecuteRequest($request, $this->createMock(Endpoint::class));
    $this->searchSubscriber->preExecuteRequest($event, $event_name, $event_dispatcher);
    $resp = $event->getResponse();
    self::assertNotNull($resp);
    self::assertEquals(401, $resp->getStatusCode());
    self::assertEquals(['HTTP/1.1 401 Unauthorized'], $resp->getHeaders());
    self::assertEquals(self::MESSAGE, $resp->getBody());
  }

}
