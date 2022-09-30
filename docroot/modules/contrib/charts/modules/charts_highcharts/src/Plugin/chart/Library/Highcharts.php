<?php

namespace Drupal\charts_highcharts\Plugin\chart\Library;

use Drupal\charts\Element\Chart as ChartElement;
use Drupal\charts\Plugin\chart\Library\ChartBase;
use Drupal\charts\TypeManager;
use Drupal\charts_highcharts\Form\ColorChanger;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a concrete class for a Highcharts.
 *
 * @Chart(
 *   id = "highcharts",
 *   name = @Translation("Highcharts")
 * )
 */
class Highcharts extends ChartBase implements ContainerFactoryPluginInterface {

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The chart type manager.
   *
   * @var \Drupal\charts\TypeManager
   */
  protected $chartTypeManager;

  /**
   * The chart type manager.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a \Drupal\views\Plugin\Block\ViewsBlockBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\charts\TypeManager $chart_type_manager
   *   The chart type manager.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ElementInfoManagerInterface $element_info, TypeManager $chart_type_manager, FormBuilder $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->elementInfo = $element_info;
    $this->chartTypeManager = $chart_type_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('element_info'),
      $container->get('plugin.manager.charts_type'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configurations = [
      'legend' => [
        'layout' => NULL,
        'background_color' => '',
        'border_width' => 0,
        'shadow' => FALSE,
        'item_style' => [
          'color' => '',
          'overflow' => '',
        ],
      ],
      'exporting_library' => TRUE,
      'texture_library' => TRUE,
    ] + parent::defaultConfiguration();

    return $configurations;
  }

  /**
   * Build configurations.
   *
   * @param array $form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Return the from.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['placeholder'] = [
      '#title' => $this->t('Placeholder'),
      '#type' => 'fieldset',
      '#description' => $this->t(
        'This is a placeholder for Highcharts-specific library options. If you would like to help build this out, please work from <a href="@issue_link">this issue</a>.', [
          '@issue_link' => Url::fromUri('https://www.drupal.org/project/charts/issues/3046981')
            ->toString(),
        ]),
    ];

    $legend_configuration = $this->configuration['legend'] ?? [];
    $form['legend'] = [
      '#title' => $this->t('Legend Settings'),
      '#type' => 'fieldset',
    ];

    $form['legend']['layout'] = [
      '#title' => $this->t('Legend layout'),
      '#type' => 'select',
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#default_value' => $legend_configuration['layout'] ?? NULL,
    ];
    $form['legend']['background_color'] = [
      '#title' => $this->t('Legend background color'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#attributes' => ['placeholder' => $this->t('transparent')],
      '#description' => $this->t('Leave blank for a transparent background.'),
      '#default_value' => $legend_configuration['background_color'] ?? '',
    ];
    $form['legend']['border_width'] = [
      '#title' => $this->t('Legend border width'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('None'),
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
      ],
      '#default_value' => $legend_configuration['border_width'] ?? 0,
    ];
    $form['legend']['shadow'] = [
      '#title' => $this->t('Enable legend shadow'),
      '#type' => 'checkbox',
      '#default_value' => !empty($legend_configuration['shadow']),
    ];

    $form['legend']['item_style'] = [
      '#title' => $this->t('Item Style'),
      '#type' => 'fieldset',
    ];
    $form['legend']['item_style']['color'] = [
      '#title' => $this->t('Item style color'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#attributes' => ['placeholder' => '#333333'],
      '#description' => $this->t('Leave blank for a dark gray font.'),
      '#default_value' => $legend_configuration['item_style']['color'] ?? '',
    ];
    $form['legend']['item_style']['overflow'] = [
      '#title' => $this->t('Text overflow'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('No'),
        'ellipsis' => $this->t('Ellipsis'),
      ],
      '#default_value' => $legend_configuration['item_style']['overflow'] ?? '',
    ];

    $form['exporting_library'] = [
      '#title' => $this->t('Enable Highcharts\' "Exporting" library'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->configuration['exporting_library']),
    ];

    $form['texture_library'] = [
      '#title' => $this->t('Enable Highcharts\' "Texture" library'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->configuration['texture_library']),
    ];

    return $form;
  }

  /**
   * Build configurations.
   *
   * @param array $form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['legend'] = $values['legend'];
      $this->configuration['exporting_library'] = $values['exporting_library'];
      $this->configuration['texture_library'] = $values['texture_library'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    // Populate chart settings.
    $chart_definition = [];

    $chart_definition = $this->populateOptions($element, $chart_definition);
    $chart_definition = $this->populateAxes($element, $chart_definition);
    $chart_definition = $this->populateData($element, $chart_definition);

    if (!empty($element['#height']) || !empty($element['#width'])) {
      $element['#attributes']['style'] = 'height:' . $element['#height'] . $element['#height_units'] . ';width:' . $element['#width'] . $element['#width_units'].  ';';
    }

    // Remove machine names from series. Highcharts series must be an array.
    $series = array_values($chart_definition['series']);
    unset($chart_definition['series']);

    // Trim out empty options (excluding "series" for efficiency).
    ChartElement::trimArray($chart_definition);

    // Put back the data.
    $chart_definition['series'] = $series;

    if (!isset($element['#id'])) {
      $element['#id'] = Html::getUniqueId('highchart-render');
    }

    $element['#attached']['library'][] = 'charts_highcharts/highcharts';
    if (!empty($this->configuration['exporting_library'])) {
      $element['#attached']['library'][] = 'charts_highcharts/highcharts_exporting';
    }
    if (!empty($this->configuration['texture_library'])) {
      $element['#attached']['library'][] = 'charts_highcharts/texture';
    }
    $element['#attributes']['class'][] = 'charts-highchart';
    $element['#chart_definition'] = $chart_definition;
    // Show a form on the front-end so users can change chart colors.
    if (!empty($element['#color_changer'])) {
      $form_state = new FormState();
      $form_state->set('chart_series', $series);
      $form_state->set('chart_id', $element['#id']);
      $form_state->set('chart_type', $chart_definition['chart']['type']);
      $form_state->set('y_axis', $chart_definition['yAxis']);
      $element['#attached']['library'][] = 'charts_highcharts/color_changer';
      $element['#content_suffix']['color_changer'] = $this->formBuilder->buildForm(ColorChanger::class, $form_state);
    }

    return $element;
  }

  /**
   * Populate options.
   *
   * @param array $element
   *   The element.
   * @param array $chart_definition
   *   The chart definition.
   *
   * @return array
   *   Return the chart definition.
   */
  private function populateOptions(array $element, array $chart_definition) {
    $chart_type = $this->getType($element['#chart_type']);
    $chart_definition['chart']['type'] = $chart_type;
    $chart_definition['chart']['backgroundColor'] = $element['#background'];
    $chart_definition['chart']['polar'] = $element['#polar'] ?? NULL;
    $chart_definition['chart']['options3d']['enabled'] = $element['#three_dimensional'] ?? NULL;
    $chart_definition['credits']['enabled'] = FALSE;
    $chart_definition['title']['text'] = $element['#title'] ?? '';
    $chart_definition['title']['style']['color'] = $element['#title_color'];
    $chart_definition['title']['verticalAlign'] = $element['#title_position'] === 'in' ? 'top' : NULL;
    $chart_definition['title']['y'] = $element['#title_position'] === 'in' ? 24 : NULL;
    $chart_definition['colors'] = $element['#colors'];

    $chart_definition['tooltip']['enabled'] = (bool) $element['#tooltips'];
    $chart_definition['tooltip']['useHTML'] = (bool) $element['#tooltips_use_html'];

    $chart_definition['plotOptions']['series']['stacking'] = $element['#stacking'] ?? '';
    $chart_definition['plotOptions']['series']['dataLabels']['enabled'] = (bool) $element['#data_labels'];

    if ($element['#chart_type'] === 'gauge') {
      $chart_definition['yAxis']['plotBands'][] = [
        'from' => $element['#gauge']['red_from'],
        'to' => $element['#gauge']['red_to'],
        'color' => 'red',
      ];
      $chart_definition['yAxis']['plotBands'][] = [
        'from' => $element['#gauge']['yellow_from'],
        'to' => $element['#gauge']['yellow_to'],
        'color' => 'yellow',
      ];
      $chart_definition['yAxis']['plotBands'][] = [
        'from' => $element['#gauge']['green_from'],
        'to' => $element['#gauge']['green_to'],
        'color' => 'green',
      ];
      $chart_definition['yAxis']['min'] = (int) $element['#gauge']['min'];
      $chart_definition['yAxis']['max'] = (int) $element['#gauge']['max'];
    }

    // These changes are for consistency with Google. Perhaps too specific?
    if ($element['#chart_type'] === 'pie') {
      $chart_definition['plotOptions']['pie']['dataLabels']['distance'] = -30;
      $chart_definition['plotOptions']['pie']['dataLabels']['color'] = 'white';
      $chart_definition['plotOptions']['pie']['dataLabels']['format'] = '{percentage:.1f}%';

      $chart_definition['tooltip']['pointFormat'] = '<b>{point.y} ({point.percentage:.1f}%)</b><br/>';
    }

    if ($element['#legend'] === TRUE) {
      $chart_definition['legend']['enabled'] = $element['#legend'];
      if (in_array($element['#chart_type'], ['pie', 'donut'])) {
        $chart_definition['plotOptions']['pie']['showInLegend'] = TRUE;
      }
      elseif ($element['#chart_type'] == 'gauge') {
        $chart_definition['plotOptions']['gauge']['showInLegend'] = TRUE;
      }
      if (!empty($element['#legend_title'])) {
        $chart_definition['legend']['title']['text'] = $element['#legend_title'];
      }

      if ($element['#legend_position'] === 'bottom') {
        $chart_definition['legend']['verticalAlign'] = 'bottom';
        $chart_definition['legend']['layout'] = 'horizontal';
      }
      elseif ($element['#legend_position'] === 'top') {
        $chart_definition['legend']['verticalAlign'] = 'top';
        $chart_definition['legend']['layout'] = 'horizontal';
      }
      else {
        $chart_definition['legend']['align'] = $element['#legend_position'];
        $chart_definition['legend']['verticalAlign'] = 'middle';
        $chart_definition['legend']['layout'] = 'vertical';
      }

      // Setting more legend configuration based on the plugin form entry.
      $legend_configuration = $this->configuration['legend'] ?? [];
      if (!empty($legend_configuration['layout'])) {
        $chart_definition['legend']['layout'] = $legend_configuration['layout'];
      }
      if (!empty($legend_configuration['background_color'])) {
        $chart_definition['legend']['backgroundColor'] = $legend_configuration['background_color'];
      }
      if (!empty($legend_configuration['border_width'])) {
        $chart_definition['legend']['borderWidth'] = $legend_configuration['border_width'];
      }
      if (!empty($legend_configuration['shadow'])) {
        $chart_definition['legend']['shadow'] = TRUE;
      }
      if (!empty($legend_configuration['item_style']['color'])) {
        $chart_definition['legend']['itemStyle']['color'] = $legend_configuration['item_style']['color'];
      }
      if (!empty($legend_configuration['item_style']['overflow'])) {
        $chart_definition['legend']['itemStyle']['overflow'] = $legend_configuration['item_style']['overflow'];
      }
    }
    else {
      $chart_definition['legend']['enabled'] = FALSE;
    }

    // Merge in chart raw options.
    if (!empty($element['#raw_options'])) {
      $chart_definition = NestedArray::mergeDeepArray([
        $chart_definition,
        $element['#raw_options'],
      ]);
    }

    return $chart_definition;
  }

  /**
   * Utility to populate data.
   *
   * @param array $element
   *   The element.
   * @param array $chart_definition
   *   The chart definition.
   *
   * @return array
   *   Return the chart definition.
   */
  private function populateData(array &$element, array $chart_definition) {
    $categories = [];
    $chart_type = $this->getType($element['#chart_type']);
    foreach (Element::children($element) as $key) {
      if ($element[$key]['#type'] === 'chart_xaxis' && !empty($element[$key]['#labels'])) {
        if ($chart_type === 'pie') {
          $categories = $element[$key]['#labels'];
          break;
        }
        $categories[] = $element[$key]['#labels'];
      }
    }
    foreach (Element::children($element) as $key) {
      if ($element[$key]['#type'] === 'chart_data') {
        $series = [];
        $series_data = [];

        // Make sure defaults are loaded.
        if (empty($element[$key]['#defaults_loaded'])) {
          $element[$key] += $this->elementInfo->getInfo($element[$key]['#type']);
        }

        // Convert target named axis keys to integers.
        if (isset($element[$key]['#target_axis'])) {
          $axis_name = $element[$key]['#target_axis'];
          $axis_index = 0;
          foreach (Element::children($element) as $axis_key) {
            if ($element[$axis_key]['#type'] === 'chart_yaxis') {
              if ($axis_key === $axis_name) {
                break;
              }
              $axis_index++;
            }
          }
          $series['yAxis'] = $axis_index;
        }

        // Allow data to provide the labels.
        // This will override the axis settings.
        if ($element[$key]['#labels'] && !in_array($element[$key]['#chart_type'], [
          'scatter',
          'bubble',
        ])) {
          foreach ($element[$key]['#labels'] as $label_index => $label) {
            $series_data[$label_index][0] = $label;
          }
        }
        elseif ($categories && $chart_type === 'pie') {
          foreach ($categories as $label_index => $label) {
            $series_data[$label_index][0] = $label;
          }
        }

        // Populate the data.
        foreach ($element[$key]['#data'] as $data_index => $data) {
          if (isset($series_data[$data_index])) {
            $series_data[$data_index][] = $data;
          }
          else {
            $series_data[$data_index] = $data;
          }
        }

        $series['type'] = $element[$key]['#chart_type'];
        if ($element['#chart_type'] === 'donut') {
          // Add innerSize to differentiate between donut and pie.
          $series['innerSize'] = '40%';
        }
        $series['name'] = $element[$key]['#title'];
        $series['color'] = $element[$key]['#color'];

        if ($element[$key]['#prefix'] || $element[$key]['#suffix']) {
          $yaxis_index = isset($series['yAxis']) ? $series['yAxis'] : 0;
          // For axis formatting, we need to use a format string.
          // See http://docs.highcharts.com/#formatting.
          $decimal_formatting = $element[$key]['#decimal_count'] ? (':.' . $element[$key]['#decimal_count'] . 'f') : '';
          $chart_definition['yAxis'][$yaxis_index]['labels']['format'] = $element[$key]['#prefix'] . "{value$decimal_formatting}" . $element[$key]['#suffix'];
        }

        // Remove unnecessary keys to trim down the resulting JS settings.
        ChartElement::trimArray($series);

        // If you want a different type of scatter.
        if (!empty($element['#alternative_scatter'])) {
          $series = $series_data;
        }
        else {
          $series['data'] = $series_data;
        }

        // Merge in series raw options.
        if (!empty($element[$key]['#raw_options'])) {
          $series = NestedArray::mergeDeepArray([
            $series,
            $element[$key]['#raw_options'],
          ]);
        }

        // Add the series to the main chart definition.
        // Scatter colors adjustment.
        if (!empty($element['#alternative_scatter'])) {
          $chart_definition['series'] = $series;
        }
        else {
          $chart_definition['series'][$key] = $series;
        }

        // Merge in any point-specific data points.
        foreach (Element::children($element[$key]) as $sub_key) {
          if ($element[$key][$sub_key]['#type'] === 'chart_data_item') {
            // Make sure defaults are loaded.
            if (empty($element[$key][$sub_key]['#defaults_loaded'])) {
              $element[$key][$sub_key] += $this->elementInfo->getInfo($element[$key][$sub_key]['#type']);
            }

            $data_item = $element[$key][$sub_key];
            $series_point = &$chart_definition['series'][$key]['data'][$sub_key];

            // Convert the point from a simple data value to a complex point.
            if (!isset($series_point['data'])) {
              $data = $series_point;
              $series_point = [];
              if (is_array($data)) {
                $series_point['name'] = $data[0];
                $series_point['y'] = $data[1];
              }
              else {
                $series_point['y'] = $data;
              }
            }
            if (isset($data_item['#data'])) {
              if (is_array($data_item['#data'])) {
                $series_point['x'] = $data_item['#data'][0];
                $series_point['y'] = $data_item['#data'][1];
              }
              else {
                $series_point['y'] = $data_item['#data'];
              }
            }
            if ($data_item['#title']) {
              $series_point['name'] = $data_item['#title'];
            }

            // Setting the color requires several properties for consistency.
            $series_point['color'] = $data_item['#color'];
            $series_point['fillColor'] = $data_item['#color'];
            $series_point['states']['hover']['fillColor'] = $data_item['#color'];
            $series_point['states']['select']['fillColor'] = $data_item['#color'];
            ChartElement::trimArray($series_point);

            // Merge in point raw options.
            if (!empty($data_item['#raw_options'])) {
              $series_point = NestedArray::mergeDeepArray([
                $series_point,
                $data_item['#raw_options'],
              ]);
            }
          }
        }
      }
    }

    return $chart_definition;
  }

  /**
   * Populate axes.
   *
   * @param array $element
   *   The element.
   * @param array $chart_definition
   *   The chart definition.
   *
   * @return array
   *   Return the chart definition.
   */
  private function populateAxes(array $element, array $chart_definition) {
    foreach (Element::children($element) as $key) {
      if ($element[$key]['#type'] === 'chart_xaxis' || $element[$key]['#type'] === 'chart_yaxis') {
        // Make sure defaults are loaded.
        if (empty($element[$key]['#defaults_loaded'])) {
          $element[$key] += $this->elementInfo->getInfo($element[$key]['#type']);
        }

        // Populate the chart data.
        $axis_type = $element[$key]['#type'] === 'chart_xaxis' ? 'xAxis' : 'yAxis';
        $axis = [];
        $axis['type'] = $element[$key]['#axis_type'];
        $axis['title']['text'] = $element[$key]['#title'];
        $axis['title']['style']['color'] = $element[$key]['#title_color'];
        if (!empty($element[$key]['#labels'])) {
          $axis['categories'] = $element[$key]['#labels'];
        }
        $axis['labels']['style']['color'] = $element[$key]['#labels_color'];
        $axis['labels']['rotation'] = $element[$key]['#labels_rotation'];
        $axis['gridLineColor'] = $element[$key]['#grid_line_color'];
        $axis['lineColor'] = $element[$key]['#base_line_color'];
        $axis['minorGridLineColor'] = $element[$key]['#minor_grid_line_color'];
        $axis['endOnTick'] = isset($element[$key]['#max']) ? FALSE : NULL;
        $axis['max'] = $element[$key]['#max'];
        $axis['min'] = $element[$key]['#min'];
        $axis['opposite'] = $element[$key]['#opposite'];

        if ($axis['labels']['rotation']) {
          $chart_type = $this->chartTypeManager->getDefinition($element['#chart_type']);
          if ($axis_type === 'xAxis' && !$chart_type['axis_inverted']) {
            $axis['labels']['align'] = 'left';
          }
          elseif ($axis_type === 'yAxis' && $chart_type['axis_inverted']) {
            $axis['labels']['align'] = 'left';
          }
        }

        // Merge in axis raw options.
        if (!empty($element[$key]['#raw_options'])) {
          $axis = NestedArray::mergeDeepArray([
            $axis,
            $element[$key]['#raw_options'],
          ]);
        }

        $chart_definition[$axis_type][] = $axis;
      }
    }

    return $chart_definition;
  }

  /**
   * The chart type.
   *
   * @param string $type
   *   The chart type.
   *
   * @return string
   *   Return the chart type.
   */
  private function getType($type) {
    if ($type === 'donut') {
      $type = 'pie';
    }

    return $type;
  }

}
