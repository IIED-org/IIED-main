<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\Geocoder;

use Drupal\geolocation_google_maps\Plugin\geolocation\MapProvider\GoogleMaps;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\geolocation_google_maps\GoogleGeocoderBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides the Google Geocoding API.
 *
 * @Geocoder(
 *   id = "google_geocoding_api",
 *   name = @Translation("Google Geocoding API"),
 *   description = @Translation("You do require an API key for this plugin to work."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 *   frontendCapable = true,
 *   reverseCapable = true,
 * )
 */
class GoogleGeocodingAPI extends GoogleGeocoderBase {

  /**
   * {@inheritdoc}
   */
  public function formAttachGeocoder(array &$render_array, $element_name) {
    parent::formAttachGeocoder($render_array, $element_name);

    $config = \Drupal::config('geolocation_google_maps.settings');

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'],
      [
        'library' => [
          'geolocation_google_maps/geocoder.googlegeocodingapi',
        ],
      ]
    );

    if (!empty($config->get('google_map_custom_url_parameters')['region'])) {
      $render_array['#attached']['drupalSettings']['geolocation']['geocoder'][$this->getPluginId()]['region'] = $config->get('google_map_custom_url_parameters')['region'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address) {
    $config = \Drupal::config('geolocation_google_maps.settings');
    if (empty($address)) {
      return FALSE;
    }

    if (!empty($config->get('google_maps_base_url'))) {
      $request_url = $config->get('google_maps_base_url');
    }
    elseif ($config->get('china_mode')) {
      $request_url = GoogleMaps::$googleMapsApiUrlBaseChina;
    }
    else {
      $request_url = GoogleMaps::$googleMapsApiUrlBase;
    }
    $request_url .= '/maps/api/geocode/json?address=' . urlencode($address);

    if (!empty($config->get('google_map_api_server_key'))) {
      $request_url .= '&key=' . $config->get('google_map_api_server_key');
    }
    elseif (!empty($config->get('google_map_api_key'))) {
      $request_url .= '&key=' . $config->get('google_map_api_key');
    }
    if (!empty($this->configuration['component_restrictions'])) {
      $request_url .= '&components=';
      foreach ($this->configuration['component_restrictions'] as $component_id => $component_value) {
        $request_url .= $component_id . ':' . $component_value . '|';
      }
    }
    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $request_url .= '&language=' . $config->get('google_map_custom_url_parameters')['language'];
    }
    if (
      !empty($this->configuration['boundary_restriction'])
      && !empty($this->configuration['boundary_restriction']['south'])
      && !empty($this->configuration['boundary_restriction']['west'])
      && !empty($this->configuration['boundary_restriction']['north'])
      && !empty($this->configuration['boundary_restriction']['east'])
    ) {
      $bounds = $this->configuration['boundary_restriction']['south'] . ',';
      $bounds .= $this->configuration['boundary_restriction']['west'] . '|';
      $bounds .= $this->configuration['boundary_restriction']['north'] . ',';
      $bounds .= $this->configuration['boundary_restriction']['east'];
      $request_url .= '&bounds=' . $bounds;
    }

    try {
      $result = Json::decode(\Drupal::httpClient()->request('GET', $request_url)->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      if (isset($result['error_message'])) {
        \Drupal::logger('geolocation')->error(t('Unable to geocode "@address" with error: "@error". Request URL: @url', [
          '@address' => $address,
          '@error' => $result['error_message'],
          '@url' => $request_url,
        ]));
      }
      return FALSE;
    }

    return [
      'location' => [
        'lat' => $result['results'][0]['geometry']['location']['lat'],
        'lng' => $result['results'][0]['geometry']['location']['lng'],
      ],
      // TODO: Add viewport or build it if missing.
      'boundary' => [
        'lat_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode($latitude, $longitude) {
    $config = \Drupal::config('geolocation_google_maps.settings');

    $request_url = GoogleMaps::$googleMapsApiUrlBase;
    if ($config->get('china_mode')) {
      $request_url = GoogleMaps::$googleMapsApiUrlBaseChina;
    }
    $request_url .= '/maps/api/geocode/json?latlng=' . (float) $latitude . ',' . (float) $longitude;

    if (!empty($config->get('google_map_api_server_key'))) {
      $request_url .= '&key=' . $config->get('google_map_api_server_key');
    }
    elseif (!empty($config->get('google_map_api_key'))) {
      $request_url .= '&key=' . $config->get('google_map_api_key');
    }

    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $request_url .= '&language=' . $config->get('google_map_custom_url_parameters')['language'];
    }

    try {
      $result = Json::decode(\Drupal::httpClient()->request('GET', $request_url)->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      if (isset($result['error_message'])) {
        \Drupal::logger('geolocation')->error(t('Unable to reverse geocode "@latitude, $longitude" with error: "@error". Request URL: @url', [
          '@latitude' => $latitude,
          '@$longitude' => $longitude,
          '@error' => $result['error_message'],
          '@url' => $request_url,
        ]));
      }
      return FALSE;
    }

    if (empty($result['results'][0]['address_components'])) {
      return NULL;
    }

    $addressAtomicsMapping = [
      'streetNumber' => [
        'type' => 'street_number',
      ],
      'route' => [
        'type' => 'route',
      ],
      'locality' => [
        'type' => 'locality',
      ],
      'county' => [
        'type' => 'administrative_area_level_2',
      ],
      'postalCode' => [
        'type' => 'postal_code',
      ],
      'administrativeArea' => [
        'type' => 'administrative_area_level_1',
        'short' => TRUE,
      ],
      'country' => [
        'type' => 'country',
      ],
      'countryCode' => [
        'type' => 'country',
        'short' => TRUE,
      ],
      'postalTown' => [
        'type' => 'postal_town',
      ],
      'neighborhood' => [
        'type' => 'neighborhood',
      ],
      'premise' => [
        'type' => 'premise',
      ],
      'political' => [
        'type' => 'political',
      ],
    ];

    $address_atomics = [];
    foreach ($result['results'][0]['address_components'] as $component) {
      foreach ($addressAtomicsMapping as $atomic => $google_format) {
        if ($google_format['type'] == $component['types'][0]) {
          if (!empty($google_format['short'])) {
            $address_atomics[$atomic] = $component['short_name'];
          }
          else {
            $address_atomics[$atomic] = $component['long_name'];
          }
        }
      }
    }

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'string' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
    ];
  }

}
