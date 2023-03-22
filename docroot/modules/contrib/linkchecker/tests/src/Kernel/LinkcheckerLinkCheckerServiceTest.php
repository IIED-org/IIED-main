<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Test for LinkCheckerService.
 *
 * @group linkchecker
 */
class LinkcheckerLinkCheckerServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'dynamic_entity_reference',
    'linkchecker',
  ];

  /**
   * Link checker service.
   *
   * @var \Drupal\linkchecker\LinkCheckerService
   */
  protected $checkerService;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $linkcheckerSetting;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig('linkchecker');

    // Create a mock and queue the responses.
    $mock = new MockHandler([
      new Response(200, []),
      new Response(200, [
        'Content-Type' => 'text/html',
        'Link' => ['bar', 'foo'],
      ], '<div id="foo"></div>'),
      new Response(200, [
        'Content-Type' => 'text/html',
        'Link' => ['foo', 'baz'],
      ], '<div id="foo"></div>'),
      new Response(200, [
        'Content-type' => 'text/html',
        'Link' => '<https://drupal.org>; rel="my-rel", <https://drupal.org>; rel=shortlink',
      ], '<div id="bar">This is bar</div>'),
      new Response(301, []),
      new Response(404, []),
      new Response(405, []),
      new Response(500, []),
      new Response(100, []),
    ]);

    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    // Second client for comparing results.
    // @see http://docs.guzzlephp.org/en/stable/testing.html
    $handler2 = HandlerStack::create(clone $mock);
    $client2 = new Client(['handler' => $handler2]);

    $this->container->set('http_client', $client);

    $this->checkerService = $this->container->get('linkchecker.checker');
    $this->httpClient = $client2;
    $this->time = $this->container->get('datetime.time');
    $this->linkcheckerSetting = $this->container->get('config.factory')
      ->getEditable('linkchecker.settings');
  }

  /**
   * Test link checker service status handling.
   */
  public function testStatusHandling() {
    $ignoreResponseCodes = preg_split('/(\r\n?|\n)/', $this->linkcheckerSetting->get('error.ignore_response_codes'));

    $urls = [
      '200',
      '200#foo',
      '200#baz',
      '200#bar',
      '301',
      '404',
      '405',
      '500',
      '100',
    ];

    $links = [];
    foreach ($urls as $url) {
      $links[] = $this->createDummyLink($url);
    }
    // Create duplicate link.
    $duplicateLink = $this->createDummyLink(reset($urls));

    /** @var \Drupal\linkchecker\LinkCheckerLinkInterface $link */
    foreach ($links as $link) {
      $linkBeforeUpdate = clone $link;
      $this->checkerService->check($link)->wait();
      // We use mocked http client where URL and status code are same.
      $response = $this->httpClient->request($linkBeforeUpdate->getRequestMethod(), $linkBeforeUpdate->getUrl(), ['http_errors' => FALSE]);

      $expectedStatusCode = $response->getStatusCode();
      $expectedErrorMessage = $response->getReasonPhrase();
      if ($link->getUrl() == '200#baz') {
        $expectedStatusCode = 404;
        $expectedErrorMessage = 'URL fragment identifier not found in content';
      }

      $this->assertEquals($expectedStatusCode, $link->getStatusCode(), new FormattableMarkup(
        'Expected status code is @expected. @actual is given',
        [
          '@expected' => $expectedStatusCode,
          '@actual' => $link->getStatusCode(),
        ]
      ));
      $this->assertEquals($expectedErrorMessage, $link->getErrorMessage(), new FormattableMarkup(
        'Expected error message is @expected. "@actual" is given',
        [
          '@expected' => $expectedErrorMessage,
          '@actual' => $link->getErrorMessage(),
        ]
      ));
      $this->assertGreaterThan(0, $link->getLastCheckTime(), new FormattableMarkup(
        'Expected last check time is greater than @expected. @actual is given',
        [
          '@expected' => 0,
          '@actual' => $link->getLastCheckTime(),
        ]
      ));

      if (in_array($expectedStatusCode, $ignoreResponseCodes)) {
        $this->assertEquals(0, $link->getFailCount(), new FormattableMarkup(
          'Expected fail count is @expected. @actual is given',
          [
            '@expected' => 0,
            '@actual' => $link->getFailCount(),
          ]
        ));
      }
      else {
        $this->assertEquals($linkBeforeUpdate->getFailCount() + 1, $link->getFailCount(), new FormattableMarkup(
          'Expected fail count is @expected. @actual is given',
          [
            '@expected' => $linkBeforeUpdate->getFailCount() + 1,
            '@actual' => $link->getFailCount(),
          ]
        ));
      }
    }

    // Check if duplicate link was updated.
    $link = reset($links);
    $duplicateLink = LinkCheckerLink::load($duplicateLink->id());
    $this->assertEquals($duplicateLink->getStatusCode(), $link->getStatusCode(), new FormattableMarkup(
      'Expected status code is @expected. @actual is given',
      [
        '@expected' => $duplicateLink->getStatusCode(),
        '@actual' => $link->getStatusCode(),
      ]
    ));
    $this->assertEquals($duplicateLink->getErrorMessage(), $link->getErrorMessage(), new FormattableMarkup(
      'Expected error message is @expected. "@actual" is given',
      [
        '@expected' => $duplicateLink->getErrorMessage(),
        '@actual' => $link->getErrorMessage(),
      ]
    ));
    $this->assertEquals($duplicateLink->getLastCheckTime(), $link->getLastCheckTime(), new FormattableMarkup(
      'Expected last check time is @expected. @actual is given',
      [
        '@expected' => $duplicateLink->getLastCheckTime(),
        '@actual' => $link->getLastCheckTime(),
      ]
    ));
    $this->assertEquals($duplicateLink->getFailCount(), $link->getFailCount(), new FormattableMarkup(
      'Expected fail count is @expected. @actual is given',
      [
        '@expected' => $duplicateLink->getFailCount(),
        '@actual' => $link->getFailCount(),
      ]
    ));
  }

  /**
   * Helper function for link creation.
   */
  protected function createDummyLink($url) {
    $link = LinkCheckerLink::create([
      'url' => $url,
      'entity_id' => [
        'target_id' => 1,
        'target_type' => 'dummy_type',
      ],
      'entity_field' => 'dummy_field',
      'entity_langcode' => 'en',
    ]);
    $link->save();
    return $link;
  }

}
