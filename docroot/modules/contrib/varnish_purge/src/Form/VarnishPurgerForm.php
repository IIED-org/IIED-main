<?php

namespace Drupal\varnish_purger\Form;

/**
 * Configuration form for the Varnish Bundled Purger.
 */
class VarnishPurgerForm extends VarnishPurgerFormBase {

  /**
   * The token group names this purger supports replacing tokens for.
   *
   * @var string[]
   *
   * @see purge_tokens_token_info()
   */
  protected $tokenGroups = ['invalidation'];

}
