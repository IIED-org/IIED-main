<?php

namespace Drupal\readmore_js\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'read_more_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "read_more_field_formatter",
 *   label = @Translation("readmore.js"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class ReadMoreFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'more_link' => 'Read more',
      'more_link_classes' => '',
      'close_link' => 'Close',
      'close_link_classes' => '',
      'speed' => 75,
      'collapsed_height' => '200px',
      'height_margin' => '16px',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
   public function settingsForm(array $form, FormStateInterface $form_state) {
       $element = parent::settingsForm($form, $form_state);

       $element['more_link'] = array(
         '#title' => $this->t('Read more label'),
         '#type' => 'textfield',
         '#size' => 10,
         '#default_value' => $this->getSetting('more_link'),
       );

       $element['close_link'] = array(
         '#title' => $this->t('Close label'),
         '#type' => 'textfield',
         '#size' => 10,
         '#default_value' => $this->getSetting('close_link'),
       );
       $element['more_link_classes'] = array(
         '#title' => $this->t('Read more classes'),
         '#type' => 'textfield',
         '#size' => 10,
         '#default_value' => $this->getSetting('more_link_classes'),
       );

       $element['close_link_classes'] = array(
         '#title' => $this->t('Close classes'),
         '#type' => 'textfield',
         '#size' => 10,
         '#default_value' => $this->getSetting('close_link_classes'),
       );

       $element['speed'] = array(
         '#title' => $this->t('Speed'),
         '#type' => 'textfield',
         '#size' => 10,
         '#description' => 'in milliseconds',
         '#default_value' => $this->getSetting('speed'),
       );

       $element['collapsed_height'] = array(
         '#title' => $this->t('Collapsed Height'),
         '#type' => 'textfield',
         '#size' => 10,
         '#description' => 'eg. 200 in pixels (<em>200px</em>)',
         '#default_value' => $this->getSetting('collapsed_height'),
       );

       $element['height_margin'] = array(
         '#title' => $this->t('Height Margin'),
         '#type' => 'textfield',
         '#size' => 10,
         '#description' => 'eg. 16 in pixels (<em>16px</em>), avoids collapsing blocks that are only slightly larger than collapsedHeight',
         '#default_value' => $this->getSetting('height_margin'),
       );

       return $element;
   }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      t('Read more label: <b>@placeholder</b>', ['@placeholder' => $this->getSetting('more_link'),]),
      t('Close label: <b>@placeholder</b>', ['@placeholder' => $this->getSetting('close_link'),]),
      t('Read more classes: <b>@placeholder</b>', ['@placeholder' => $this->getSetting('more_link_classes'),]),
      t('Close classes: <b>@placeholder</b>', ['@placeholder' => $this->getSetting('close_link_classes'),]),
      t('Speed (in milliseconds): <b>@placeholder</b>', ['@placeholder' => $this->getSetting('speed'),]),
      t('Collapsed Height (in pixels): <b>@placeholder</b>', ['@placeholder' => $this->getSetting('collapsed_height'),]),
      t('Height Margin (in pixels): <b>@placeholder</b>', ['@placeholder' => $this->getSetting('height_margin'),]),
    ];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // Create selector for readmore.js
      $selector = 'field--readmore-js--' . $items->getName();
      // Return config.
      $elements['#attached']['drupalSettings']['readmore_js'][$items->getName()] = [
        'selector' => $selector,
        'more_link' => $this->getSetting('more_link'),
        'close_link' => $this->getSetting('close_link'),
        'more_link_classes' => $this->getSetting('more_link_classes'),
        'close_link_classes' => $this->getSetting('close_link_classes'),
        'speed' => $this->getSetting('speed'),
        'collapsed_height' => $this->getSetting('collapsed_height'),
        'height_margin' => $this->getSetting('height_margin'),
      ];
      // Append unique class.
      $elements['#attributes']['class'][] = $selector;
      // Append Field Value.
      $elements[$delta] = ['#markup' => $this->viewValue($item)];

    }

    $elements['#attached']['library'][] = 'readmore_js/readmore_js.library';
    $elements['#attached']['library'][] = 'readmore_js/readmore_js.selector';

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {

    return $item->value;
  }

}
