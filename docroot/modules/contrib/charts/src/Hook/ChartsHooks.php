<?php

declare(strict_types=1);

namespace Drupal\charts\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service class for implementing module hooks for Charts.
 */
class ChartsHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ChartsHooks object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   The extension path resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected readonly RequestStack $requestStack,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ExtensionPathResolver $extensionPathResolver,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_theme().
   *
   * @return array
   *   The theme array.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'charts_chart' => [
        'render element' => 'element',
      ],
    ];
  }

  /**
   * Implements hook_views_data().
   *
   * @return array
   *   The views data.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data['charts_fields']['table']['group'] = $this->t('Charts');
    $data['charts_fields']['table']['join'] = [
      '#global' => [],
    ];
    $data['charts_fields']['field_charts_fields_scatter'] = [
      'title' => $this->t('Scatter Field'),
      'help' => $this->t('Use this field for your data field in a scatter plot.'),
      'field' => ['id' => 'field_charts_fields_scatter'],
    ];
    $data['charts_fields']['field_charts_fields_bubble'] = [
      'title' => $this->t('Bubble Field'),
      'help' => $this->t('Use this field for your data field in a bubble chart.'),
      'field' => ['id' => 'field_charts_fields_bubble'],
    ];
    $data['charts_fields']['field_charts_numeric_array'] = [
      'title' => $this->t('Numeric Array'),
      'help' => $this->t('Use this field for your data field in a chart of 1-10 array items.'),
      'field' => ['id' => 'field_charts_numeric_array'],
    ];
    $data['charts_fields']['field_exposed_chart_type'] = [
      'title' => $this->t('Exposed Chart Type'),
      'help' => $this->t('Use this field for exposing chart type.'),
      'field' => ['id' => 'field_exposed_chart_type'],
    ];

    return $data;
  }

  /**
   * Implements hook_views_pre_view().
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The views executable.
   * @param string $display_id
   *   The views display ID.
   * @param array $args
   *   The views arguments.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, string $display_id, array &$args): void {
    if (array_key_exists('fields', $view->display_handler->options)) {
      $fields = $view->display_handler->getOption('fields');
      $hasViewsFieldsOnOffHandler = FALSE;
      foreach ($fields as $field) {
        if (($field['plugin_id'] ?? '') === 'field_exposed_chart_type') {
          $hasViewsFieldsOnOffHandler = TRUE;
          break;
        }
      }
      if ($hasViewsFieldsOnOffHandler) {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
          $params = array_merge($request->query->all(), $request->request->all());
          foreach ($params as $key => $value) {
            if (str_starts_with($key, 'ct')) {
              $view->storage->set('exposed_chart_type', $value);
            }
          }
          $view->element['#cache']['contexts'][] = 'url';
        }
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   *
   * @param array $libraries
   *   The library definitions.
   * @param string $extension
   *   The enabled modules.
   *
   * @phpstan-ignore-next-line
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if (!str_starts_with($extension, 'charts_')) {
      return;
    }

    $config = $this->configFactory->get('charts.settings');
    if (!$config->get('advanced.requirements.cdn')) {
      return;
    }

    foreach ($libraries as &$library) {
      if (isset($library['cdn']) && is_array($library['cdn'])) {
        $this->alterRecursive($library, $library['cdn']);
      }
    }
  }

  /**
   * Recursive helper for library alter.
   *
   * @param array $library
   *   The library.
   * @param array $cdn
   *   The CDN info.
   */
  private function alterRecursive(array &$library, array $cdn): void {
    foreach ($library as $key => &$value) {
      if (!is_string($key) || !is_array($value) || $key === 'cdn') {
        continue;
      }

      foreach ($cdn as $source => $destination) {
        if ($this->checkSourceExists($source)) {
          continue;
        }
        if (str_starts_with($key, $source)) {
          $uri = str_replace($source, $destination, $key);
          $library[$uri] = $value;
          $library[$uri]['type'] = 'external';
          unset($library[$key]);
          break;
        }
      }

      $this->alterRecursive($value, $cdn);
    }
  }

  /**
   * Checks whether a library source directory or file exists locally.
   *
   * @param string $source
   *   The source directory or file.
   *
   * @return bool
   *   True when the file exist, FALSE otherwise.
   */
  private function checkSourceExists(string $source): bool {
    $search_paths = [DRUPAL_ROOT];

    // Find the profile via the module list.
    $install_profile = NULL;
    foreach ($this->moduleHandler->getModuleList() as $module) {
      if ($module->getType() === 'profile') {
        $install_profile = $module->getName();
        break;
      }
    }

    if ($install_profile) {
      $search_paths[] = $this->extensionPathResolver->getPath('profile', $install_profile);
    }

    foreach ($search_paths as $path) {
      if (file_exists($path . '/' . ltrim($source, '/'))) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
