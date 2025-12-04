<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaCryptConnector;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\AcquiaSearchEvents;
use Drupal\acquia_search\Client\Solarium\AcquiaGuzzle;
use Drupal\acquia_search\Client\Solarium\Endpoint as AcquiaEndpoint;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\EventSubscriber\SearchSubscriber;
use Drupal\acquia_search\Helper\Flood;
use Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend;
use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\acquia_search\PreferredCoreServiceFactory;
use Drupal\Component\Datetime\Time;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\search_api\ServerInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginManager;
use Drupal\search_api_solr\SolrConnectorInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Solarium\Core\Event\Events;
use Solarium\Core\Query\Helper;
use Solarium\QueryType\MorelikeThis\Query as MoreLikeThisQuery;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector
 */
final class SearchApiSolrAcquiaConnectorTest extends UnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher|void
   */
  private $eventDispatcher;

  public function testCoreLink(): void {
    $container = $this->createContainerMock();
    $sut = $this->createInstance($container);
    // '<a href="https://foobarbaz.host:443/solr/FooBarBaz/">https://foobarbaz.host:443/solr/FooBarBaz/</a>'
    self::assertEquals(
      [
        '#type' => 'link',
        '#url' => Url::fromUri('https://solr.acquia.com:443/solr/abc123.prod/'),
        '#title' => 'https://solr.acquia.com:443/solr/abc123.prod/',
      ],
      $sut->getCoreLink()->toRenderable()
    );
  }

  public function testCoreLinkWithExistingConfiguration(): void {
    $container = $this->createContainerMock();
    $sut = $this->createInstance($container, [
      'port' => '8983',
      'scheme' => 'http',
    ]);
    // '<a href="https://foobarbaz.host:443/solr/FooBarBaz/">https://foobarbaz.host:443/solr/FooBarBaz/</a>'
    self::assertEquals(
      [
        '#type' => 'link',
        '#url' => Url::fromUri('https://solr.acquia.com:443/solr/abc123.prod/'),
        '#title' => 'https://solr.acquia.com:443/solr/abc123.prod/',
      ],
      $sut->getCoreLink()->toRenderable()
    );
  }

  public function testPingServer(): void {
    $ping_response = Json::encode([
      'core' => [
        'schema' => 'drupal-4.2.6-solr-8.x-0',
      ],
    ]);
    $container = $this->createContainerMock([
      '/solr/abc123.prod/admin/system' => new Response(200, [], $ping_response),
    ]);
    $sut = $this->createInstance($container);
    self::assertNotFalse($sut->pingServer());
    // Verify result is statically cached, no 2nd request.
    self::assertNotFalse($sut->pingServer());
  }

  public function testAdjustTimeout(): void {
    $container = $this->createContainerMock([]);
    $sut = $this->createInstance($container);

    $endpoint = NULL;
    $sut->adjustTimeout(15, SolrConnectorInterface::QUERY_TIMEOUT, $endpoint);
    self::assertInstanceOf(AcquiaEndpoint::class, $endpoint, 'Custom endpoint class preserved');
    self::assertEquals(15, $endpoint->getOption(SolrConnectorInterface::QUERY_TIMEOUT));
  }

  public function testGetEndpoint(): void {
    $container = $this->createContainerMock([]);
    $sut = $this->createInstance($container);
    self::assertInstanceOf(AcquiaEndpoint::class, $sut->getEndpoint());
  }

  public function testGetUpdateQuery(): void {
    $container = $this->createContainerMock([]);
    $sut = $this->createInstance($container);
    self::assertInstanceOf(UpdateQuery::class, $sut->getUpdateQuery());

    // This is normally modified during hook_search_api_server_load().
    $options = $sut->getEndpoint()->getOptions();
    $options['overridden_by_acquia_search'] = SearchApiSolrAcquiaConnector::READ_ONLY;
    $sut->getEndpoint()->setOptions($options, TRUE);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The Search API Server serving this index is currently in read-only mode.');
    $sut->getUpdateQuery();
  }

  /**
   * @testWith [null]
   *           ["extract/tika"]
   *           ["update/extract"]
   */
  public function testGetExtractQuery(?string $extract_handler): void {
    $container = $this->createContainerMock([], [
      'extract_query_handler_option' => $extract_handler,
    ]);
    $sut = $this->createInstance($container);
    $query = $sut->getExtractQuery();
    self::assertEquals($extract_handler ?: 'update/extract', $query->getHandler());
  }

  public function testMoreLikeThisQuery(): void {
    $container = $this->createContainerMock([]);
    $sut = $this->createInstance($container);
    $query = $sut->getMoreLikeThisQuery();
    self::assertInstanceOf(MoreLikeThisQuery::class, $query);
    self::assertEquals('mlt', $query->getHandler());
    // @todo the params is the only difference from parent class. can we document why?
    self::assertEquals([
      'qt' => 'mlt',
    ], $query->getParams());
  }

  private function createInstance(ContainerInterface $container, array $configuration = []): SearchApiSolrAcquiaConnector {
    $server = $this->createMock(ServerInterface::class);
    $server->method('id')->willReturn('foobar');
    $configuration['#server'] = $server;
    $backend = AcquiaSearchSolrBackend::create(
      $container,
      $configuration,
      'acquia_search_solr',
      [
        'label' => 'Acquia Search Solr',
        'description' => '',
      ]
    );
    return $backend->getSolrConnector();
  }

  private function createContainerMock(array $solr_responses = [], array $subscription_data = []): ContainerInterface {
    $container = new ContainerBuilder();

    $state = $this->createMock(StateInterface::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($this->createMock(LoggerInterface::class));

    $date_formatter = $this->createMock(DateFormatterInterface::class);
    $messenger = $this->createMock(MessengerInterface::class);
    $cache_default = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')->disableOriginalConstructor()->getMock();

    // Effectively disable the memory cache.
    $cache_default->method('get')->willReturn(FALSE);

    $datetime_time = new Time(new RequestStack());
    $subscription = $this->createMock(Subscription::class);
    $settings = $this->createMock(Settings::class);
    $settings->method('getIdentifier')->willReturn('abc123');
    $settings->method('getApplicationUuid')->willReturn('a47ac10b-58cc-4372-a567-0e02b2c3d470');
    $settings->method('getSecretKey')->willReturn('FooBar');
    $subscription->method('isActive')->willReturn(TRUE);
    $subscription->method('getSettings')->willReturn($settings);
    $subscription->method('getSubscription')->willReturn([
      'active' => TRUE,
      'uuid' => '',
      'subscription_name' => '',
      'expiration_date' => '',
      'acquia_search' => array_filter($subscription_data + [
        'api_host' => 'https://api.sr-prod02.acquia.com',
        'extract_query_handler_option' => 'update/extract',
        'read_only' => FALSE,
        'override_search_core' => NULL,
        'module_version' => \Drupal::VERSION,
      ]),
    ]);

    $acquia_guzzle = new AcquiaGuzzle(
      new HandlerStack(
        function (Request $request) use ($solr_responses) {
          $uri = $request->getUri();
          self::assertArrayHasKey($uri->getPath(), $solr_responses);
          $response = $solr_responses[$uri->getPath()];
          assert($response instanceof Response);
          $hmac = [];
          $cookie_to_parseable_string = implode('&', array_map(
            'trim',
            explode(';', $request->getHeaderLine('Cookie'))
          ));
          parse_str($cookie_to_parseable_string, $hmac);
          $derived_key = AcquiaCryptConnector::createDerivedKey(
            Crypt::hashBase64('abc123.prod'),
            'abc123.prod',
            'ubersecret',
          );

          return $response->withHeader(
            'Pragma',
            'hmac_digest=' . hash_hmac('sha1', $hmac['acquia_solr_nonce'] . $response->getBody(), $derived_key) . ';'
          );
        }
      )
    );

    $client = new Client([
      'handler' => HandlerStack::create(
        new MockHandler([
          new Response(200, [], Json::encode([
            'data' => [
              [
                'id' => 'foobarprodbaz',
                'attributes' => [
                  'url' => 'https://foobar.acquia.com/core/foobarprodbaz',
                ],
              ],
              [
                'id' => 'prod_foobarbaz',
                'attributes' => [
                  'url' => 'https://foobar.acquia.com/core/prod_foobarbaz',
                ],
              ],
              [
                'id' => 'abc123.prod',
                'attributes' => [
                  'url' => 'https://solr.acquia.com/core/abc123.prod',
                ],
              ],
              [
                'id' => 'abc123.prod.othersite',
                'attributes' => [
                  'url' => 'https://solr.acquia.com/core/abc123.prod.othersite',
                ],
              ],
              [
                'id' => 'abc123.prod.default',
                'attributes' => [
                  'url' => 'https://solr.acquia.com/core/abc123.prod.default',
                ],
              ],
            ],
          ])),
          new Response(200, [], Json::encode([
            'key' => 'abc123.prod',
            'secret_key' => 'ubersecret',
            'product_policies' => [
              'salt' => Crypt::hashBase64('abc123.prod'),
            ],
          ])),
        ])
      ),
    ]);
    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')->willReturn($client);

    $lock = $this->createMock(LockBackendInterface::class);
    $lock->method('acquire')
      ->with('acquia_search_get_search_indexes')
      ->willReturn(TRUE);

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
      ->willReturnMap([
        ['admin/system', TRUE],
      ]);
    $search_subscriber = new SearchSubscriber($subscription, $api_client, $flood);

    if (version_compare(\Drupal::VERSION, '10.0', '<')) {
      $event_dispatcher = new ContainerAwareEventDispatcher($container, [
        AcquiaSearchEvents::GET_POSSIBLE_CORES => [
          0 => [
            [
              'callable' => function (AcquiaPossibleCoresEvent $event) {
                $event->addPossibleCore('abc123.prod');
              },
            ],
          ],
        ],
        Events::PRE_EXECUTE_REQUEST => [
          0 => [
            [
              'service' => [
                'acquia_search.search_subscriber',
                'preExecuteRequest',
              ],
            ],
          ],
        ],
        Events::POST_EXECUTE_REQUEST => [
          0 => [
            [
              'service' => [
                'acquia_search.search_subscriber',
                'postExecuteRequest',
              ],
            ],
          ],
        ],
      ]);
    }
    else {
      $event_dispatcher = (new \ReflectionClass(EventDispatcher::class))->newInstanceWithoutConstructor();

      $event_dispatcher->addListener(
        AcquiaSearchEvents::GET_POSSIBLE_CORES,
        function (AcquiaPossibleCoresEvent $event) {
          $event->addPossibleCore('abc123.prod');
        }
      );
      $event_dispatcher->addListener(
        Events::PRE_EXECUTE_REQUEST,
        [
          $search_subscriber,
          'preExecuteRequest',
        ],
      );
      $event_dispatcher->addListener(
        Events::POST_EXECUTE_REQUEST,
        [
          $search_subscriber,
          'postExecuteRequest',
        ],
      );
    }

    $preferred_core = new PreferredCoreServiceFactory(
      $event_dispatcher,
      $subscription,
      $api_client,
      $this->createMock(ModuleHandlerInterface::class)
    );

    $container->set('state', $state);
    $container->set('logger.factory', $logger_factory);
    $container->set('acquia_search.solarium.guzzle', $acquia_guzzle);
    $container->set('date.formatter', $date_formatter);
    $container->set('messenger', $messenger);
    $container->set('cache.default', $cache_default);
    $container->set('acquia_connector.subscription', $subscription);
    $container->set('acquia_search.api_client', $api_client);
    $container->set('datetime.time', $datetime_time);
    $container->set('acquia_search.preferred_core_factory', $preferred_core);
    $container->set('event_dispatcher', $event_dispatcher);
    $container->set('acquia_search.search_subscriber', $search_subscriber);
    $container->set('module_handler', $this->createMock(ModuleHandlerInterface::class));
    $container->set('config.factory', $this->getConfigFactoryStub([
      'search_api_solr.settings' => [],
    ]));
    $container->set('language_manager', $this->createMock(LanguageManagerInterface::class));

    $plugin_manager_search_api_solr_connector = $this->createMock(SolrConnectorPluginManager::class);
    $plugin_manager_search_api_solr_connector->method('createInstance')
      ->willReturnCallback(function (string $plugin_id, array $config) use ($container) {
        return SearchApiSolrAcquiaConnector::create($container, $config, $plugin_id, [
          'label' => 'Acquia Search Connector',
          'description' => '',
        ]);
      });
    $container->set('plugin.manager.search_api_solr.connector', $plugin_manager_search_api_solr_connector);
    $container->set('search_api.fields_helper', $this->createMock(FieldsHelperInterface::class));
    $container->set('search_api.data_type_helper', $this->createMock(DataTypeHelperInterface::class));
    $container->set('solarium.query_helper', new Helper());
    $container->set('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    $container->set('lock', $this->createMock(LockBackendInterface::class));
    $container->set('extension.list.module', $this->createMock(ModuleExtensionList::class));
    \Drupal::setContainer($container);
    return $container;
  }

}
