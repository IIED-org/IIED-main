<?php

namespace Drupal\hal\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for hal.
 */
class HalHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.hal':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('<a href=":hal_spec">Hypertext Application Language (HAL)</a> is a format that supports the linking required for hypermedia APIs.', [
          ':hal_spec' => 'http://stateless.co/hal_specification.html',
        ]) . '</p>';
        $output .= '<p>' . $this->t('Hypermedia APIs are a style of Web API that uses URIs to identify resources and the <a href="http://wikipedia.org/wiki/Link_relation">link relations</a> between them, enabling API consumers to follow links to discover API functionality.') . '</p>';
        $output .= '<p>' . $this->t('This module adds support for serializing entities (such as content items, taxonomy terms, etc.) to the JSON version of HAL. For more information, see the <a href=":hal_do">online documentation for the HAL module</a>.', [
          ':hal_do' => 'https://www.drupal.org/documentation/modules/hal',
        ]) . '</p>';
        return $output;
    }
  }

}
