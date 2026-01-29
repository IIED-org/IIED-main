<?php

declare(strict_types=1);

namespace Drupal\matomo\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP policy for Matomo Analytics.
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!\class_exists(CspEvents::class)) {
      return [];
    }

    $events = [];
    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter'];
    return $events;
  }

  /**
   * Alter CSP policy to allow inline Javascript.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent) {
    $policy = $alterEvent->getPolicy();
    $response = $alterEvent->getResponse();

    if ($response instanceof AttachmentsInterface) {
      $attachments = $response->getAttachments();

      if (isset($attachments['html_head'])) {
        foreach ($attachments['html_head'] as $head_element) {
          if ($head_element[1] == 'matomo_tracking_script') {
            $config = $this->configFactory->get('matomo.settings');
            $url_https = $config->get('url_https');
            $filtered_url_https = UrlHelper::filterBadProtocol($url_https);

            $matomo_requirements = [
              Csp::POLICY_UNSAFE_INLINE,
              $filtered_url_https,
            ];

            // Script-src.
            $policy->fallbackAwareAppendIfEnabled('script-src', $matomo_requirements);

            // Connect-src.
            $policy->fallbackAwareAppendIfEnabled('connect-src', [$filtered_url_https]);
            break;
          }
        }
      }
    }
  }

}
