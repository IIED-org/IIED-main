<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\acquia_connector\Subscription;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Checks the current status of the Acquia Service.
 */
class StatusController extends ControllerBase {

  /**
   * Acquia Subscription Service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Page Cache Kill Switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Current Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * WebhooksSettingsForm constructor.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   The event dispatcher.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   The client factory.
   * @param \Drupal\Core\Http\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(Subscription $subscription, KillSwitch $kill_switch, RequestStack $request_stack) {
    $this->subscription = $subscription;
    $this->killSwitch = $kill_switch;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_connector.subscription'),
      $container->get('page_cache_kill_switch'),
      $container->get('request_stack')
    );
  }

  /**
   * Menu callback for 'admin/config/services/acquia-agent/refresh-status'.
   */
  public function refresh() {
    // Refresh subscription information, so we are sure about our update status.
    // We send a heartbeat here so that all of our status information gets
    // updated locally via the return data.
    $this->subscription->getSubscription(TRUE);

    // Return to the setting pages (or destination).
    return $this->redirect('system.status');
  }

  /**
   * Return JSON site status.
   *
   * Used by Acquia uptime monitoring.
   */
  public function json() {
    // We don't want this page cached.
    $this->killSwitch->trigger();

    $performance_config = $this->config('system.performance');

    $data = [
      'version' => '1.0',
      'data' => [
        'maintenance_mode' => (bool) $this->state()->get('system.maintenance_mode'),
        'cache' => $performance_config->get('cache.page.use_internal'),
        'block_cache' => FALSE,
      ],
    ];

    return new JsonResponse($data);
  }

  /**
   * Access callback for json() callback.
   */
  public function access() {
    $nonce = $this->request->get('nonce', FALSE);
    $connector_config = $this->config('acquia_connector.settings');

    // If we don't have all the query params, leave now.
    if (!$nonce) {
      return AccessResultForbidden::forbidden();
    }

    $sub_data = $this->subscription->getSubscription();
    $sub_uuid = $sub_data['uuid'];

    $expected_hash = '';

    if (!empty($sub_uuid)) {
      $expected_hash = hash('sha1', "{$sub_uuid}:{$nonce}");

      // If the generated hash matches the hash from $_GET['key'], we're good.
      if ($this->request->get('key', FALSE) === $expected_hash) {
        return AccessResultAllowed::allowed();
      }
    }

    // Log the request if validation failed and debug is enabled.
    if ($connector_config->get('debug')) {
      $info = [
        'sub_data' => $sub_data,
        'sub_uuid_from_data' => $sub_uuid,
        'expected_hash' => $expected_hash,
        'get' => $this->request->query->all(),
        'server' => $this->request->server->all(),
        'request' => $this->request->request->all(),
      ];

      $this->getLogger('acquia_agent')->notice('Site status request: @data', ['@data' => var_export($info, TRUE)]);
    }

    return AccessResultForbidden::forbidden();
  }

}
