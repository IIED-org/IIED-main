<?php

namespace Drupal\devel_kint_extras\Plugin\Devel\Dumper;

use Drupal\devel\Plugin\Devel\Dumper\Kint;
use Kint\Parser\BlacklistPlugin;
use Kint\Renderer\RichRenderer;
use Psr\Container\ContainerInterface;

/**
 * Provides a Kint Extended dumper plugin.
 */
class KintExtended extends Kint {

  /**
   * Configures kint with more sane values.
   */
  protected function configure() {
    \Kint::$plugins = array_diff(\Kint::$plugins, [
      'Kint\\Parser\\IteratorPlugin',
    ]);
    \Kint::$aliases = $this->getInternalFunctions();

    RichRenderer::$folder = FALSE;
    BlacklistPlugin::$shallow_blacklist[] = ContainerInterface::class;
  }

}
