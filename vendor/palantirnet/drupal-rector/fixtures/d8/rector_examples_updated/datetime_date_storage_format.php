<?php

/**
 * @file
 * Demonstrates deprecated constants that might be used in procedural code.
 */
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * A simple example.
 */
function simple_example() {
  $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
}

/**
 * An example using the constant as an argument.
 */
function as_an_argument() {
  $date = new DrupalDateTime('now', new \DateTimezone('America/Los_Angeles'));
  $now = $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
}
