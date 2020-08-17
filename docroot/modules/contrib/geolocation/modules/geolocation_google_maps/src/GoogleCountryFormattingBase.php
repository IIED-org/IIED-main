<?php

namespace Drupal\geolocation_google_maps;

use Drupal\geolocation\GeocoderCountryFormattingInterface;
use Drupal\geolocation\GeocoderCountryFormattingBase;

/**
 * Defines an interface for geolocation google geocoder country  plugins.
 */
abstract class GoogleCountryFormattingBase extends GeocoderCountryFormattingBase implements GeocoderCountryFormattingInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $atomics) {
    $address_elements = parent::format($atomics);

    if (
      $atomics['streetNumber']
      && $atomics['route']
    ) {
      $address_elements['addressLine1'] = $atomics['streetNumber'] . ' ' . $atomics['route'];
    }
    elseif ($atomics['route']) {
      $address_elements['addressLine1'] = $atomics['route'];
    }
    elseif ($atomics['premise']) {
      $address_elements['addressLine1'] = $atomics['premise'];
    }

    if (
      $atomics['locality']
      && $atomics['postalTown']
      && $atomics['locality'] !== $atomics['postalTown']
    ) {
      $address_elements['addressLine2'] = $atomics['locality'];
    }
    elseif (
      empty($atomics['locality'])
      && $atomics['neighborhood']
    ) {
      $address_elements['addressLine2'] = $atomics['neighborhood'];
    }

    if ($atomics['locality']) {
      $address_elements['locality'] = $atomics['locality'];
    }
    elseif ($atomics['postalTown']) {
      $address_elements['locality'] = $atomics['postalTown'];
    }
    elseif (
      empty($atomics['locality'])
      && $atomics['political']
    ) {
      $address_elements['locality'] = $atomics['political'];
    }

    if ($atomics['postalCode']) {
      $address_elements['postalCode'] = $atomics['postalCode'];
    }

    if ($atomics['postalCode']) {
      $address_elements['postalCode'] = $atomics['postalCode'];
    }

    if ($atomics['administrativeArea']) {
      $address_elements['administrativeArea'] = $atomics['administrativeArea'];
    }

    if ($atomics['countryCode']) {
      $address_elements['countryCode'] = $atomics['countryCode'];
    }

    return $address_elements;
  }

}
