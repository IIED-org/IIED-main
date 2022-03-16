<?php

namespace Drupal\color_field\Plugin\Field\FieldFormatter;

use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\color_field\ColorHex;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the color_field swatch formatter.
 *
 * @FieldFormatter(
 *   id = "color_field_formatter_swatch",
 *   module = "color_field",
 *   label = @Translation("Color swatch"),
 *   field_types = {
 *     "color_field_type"
 *   }
 * )
 */
class ColorFieldFormatterSwatch extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'shape' => 'square',
      'width' => 50,
      'height' => 50,
      'opacity' => TRUE,
      'data_attribute' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $opacity = $this->getFieldSetting('opacity');

    $elements = [];

    $elements['shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Shape'),
      '#options' => $this->getShape(),
      '#default_value' => $this->getSetting('shape'),
      '#description' => '',
    ];
    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#description' => $this->t('Defaults to pixels (px) if a number is entered, otherwise, you can enter any unit (ie %, em, vw)'),
    ];
    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#description' => $this->t('Defaults to pixels (px) if a number is entered, otherwise, you can enter any unit (ie %, em, vh)'),
    ];

    if ($opacity) {
      $elements['opacity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display opacity'),
        '#default_value' => $this->getSetting('opacity'),
      ];
    }

    $elements['data_attribute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use HTML5 data attribute'),
      '#description' => $this->t('Render a data-color HTML 5 data attribute to allow css selectors based on color'),
      '#default_value' => $this->getSetting('data_attribute'),
    ];

    return $elements;
  }

  /**
   * This is used to get the shape.
   *
   * @param string|null $shape
   *   The specific shape name to get.
   *
   * @return array|string
   *   An array of shape ids/names or translated name of the specified shape.
   */
  protected function getShape($shape = NULL) {
    $formats = [];
    $formats['square'] = $this->t('Square');
    $formats['circle'] = $this->t('Circle');
    $formats['parallelogram'] = $this->t('Parallelogram');
    $formats['triangle'] = $this->t('Triangle');

    if ($shape) {
      return $formats[$shape];
    }
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $opacity = $this->getFieldSetting('opacity');
    $settings = $this->getSettings();

    $summary = [];

    $summary[] = $this->t('@shape', [
      '@shape' => $this->getShape($settings['shape']),
    ]);

    $summary[] = $this->t('Width: @width Height: @height', [
      '@width' => $settings['width'],
      '@height' => $settings['height'],
    ]);

    if ($opacity && $settings['opacity']) {
      $summary[] = $this->t('Display with opacity.');
    }

    if ($settings['data_attribute']) {
      $summary[] = $this->t('Use HTML5 data attribute.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();

    $elements = [];

    $elements['#attached']['library'][] = 'color_field/color-field-formatter-swatch';

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'color_field_formatter_swatch',
        '#color' => $this->viewValue($item),
        '#shape' => $settings['shape'],
        '#width' => is_numeric($settings['width']) ? "{$settings['width']}px" : $settings['width'],
        '#height' => is_numeric($settings['height']) ? "{$settings['height']}px" : $settings['height'],
        '#attributes' => new Attribute([
          'class' => [
            'color_field__swatch',
            "color_field__swatch--{$settings['shape']}",
          ],
        ]),
      ];
      if ($settings['data_attribute']) {
        $color = new ColorHex($item->color, $item->opacity);
        $elements[$delta]['#attributes']['data-color'] = $color->toString(FALSE);
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(ColorFieldType $item) {
    $opacity = $this->getFieldSetting('opacity');
    $settings = $this->getSettings();

    $color_hex = new ColorHex($item->color, $item->opacity);

    if ($opacity && $settings['opacity']) {
      $rgbtext = $color_hex->toRgb()->toString(TRUE);
    }
    else {
      $rgbtext = $color_hex->toRgb()->toString(FALSE);
    }

    return $rgbtext;
  }

}
