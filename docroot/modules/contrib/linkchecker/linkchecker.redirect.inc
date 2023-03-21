<?php

/**
 * @file
 * Redirect interface to linkchecker functionalities.
 */

use Drupal\Core\Url;

/**
 * Implements hook_redirect_insert().
 */
function linkchecker_redirect_insert($redirect) {
  linkchecker_redirect_update($redirect);
}

/**
 * Implements hook_redirect_update().
 */
function linkchecker_redirect_update($redirect) {
  // Get Source URL.
  $source = $redirect->getSourceUrl();

  // Get host domain.
  $host = \Drupal::request()->getSchemeAndHttpHost();

  // It's unknown if this is a redirect for HTTP/HTTPS or the encoded urls.
  $url_http = Url::fromUri('internal:' . $source)->toString();
  $url_https = Url::fromUri('internal:' . $source, ['https' => TRUE])->toString();
  $full_url_http = Url::fromUri($host . $source, ['https' => FALSE])->toString();
  $full_url_https = Url::fromUri($host . $source, ['https' => TRUE])->toString();

  $urls = [
    $source,
    $url_http,
    $url_https,
    $full_url_http,
    $full_url_https,
    rawurldecode($source),
    rawurldecode($url_http),
    rawurldecode($url_https),
    rawurldecode($full_url_http),
    rawurldecode($full_url_https),
  ];

  _linkchecker_redirect_reset($urls);
}

/**
 * Reset last_checked status.
 *
 * @param array $urls
 *   An array of urls that should be checked on next cron run.
 */
function _linkchecker_redirect_reset(array $urls = []) {
  $urls = array_unique($urls);

  $linkCheckerLinkStorage = \Drupal::entityTypeManager()->getStorage('linkcheckerlink');
  $query = $linkCheckerLinkStorage->getQuery();
  $query->accessCheck();
  $query->condition('urlhash', array_map('\Drupal\Component\Utility\Crypt::hashBase64', $urls), 'IN');
  $query->condition('fail_count', 0, '>');
  $query->condition('status', 1);
  $linkcheckerLinkIds = $query->execute();

  if (empty($linkcheckerLinkIds)) {
    return;
  }

  $num_updated = 0;
  $linkcheckerLinks = $linkCheckerLinkStorage->loadMultiple($linkcheckerLinkIds);

  foreach ($linkcheckerLinks as $linkcheckerLink) {
    $linkcheckerLink->set('last_check', NULL);
    $linkcheckerLink->save();
    $num_updated++;
  }

  if ($num_updated) {
    \Drupal::messenger()->addMessage(t('The link %url will be checked again on the next cron run.', ['%url' => $urls[0]]));
  }
}
