<?php

namespace Drupal\charts\Plugin\chart;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Chart plugins.
 */
interface ChartInterface extends PluginInspectionInterface {

  /**
   * Build Variables.
   *
   * @param $options
   * @param $categories
   * @param $seriesData
   * @param $attachmentDisplayOptions
   * @param $variables
   * @param $chartId
   * @param array $customOptions
   *
   * @return array
   *   Variables.
   */
  public function buildVariables($options, $categories, $seriesData, $attachmentDisplayOptions, &$variables, $chartId, $customOptions = []);

  /**
   * Return the name of the chart.
   *
   * @return string
   *   Returns the name as a string.
   */
  public function getChartName();

}
