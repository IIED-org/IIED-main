<?php

namespace Drupal\address\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Look up two letter country code given a country name.
 *
 * The matching mechanism ignores:
 * - casing differences.
 * - special characters or punctuation, via transliteration.
 * - whitespace from the beginning and end.
 *
 * Available configuration keys:
 * - source: The input value - either a scalar or an array.
 * - source_language: The language the input value is in.
 * - default_value: (optional) The value to return if the source is not found in
 *   the map array.
 *
 * @MigrateProcessPlugin(
 *   id = "lookup_country"
 * )
 */
class LookupCountry extends ProcessPluginBase {

  /**
   * Array with country names as keys and country codes as values.
   *
   * @var array
   */
  protected $flippedCountryList;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      $value = [$value];
    }
    $return = [];
    if (!isset($this->flippedCountryList)) {
      /** @var \Drupal\address\Repository\CountryRepository $country_repository */
      $country_repository = \Drupal::service('address.country_repository');
      $locale = NULL;
      if (array_key_exists('source_language', $this->configuration)) {
        $locale = $this->configuration['source_language'];
      }
      $list = $country_repository->getList($locale);
      foreach ($list as $county_code => $country_name) {
        $list[$county_code] = \Drupal::transliteration()->transliterate($country_name);
      }
      $flipped = array_flip($list);
      $this->flippedCountryList = array_change_key_case($flipped);
    }
    foreach ($value as $item) {
      $item = strtolower(\Drupal::transliteration()->transliterate(trim($item)));
      if (isset($this->flippedCountryList[$item])) {
        $return[] = $this->flippedCountryList[$item];
        continue;
      }
    }
    if (count($return) == 1) {
      return reset($return);
    }
    if (array_key_exists('default_value', $this->configuration)) {
      return $this->configuration['default_value'];
    }
    return NULL;
  }

}
