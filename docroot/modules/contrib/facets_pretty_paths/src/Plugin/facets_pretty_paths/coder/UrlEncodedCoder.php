<?php

namespace Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder;

use Drupal\facets_pretty_paths\Coder\CoderPluginBase;

/**
 * URL-encoded facets pretty paths coder.
 *
 * Forward slashes, even URL-encoded, cannot safely occur in a path part, so
 * these are further encoded to "--slash--". This ensures they do not appear as
 * forward slashes to web servers, yet remain human-readable.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "url_encoded_coder",
 *   label = @Translation("URL encoded"),
 *   description = @Translation("Use the URL-encoded raw value, e.g. /color/<strong>2</strong>. Ensures values containing special or reserved characters do not break the pretty paths URL.")
 * )
 */
class UrlEncodedCoder extends CoderPluginBase {

  /**
   * Encode an id into an alias.
   *
   * @param string $id
   *   An entity id.
   *
   * @return string
   *   An alias.
   */
  public function encode($id) {
    return rawurlencode(str_replace('/', $this->t('--slash--'), $id));
  }

  /**
   * Decodes an alias back to an id.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   An id.
   */
  public function decode($alias) {
    return str_replace($this->t('--slash--'), '/', rawurldecode($alias));
  }

}
