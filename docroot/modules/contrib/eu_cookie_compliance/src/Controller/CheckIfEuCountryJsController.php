<?php

namespace Drupal\eu_cookie_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for JS call that checks if the visitor is in the EU.
 */
class CheckIfEuCountryJsController extends ControllerBase {

  /**
   * Check if visitor is in the EU.
   *
   * @return JsonResponse
   *   Whether the user is in EU.
   */
  public function content() {
    $data = eu_cookie_compliance_user_in_eu();

    // Allow other modules to alter the geo IP matching logic.
    \Drupal::moduleHandler()->alter('eu_cookie_compliance_geoip_match', $data);

    return new JsonResponse($data, 200, ['Cache-Control' => 'private']);
  }

}
