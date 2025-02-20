<?php

namespace Drupal\varnish_purger\Tests;

/**
 * Tests \Drupal\varnish_purger\Form\VarnishBundledPurgerForm.
 *
 * @group varnish_purger
 */
class VarnishBundledPurgerFormTest extends VarnishPurgerFormTestBase {

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\varnish_purger\Form\VarnishBundledPurgerForm';

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'varnishbundled';

  /**
   * The token group names the form is supposed to display.
   *
   * @var string[]
   *
   * @see purge_tokens_token_info()
   */
  protected $tokenGroups = ['invalidations'];

}
