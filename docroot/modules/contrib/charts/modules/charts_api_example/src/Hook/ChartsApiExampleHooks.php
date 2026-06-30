<?php

declare(strict_types=1);

namespace Drupal\charts_api_example\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the Charts API Example module.
 */
class ChartsApiExampleHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   *
   * @param string $route_name
   *   The route name.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The help HTML.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string {
    $output = '';
    switch ($route_name) {
      // Help for the charts_api_example module.
      case 'help.page.charts_api_example':
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('A simple example on how to interact with the Charts API') . '</p>';
        break;
    }
    return $output;
  }

  /**
   * Implements hook_chart_definition_CHART_ID_alter().
   *
   * @param array $chart
   *   The chart element.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('chart_definition_example_id_php_alter')]
  public function chartDefinitionExampleIdPhpAlter(array &$chart): void {
    // Please note: this will only show when the charts_highcharts module is
    // enabled and Highcharts is set as your default library.
    $chart['chart']['backgroundColor'] = 'blue';
  }

  /**
   * Implements hook_chart_alter().
   *
   * @param array $element
   *   The chart element.
   * @param string|null $chart_id
   *   The chart ID.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('chart_alter')]
  public function chartAlter(array &$element, ?string $chart_id): void {
    // Please note: this will only show when the charts_highcharts module is
    // enabled and Highcharts is set as your default library.
    if ($chart_id === 'example_id_js_chart') {
      $element['#attached']['library'][] = 'charts_api_example/charts_api_example';
    }
  }

}
