<?php

namespace Drupal\format_bytes\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extensions as filters for formate bytes.
 */
class ByteConversionTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('format_bytes', 'format_size'),
    ];
  }

}
