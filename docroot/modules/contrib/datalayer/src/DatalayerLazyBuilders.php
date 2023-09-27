<?php

namespace Drupal\datalayer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Lazy builders for the datalayer module.
 */
class DatalayerLazyBuilders implements TrustedCallbackInterface {

  /**
   * The Router Admin Context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $router_admin_context;

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new DatalayerLazyBuilders object.
   */
  public function __construct(ConfigFactoryInterface $config, AdminContext $router_admin_context) {
    $this->config = $config;
    $this->router_admin_context = $router_admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['lazyScriptTag'];
  }

  /**
   * #lazy_builder callback; builds script tag with user-specific info.
   *
   * @return array
   *   A renderable array.
   */
  public function lazyScriptTag() {
    $datalayer_attachment = datalayer_get_data_from_page();
    $datalayer_settings = $this->config->get('datalayer.settings');
    $build['datalayer'] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#access' => !$this->router_admin_context->isAdminRoute() || $datalayer_settings->get('remove_from_admin_routes') === FALSE,
      // Use json_encode() instead of
      // Drupal\Component\Serialization\Json::encode() because we want to pass
      // the additional JSON_UNESCAPED_UNICODE flag.
      '#value' => 'window.dataLayer = window.dataLayer || []; window.dataLayer.push(' . json_encode($datalayer_attachment, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ');',
    ];
    return $build;
  }

}
