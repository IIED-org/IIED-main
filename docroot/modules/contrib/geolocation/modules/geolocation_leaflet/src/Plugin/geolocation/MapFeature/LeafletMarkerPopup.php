<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides marker popup.
 *
 * @MapFeature(
 *   id = "leaflet_marker_popup",
 *   name = @Translation("Marker Popup"),
 *   description = @Translation("Open Popup on Marker click."),
 *   type = "leaflet",
 * )
 */
class LeafletMarkerPopup extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    return [
      'info_auto_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents) {
    $settings = $this->getSettings($settings);

    $form['info_auto_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically show info text.'),
      '#default_value' => $settings['info_auto_display'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings, array $context = []) {
    $render_array = parent::alterMap($render_array, $feature_settings, $context);

    $feature_settings = $this->getSettings($feature_settings);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      empty($render_array['#attached']) ? [] : $render_array['#attached'],
      [
        'library' => [
          'geolocation_leaflet/mapfeature.' . $this->getPluginId(),
        ],
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                $this->getPluginId() => [
                  'enable' => TRUE,
                  'infoAutoDisplay' => $feature_settings['info_auto_display'],
                ],
              ],
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

}
