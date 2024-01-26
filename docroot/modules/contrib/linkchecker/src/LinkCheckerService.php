<?php

namespace Drupal\linkchecker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\linkchecker\Event\BuildHeader;
use Drupal\linkchecker\Event\LinkcheckerEvents;
use Drupal\linkchecker\Plugin\LinkStatusHandlerManager;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LinkCheckerService.
 */
class LinkCheckerService {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $linkcheckerSetting;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The report link.
   *
   * @var \Drupal\Core\Link
   */
  protected $reportLink;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The status handler manager.
   *
   * @var \Drupal\linkchecker\Plugin\LinkStatusHandlerManager
   */
  protected $statusHandlerManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Constructs a new LinkCheckerService object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactory $config, Client $httpClient, TimeInterface $time, QueueFactory $queueFactory, LinkStatusHandlerManager $statusHandlerManager, EventDispatcherInterface $eventDispatcher) {
    $this->entityTypeManager = $entityTypeManager;
    $this->linkcheckerSetting = $config->get('linkchecker.settings');
    $this->httpClient = $httpClient;
    $this->time = $time;
    $this->queue = $queueFactory->get('linkchecker_check');
    $this->statusHandlerManager = $statusHandlerManager;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Queue all links for checking.
   *
   * @param bool $rebuild
   *   Defines whether rebuild queue or not.
   *
   * @return int
   *   Nubmer of queued items.
   */
  public function queueLinks($rebuild = FALSE) {
    if ($rebuild) {
      $this->queue->deleteQueue();
    }

    if (!empty($this->queue->numberOfItems())) {
      return $this->queue->numberOfItems();
    }

    $checkInterval = $this->linkcheckerSetting->get('check.interval');
    $query = $this->entityTypeManager->getStorage('linkcheckerlink')->getAggregateQuery()->accessCheck();
    $orGroup = $query->orConditionGroup()
      ->condition('last_check', $this->time->getRequestTime() - $checkInterval, '<=')
      ->condition('last_check', NULL, 'IS NULL');
    $query->groupBy('urlhash')
      ->aggregate('lid', 'MIN')
      ->condition($orGroup);
    $linkIds = $query->execute();

    $this->queue->createQueue();

    if (!empty($linkIds)) {
      $linkIds = array_column($linkIds, 'lid_min');
      $maxConnections = $this->linkcheckerSetting->get('check.connections_max');
      // Split ids by max connection amount to make possible send concurrent
      // requests.
      $linkIds = array_chunk($linkIds, $maxConnections);
    }
    else {
      $linkIds = [];
    }

    foreach ($linkIds as $ids) {
      $this->queue->createItem($ids);
    }

    return $this->queue->numberOfItems();
  }

  /**
   * Check the link.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link to check.
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   *   Promise of link checking request.
   */
  public function check(LinkCheckerLinkInterface $link) {
    $userAgent = $this->linkcheckerSetting->get('check.useragent');

    $headers = [];
    $headers['User-Agent'] = $userAgent;

    $uri = @parse_url($link->getUrl());

    // URL contains a fragment.
    if (in_array($link->getRequestMethod(), ['HEAD', 'GET']) && !empty($uri['fragment'])) {
      // We need the full content and not only the HEAD.
      $link->setRequestMethod('GET');
      // Request text content only (like Firefox/Chrome).
      $headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    }
    elseif ($link->getRequestMethod() == 'GET') {
      // Range: Only request the first 1024 bytes from remote server. This is
      // required to prevent timeouts on URLs that are large downloads.
      $headers['Range'] = 'bytes=0-1024';
    }

    // Allow other modules to alter the header.
    $context = [
      'method' => $link->getRequestMethod(),
      'url' => $link->getUrl(),
    ];
    $event = new BuildHeader($headers, $context);
    $this->eventDispatcher->dispatch($event, LinkcheckerEvents::BUILD_HEADER);
    $headers = $event->getHeaders();

    // Add in the headers.
    $options = [
      'headers' => $headers,
      'max_redirects' => 0,
      'http_errors' => FALSE,
      'allow_redirects' => FALSE,
      'synchronous' => FALSE,
    ];

    return $this->httpClient
      ->requestAsync($link->getRequestMethod(), $link->getUrl(), $options)
      ->then(function (ResponseInterface $response) use ($link, $uri) {
        if (!empty($uri['fragment'])) {
          $response = $response->withHeader('Fragment', $uri['fragment']);
        }
        $this->statusHandling($response, $link);
      },
      function (RequestException $e) use ($link) {
        $this->exceptionHandling($e, $link);
      }
    );
  }

  /**
   * Status code handling.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   An object containing the HTTP request headers, response code, headers,
   *   data and redirect status.
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link.
   */
  protected function statusHandling(ResponseInterface $response, LinkCheckerLinkInterface $link) {
    $ignoreResponseCodes = preg_split('/(\r\n?|\n)/', $this->linkcheckerSetting->get('error.ignore_response_codes'));

    $error = $response->getReasonPhrase();
    if (!isset($error)) {
      $error = '';
    }

    // Destination anchors in HTML documents may be specified either by:
    // - the A element (naming it with the name attribute)
    // - or by any other element (naming with the id attribute)
    // - and must not contain a key/value pair as these type of hash fragments
    //   are typically used by AJAX applications to prevent additionally HTTP
    //   requests e.g. https://www.example.com/ajax.html#key1=value1&key2=value2
    // - and must not contain '/' or ',' as this are not normal anchors.
    // - and '#top' is a reserved fragment that must not exist in a page.
    // See https://www.w3.org/TR/html401/struct/links.html
    $statusCode = $response->getStatusCode();
    if ($statusCode == 200
      && !empty($response->getBody())
      && !empty($response->getHeader('Content-Type'))
      && $response->hasHeader('Fragment')
      && preg_match('/=|\/|,/', $response->getHeaderLine('Fragment')) == FALSE
      && $response->getHeader('Fragment') !== '#top'
      && in_array($response->getHeaderLine('Content-Type'), [
        'text/html',
        'application/xhtml+xml',
        'application/xml',
      ])
      && !preg_match('/(\s[^>]*(name|id)(\s+)?=(\s+)?["\'])(' . preg_quote(urldecode($response->getHeaderLine('Fragment')), '/') . ')(["\'][^>]*>)/i', $response->getBody())
    ) {
      // Override status code 200 with status code 404 so it can be handled with
      // default status code 404 logic and custom error text.
      $statusCode = 404;
      $error = 'URL fragment identifier not found in content';
    }

    switch ($statusCode) {
      case 301:
        $link->setStatusCode($statusCode);
        $link->setErrorMessage($error);
        $link->setFailCount($link->getFailCount() + 1);
        $link->setLastCheckTime($this->time->getCurrentTime());
        $link->save();
        linkchecker_watchdog_log('linkchecker', 'Link %link has changed and needs to be updated.', [
          '%link' => $link->getUrl(),
        ], RfcLogLevel::NOTICE, $this->getReportLink());
        break;

      case 404:
        $link->setStatusCode($statusCode);
        $link->setErrorMessage($error);
        $link->setFailCount($link->getFailCount() + 1);
        $link->setLastCheckTime($this->time->getCurrentTime());
        $link->save();

        linkchecker_watchdog_log('linkchecker', 'Broken link %link has been found.', [
          '%link' => $link->getUrl(),
        ], RfcLogLevel::NOTICE, $this->getReportLink());
        break;

      case 405:
        // - 405: Special error handling if method is not allowed. Switch link
        //   checking to GET method and try again.
        $link->setRequestMethod('GET');
        $link->setStatusCode($statusCode);
        $link->setErrorMessage($error);
        $link->setFailCount($link->getFailCount() + 1);
        $link->setLastCheckTime($this->time->getCurrentTime());
        $link->save();

        linkchecker_watchdog_log('linkchecker', 'Method HEAD is not allowed for link %link. Method has been changed to GET.', [
          '%link' => $link->getUrl(),
        ], RfcLogLevel::NOTICE, $this->getReportLink());
        break;

      case 500:
        // - 500: Like WGET, try with GET on "500 Internal server error".
        // - If GET also fails with status code 500, than the link is broken.
        if ($link->getRequestMethod() == 'GET') {
          $link->setStatusCode($statusCode);
          $link->setErrorMessage($error);
          $link->setFailCount($link->getFailCount() + 1);
          $link->setLastCheckTime($this->time->getCurrentTime());
          $link->save();

          linkchecker_watchdog_log('linkchecker', 'Broken link %link has been found.', [
            '%link' => $link->getUrl(),
          ], RfcLogLevel::NOTICE, $this->getReportLink());
        }
        else {
          $link->setRequestMethod('GET');
          $link->setStatusCode($statusCode);
          $link->setErrorMessage($error);
          $link->setFailCount($link->getFailCount() + 1);
          $link->setLastCheckTime($this->time->getCurrentTime());
          $link->save();

          linkchecker_watchdog_log('linkchecker', 'Internal server error for link %link. Method has been changed to GET.', [
            '%link' => $link->getUrl(),
          ], RfcLogLevel::NOTICE, $this->getReportLink());
        }
        break;

      default:
        // Don't treat ignored response codes as errors.
        if (in_array($statusCode, $ignoreResponseCodes)) {
          $link->setStatusCode($statusCode);
          $link->setErrorMessage($error);
          $link->setFailCount(0);
          $link->setLastCheckTime($this->time->getCurrentTime());
          $link->save();
        }
        else {
          $link->setStatusCode($statusCode);
          $link->setErrorMessage($error);
          $link->setFailCount($link->getFailCount() + 1);
          $link->setLastCheckTime($this->time->getCurrentTime());
          $link->save();

          linkchecker_watchdog_log('linkchecker', 'Unhandled link error %link has been found.', [
            '%link' => $link->getUrl(),
          ], RfcLogLevel::ERROR, $this->getReportLink());
        }
    }

    $this->updateSameLinks($link);

    foreach ($this->statusHandlerManager->getDefinitions() as $definition) {
      if (in_array($statusCode, $definition['status_codes'])) {
        /** @var \Drupal\linkchecker\Plugin\LinkStatusHandlerInterface $handler */
        $handler = $this->statusHandlerManager->createInstance($definition['id']);
        $handler->queueItems($link, $response);
      }
    }
  }

  /**
   * Exception handling.
   *
   * @param \GuzzleHttp\Exception\RequestException $e
   *   An object containing the Exception.
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link.
   */
  protected function exceptionHandling(RequestException $e, LinkCheckerLinkInterface $link) {
    $link->setStatusCode('502');
    $link->setErrorMessage($e->getMessage());
    $link->setFailCount($link->getFailCount() + 1);
    $link->setLastCheckTime($this->time->getCurrentTime());
    $link->save();

    linkchecker_watchdog_log('linkchecker', 'Unhandled link error %link has been found: : %message.', [
      '%link' => $link->getUrl(),
      '%message' => $e->getMessage(),
    ], RfcLogLevel::ERROR, $this->getReportLink());

    $this->updateSameLinks($link);
  }

  /**
   * Helper function to create report link.
   */
  protected function getReportLink() {
    if (!isset($this->reportLink)) {
      $this->reportLink = Link::fromTextAndUrl($this->t('Broken links'), Url::fromUserInput('/admin/reports/linkchecker'));
    }
    return $this->reportLink;
  }

  /**
   * Helper function to update same links that were found in other entities.
   */
  protected function updateSameLinks(LinkCheckerLinkInterface $link) {
    $hash = $link->getHash();
    // If there is no hash, return early.
    if (is_null($hash)) {
      return;
    }
    $storage = $this->entityTypeManager->getStorage($link->getEntityTypeId());
    $query = $storage->getQuery();
    $query->accessCheck();
    $query->condition('urlhash', $hash);
    $query->condition('lid', $link->id(), '!=');
    $ids = $query->execute();

    foreach ($ids as $id) {
      /** @var \Drupal\linkchecker\LinkCheckerLinkInterface $linkToUpdate */
      $linkToUpdate = $storage->load($id);

      $linkToUpdate->setRequestMethod($link->getRequestMethod());
      $linkToUpdate->setStatusCode($link->getStatusCode());
      $linkToUpdate->setErrorMessage($link->getErrorMessage());
      $linkToUpdate->setFailCount($link->getFailCount());
      $linkToUpdate->setLastCheckTime($link->getLastCheckTime());

      $linkToUpdate->save();
    }
  }

}
