<?php
namespace Drupal\views_layout\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;

/**
 * Style plugin for the timeline view.
 *
 * @ViewsStyle(
 *   id = "views_layout_style",
 *   title = @Translation("Views Layout Grid"),
 *   help = @Translation("Displays content in a grid defined by a layout."),
 *   theme = "views_layout_style",
 *   display_types = {"normal"}
 * )
 */
class ViewsLayoutStyle extends StylePluginBase {

  /**
   * Does the style plugin allows to use row plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = FALSE;

  /**
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  private $layoutPluginManager;

  /**
   * Array of all possible layouts.
   */
  private $layouts;

  /**
   * Array of all possible regions, keyed by layouts.
   */
  private $regions;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LayoutPluginManagerInterface $layout_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->setRegions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.core.layout'));
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['layout_name'] = array('default' => '');
    $options['layout_regions'] = array('default' => []);
    $options['skip_processing'] = array('default' => '');
    $options['skip_text'] = array('default' => []);
    $options['callback'] = array('default' => '');

    return $options;
  }

  protected function setRegions() {
    $layouts = [];
    $regions = [];
    $definitions = $this->layoutPluginManager->getDefinitions('layout_builder');
    foreach ($definitions as $plugin_id => $definition) {
      $icon_values = $definition->getIcon(45, 60, 1, 3);
      $icon =  \Drupal::service('renderer')->renderPlain($icon_values);
      $layouts[$plugin_id] = ['icon' => $icon, 'name' => $definition->getLabel()];
      $regions[$plugin_id]['label'] = $this->t(':name', [':name' => $definition->getLabel()]);
      $regions[$plugin_id]['icon'] = $icon;
      foreach ($definition->getRegionLabels() as $region_id => $label) {
        $regions[$plugin_id]['regions'][$region_id] = $label;
      }
    }
    $this->layouts = $layouts;
    $this->regions = $regions;
  }

  /**
   * Builds the configuration form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);

    $header = [
      'icon' => $this->t('Icon'),
      'name' => $this->t('Name'),
    ];
    $form['layout_name'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $this->layouts,
      '#default_value' => $this->options['layout_name'],
      '#multiple' => FALSE,
    ];

    foreach ($this->regions as $plugin_id => $region) {
      $default_values = $this->options['layout_name'] == $plugin_id ? $this->options['layout_regions'][$plugin_id] : [];
      $form['layout_regions'][$plugin_id] = [
        '#type' => 'checkboxes',
        '#options' => $region['regions'],
        '#title' => $this->t(':name Regions', [':name' => $region['label']]),
        '#description' => $this->t("Check the regions in this layout that should be populated with Views results. Uncheck any regions that should be skipped. Skipped regions will not contain any results."),
        '#default_value' => !empty($this->options['layout_regions'][$plugin_id]) ? $this->options['layout_regions'][$plugin_id] : '',
        '#multiple' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="style_options[layout_name]"]' => [
              'value' => $plugin_id,
            ],
          ],
        ],
      ];
    }

    $form['skip_processing'] = [
      '#type' => 'select',
      '#options' => ['' => $this->t('Skip'), 'text' => $this->t('Text'), 'callback' => $this->t('Callback')],
      '#description' => $this->t('Choose processing for unchecked regions: skip (do not render), generate text, or execute a callback.'),
      '#title' => $this->t('What to do with skipped regions?'),
      '#default_value' => $this->options['skip_processing'],
    ];

    $form['skip_text'] = [
      '#type' => 'text_format',
      '#description' => $this->t('Text to render in skipped regions.'),
      '#title' => $this->t('Skipped regions text'),
      '#default_value' => $this->options['skip_text']['value'],
      '#format' => $this->options['skip_text']['format'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[skip_processing]"]' => [
            'value' => 'text',
          ],
        ],
      ],
    ];

    $form['callback'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Callback for skipped regions. Callback arguments are the layout plugin id, the current region, and the view.'),
      '#title' => $this->t('Skipped regions callback'),
      '#default_value' => $this->options['callback'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[skip_processing]"]' => [
            'value' => 'callback',
          ],
        ],
      ],
    ];
    $form['#element_validate'] = [[$this, 'validateRegions']];

  }

  /**
   * Form element validation handler for buildOptionsForm().
   */
  public function validateRegions($element, FormStateInterface $form_state) {
    $plugin_id = $element['layout_name']['#value'];
    if (empty($plugin_id)) {
      $form_state
          ->setError($element['layout_name'], $this
          ->t('A layout must be selected.'));
    }
    elseif (empty($element['layout_regions'][$plugin_id]['#value'])) {
      $form_state
          ->setError($element['layout_regions'][$plugin_id], $this
          ->t('At least one region must be selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    if (empty($this->view->rowPlugin)) {
      debug('Drupal\views\Plugin\views\style\ViewsLayout: Something is wrong, this plugin is missing.');
      return;
    }

    $options = $this->options;
    $layoutName = $options['layout_name'];

    if (empty($layoutName)) {
      debug('Drupal\views\Plugin\views\style\ViewsLayout: No layout was selected for this view.');
      return;
    }

    $layoutRegions = !empty($options['layout_regions'][$layoutName]) ? array_values(array_filter($options['layout_regions'][$layoutName])) : [];
    if (empty($layoutRegions)) {
      debug('Drupal\views\Plugin\views\style\ViewsLayout: No regions were selected for this layout.');
      return;
    }

    $definitions = $this->layoutPluginManager->getDefinitions('layout_builder');
    $definition = $definitions[$layoutName];
    $allRegions = array_keys($definition->getRegionLabels());
    $skippedRegions = array_diff($allRegions, $layoutRegions);
    $skipProcessing = $options['skip_processing'];
    $skipText = $options['skip_text'];
    $skipCallback = $options['callback'];

    // Set up layout iterator to keep track of layout position.
    $layoutIterator = new \ArrayIterator($allRegions);

    // Create a layout instance.
    $configuration = [];
    $layoutInstance = $this->layoutPluginManager->createInstance($layoutName, $configuration);

    // Iterate through the views rows and construct as many layout render arrays
    // as the rows allow.
    $newRows = [];
    $layoutRenderArray = [];
    foreach ($this->view->result as $key => $row) {

      // Process skipped regions.
      while (in_array($layoutIterator->current(), $skippedRegions)) {
        if ($skipProcessing == 'text') {
          $renderedRow = [
            '#type' => 'processed_text',
            '#text' => $skipText['value'],
            '#format' => $skipText['format'],
          ];
          $layoutRenderArray[$layoutIterator->current()] = [$renderedRow];
        }
        elseif ($skipProcessing == 'callback' && function_exists($skipCallback)) {
          $renderedRow = $skipCallback($layoutName, $layoutIterator->current(), $this->view);
          $layoutRenderArray[$layoutIterator->current()] = [$renderedRow];
        }
        $layoutIterator->next();
      }

      // Add the the current row to the build.
      // Check for end of array in case a skipped region is the last region.
      if ($layoutIterator->valid()) {
        $renderedRow = $this->view->rowPlugin->render($row);
        $layoutRenderArray[$layoutIterator->current()] = [$renderedRow];
        $layoutIterator->next();
      }

      // When we hit the end of all regions in a layout, stop and build a
      // Views row. We might not be done with all the results yet so we
      // can't break out of our foreach loop.
      if (!$layoutIterator->valid()) {
        $newRows[] = $layoutInstance->build($layoutRenderArray);
        $layoutRenderArray = [];
        $layoutIterator->rewind();
      }
    }

    // If we hit the end of the view results before we hit the end of the
    // layout, and we're not skipping the results, we need to process the
    // remaining regions in the layout.
    if ($layoutIterator->valid() && !empty($skipProcessing)) {
      do {
        if ($skipProcessing == 'text') {
          $renderedRow = [
            '#type' => 'processed_text',
            '#text' => $skipText['value'],
            '#format' => $skipText['format'],
          ];
          $layoutRenderArray[$layoutIterator->current()] = [$renderedRow];
        }
        elseif ($skipProcessing == 'callback' && function_exists($skipCallback)) {
          $renderedRow = $skipCallback($layoutName, $layoutIterator->current(), $this->view);
          $layoutRenderArray[$layoutIterator->current()] = [$renderedRow];
        }
        $layoutIterator->next();
      } while($layoutIterator->valid());
    }

    // We might have an unrendered layout left. Be sure to pick it up.
    if (!empty($layoutRenderArray)) {
      $newRows[] = $layoutInstance->build($layoutRenderArray);
    }

    // Return the render array.
    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $newRows,
    );

    return $build;
  }

}

