<?php

namespace Drupal\layout_paragraphs\Element;

use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a layout selection element.
 *
 * Extends the radios form element and adds thumbnail previews for layouts.
 *
 * Usage example:
 * @code
 * $form['layout'] = [
 *   '#type' => 'layout_select',
 *   '#title' => t('Choose a layout'),
 *   '#options' => ['layout1', 'layout2'],
 *   '#default_value' => 'layout1',
 * ];
 * @endcode
 *
 * @RenderElement("layout_select")
 */
class LayoutSelect extends Radios {

  /**
   * The layout plugin manager service.
   *
   * @var Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info += [
      '#width' => 40,
      '#height' => 60,
      '#stroke_width' => 1,
      '#padding' => 0,
    ];
    $info['#process'][] = [__CLASS__, 'processLayoutSelect'];
    return $info;
  }

  /**
   * Add layout thumbnail previews.
   */
  public static function processLayoutSelect(
    &$element,
    FormStateInterface $form_state,
    &$complete_form) {
    foreach (Element::children($element) as $key) {
      $layout_name = $key;
      $definition = \Drupal::service('plugin.manager.core.layout')->getDefinition($layout_name);
      $icon = $definition->getIcon($element['#width'], $element['#height'], $element['#stroke_width'], $element['#padding']);
      $rendered_icon = \Drupal::service('renderer')->render($icon);
      $element[$key]['#icon'] = $icon;
      $title = new FormattableMarkup('<span class="layout-select__item-icon">@icon</span><span class="layout-select__item-title">@title</span>', [
        '@title' => $element[$key]['#title'],
        '@icon' => $rendered_icon,
      ]);
      $element[$key]['#title'] = $title;
      $element[$key]['#wrapper_attributes']['class'][] = 'layout-select__item';
      $element[$key]['#attributes']['class'][] = 'visually-hidden';
    }
    $element['#attached']['library'][] = 'layout_paragraphs/layout_select';
    $element['#wrapper_attributes'] = ['class' => ['layout-select']];
    return $element;
  }

}
