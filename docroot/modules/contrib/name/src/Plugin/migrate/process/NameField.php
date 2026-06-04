<?php

declare(strict_types=1);

namespace Drupal\name\Plugin\migrate\process;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parses text values into name values.
 *
 * Available configuration keys:
 * - entity_type: The entity type of the destination field.
 * - bundle: The bundle of the destination field.
 * - field_name: The machine name of the destination name field.
 * - title: (optional) Array of title values to recognize, overrides field
 *   settings.
 * - generational: (optional) Array of generational values to recognize,
 *   overrides field settings.
 * - credentials: (optional) Array of credential values to recognize by word
 *   match. Credentials are always detected by delimiter (commas, parentheses,
 *   slashes, dashes) regardless of this setting.
 *
 * If entity_type, bundle, and field_name are provided, the plugin retrieves
 * title_options and generational_options from the field configuration. Direct
 * configuration of title, generational, or credentials arrays takes precedence
 * over field settings.
 *
 * @MigrateProcessPlugin(
 *   id = "name_field"
 * )
 */
class NameField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a NameField process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity_field.manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityFieldManagerInterface $entity_field_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
    );
  }

  /**
   * Gets the recognized values for a name component.
   *
   * @param string $component
   *   The component name (title, generational, or credentials).
   *
   * @return array
   *   The list of recognized values for this component.
   */
  protected function getComponentOptions(string $component): array {
    $from_plugin_configuration = $this->configuredComponentList($component);
    if ($from_plugin_configuration !== NULL) {
      return $from_plugin_configuration;
    }
    return $this->fieldSettingsComponentList($component);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): array {
    $normalized = $this->normalizeWhitespace((string) $value);
    $family_only = $this->familyOnlyComponentsIfSingleWord($normalized);
    if ($family_only !== NULL) {
      return $family_only;
    }

    $credential_lexicon = $this->getComponentOptions('credentials');
    $generational_lexicon = $this->getComponentOptions('generational');
    [$credential_phase_components, $remainder_after_credentials] = $this->extractCredentialOrCommaSuffix(
      $normalized,
      $credential_lexicon,
      $generational_lexicon,
    );

    return $this->mergeStructuredNameOntoParsed(
      $credential_phase_components,
      $remainder_after_credentials,
      $this->getComponentOptions('title'),
      $generational_lexicon,
    );
  }

  /**
   * Trims outer whitespace and collapses repeated internal spaces.
   */
  protected function normalizeWhitespace(string $value): string {
    $trimmed = trim($value);
    $single_spaced = preg_replace('/  +/', ' ', $trimmed);
    return is_string($single_spaced) ? $single_spaced : $trimmed;
  }

  /**
   * Maps a single-token value to a family-only component list.
   */
  protected function familyOnlyComponentsIfSingleWord(string $normalized): ?array {
    $tokens = explode(' ', $normalized);
    if (count($tokens) === 1) {
      return ['family' => $normalized];
    }
    return NULL;
  }

  /**
   * Applies credential and comma-suffix rules in fixed priority order.
   *
   * @return array
   *   A two-element list: partial component values, then the remainder string.
   */
  protected function extractCredentialOrCommaSuffix(
    string $value,
    array $credential_lexicon,
    array $generational_lexicon,
  ): array {
    $split = $this->tryParentheticalCredentials($value)
      ?? $this->tryCommaThenSlashTrailingCredentials($value)
      ?? $this->tryLeadingCredentialWord($value, $credential_lexicon)
      ?? $this->trySlashTrailingCredentials($value)
      ?? $this->trySpacedDashTrailingCredentials($value)
      ?? $this->tryCommaGenerationalOrCredentialsSuffix($value, $generational_lexicon)
      ?? $this->tryTrailingCredentialWord($value, $credential_lexicon);

    if ($split === NULL) {
      return [[], $value];
    }

    return [$split['parsed'], $split['remainder']];
  }

  /**
   * Tries to read credentials inside balanced parentheses.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function tryParentheticalCredentials(string $value): ?array {
    $open_parenthesis = strpos($value, '(');
    $close_parenthesis = strpos($value, ')');
    if ($open_parenthesis === FALSE || $close_parenthesis === FALSE || $close_parenthesis <= $open_parenthesis) {
      return NULL;
    }
    return [
      'parsed' => [
        'credentials' => trim(substr($value, $open_parenthesis + 1, $close_parenthesis - $open_parenthesis - 1)),
      ],
      'remainder' => substr($value, 0, $open_parenthesis),
    ];
  }

  /**
   * Tries comma-suffix credentials that include a slash after the comma.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function tryCommaThenSlashTrailingCredentials(string $value): ?array {
    $comma_position = strpos($value, ',');
    $slash_position = strpos($value, '/');
    if ($comma_position === FALSE || $slash_position === FALSE || $slash_position <= $comma_position) {
      return NULL;
    }
    return [
      'parsed' => [
        'credentials' => trim(substr($value, $comma_position + 1)),
      ],
      'remainder' => substr($value, 0, $comma_position),
    ];
  }

  /**
   * Tries a leading word that matches the configured credential list.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function tryLeadingCredentialWord(string $value, array $credential_lexicon): ?array {
    if ($credential_lexicon === []) {
      return NULL;
    }
    $tokens = explode(' ', $value);
    $first_token = trim($tokens[0] ?? '');
    if (!in_array($first_token, $credential_lexicon, FALSE)) {
      return NULL;
    }
    $first_space = strpos($value, ' ');
    if ($first_space === FALSE) {
      return NULL;
    }
    return [
      'parsed' => ['credentials' => trim($tokens[0])],
      'remainder' => substr($value, $first_space),
    ];
  }

  /**
   * Tries slash-delimited trailing credentials.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function trySlashTrailingCredentials(string $value): ?array {
    $slash_position = strpos($value, '/');
    if ($slash_position === FALSE) {
      return NULL;
    }
    return [
      'parsed' => [
        'credentials' => trim(substr($value, $slash_position + 1)),
      ],
      'remainder' => substr($value, 0, $slash_position),
    ];
  }

  /**
   * Tries dash-delimited trailing credentials using spaced dash markers.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function trySpacedDashTrailingCredentials(string $value): ?array {
    $dash_after_space = strpos($value, ' -');
    $dash_before_space = strpos($value, '- ');
    $dash_position = ($dash_after_space !== FALSE) ? $dash_after_space : $dash_before_space;
    if ($dash_position === FALSE) {
      return NULL;
    }
    return [
      'parsed' => [
        'credentials' => trim(substr($value, $dash_position + 2)),
      ],
      'remainder' => substr($value, 0, $dash_position),
    ];
  }

  /**
   * Tries a comma suffix as generational text or as credentials.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function tryCommaGenerationalOrCredentialsSuffix(string $value, array $generational_lexicon): ?array {
    $comma_position = strpos($value, ',');
    if ($comma_position === FALSE) {
      return NULL;
    }
    $after_comma = trim(substr($value, $comma_position + 1));
    if (in_array($after_comma, $generational_lexicon, FALSE)) {
      $parsed = ['generational' => $after_comma];
    }
    else {
      $parsed = ['credentials' => $after_comma];
    }
    return [
      'parsed' => $parsed,
      'remainder' => substr($value, 0, $comma_position),
    ];
  }

  /**
   * Tries a trailing word that matches the configured credential list.
   *
   * @return array|null
   *   Keys `parsed` and `remainder`, or NULL when the pattern does not match.
   */
  protected function tryTrailingCredentialWord(string $value, array $credential_lexicon): ?array {
    if ($credential_lexicon === []) {
      return NULL;
    }
    $tokens = explode(' ', $value);
    $last_token = trim((string) end($tokens));
    if (!in_array($last_token, $credential_lexicon, FALSE)) {
      return NULL;
    }
    $credential_token = array_pop($tokens);
    $last_space = strrpos($value, ' ');
    if ($last_space === FALSE) {
      return NULL;
    }
    return [
      'parsed' => ['credentials' => $credential_token],
      'remainder' => substr($value, 0, $last_space),
    ];
  }

  /**
   * Merges structured name fields into the parsed component list.
   */
  protected function mergeStructuredNameOntoParsed(
    array $parsed,
    string $remainder_after_credentials,
    array $title_options,
    array $generational_options,
  ): array {
    $words = explode(' ', trim($remainder_after_credentials, " \t,/()-"));
    if (in_array(trim($words[0] ?? ''), $title_options, FALSE)) {
      $parsed['title'] = trim(array_shift($words));
    }
    if (in_array(trim((string) end($words)), $generational_options, FALSE)) {
      $parsed['generational'] = trim(array_pop($words));
    }
    $parsed['given'] = trim(array_shift($words));
    if (count($words) > 1) {
      $parsed['middle'] = trim(array_shift($words));
    }
    $parsed['family'] = trim(implode(' ', $words));
    return $parsed;
  }

  /**
   * Returns a component list supplied directly on the plugin configuration.
   *
   * @return array|null
   *   The configured list, or NULL when the plugin did not supply values.
   */
  protected function configuredComponentList(string $component): ?array {
    if (empty($this->configuration[$component])) {
      return NULL;
    }
    return (array) $this->configuration[$component];
  }

  /**
   * Loads a component list from the destination field definition when present.
   */
  protected function fieldSettingsComponentList(string $component): array {
    $entity_type_id = $this->configuration['entity_type'] ?? NULL;
    $bundle_id = $this->configuration['bundle'] ?? NULL;
    $field_name = $this->configuration['field_name'] ?? NULL;
    if (!$entity_type_id || !$bundle_id || !$field_name) {
      return [];
    }
    $field_definitions = $this->entity_field_manager->getFieldDefinitions($entity_type_id, $bundle_id);
    if (!isset($field_definitions[$field_name])) {
      return [];
    }
    $settings = $field_definitions[$field_name]->getSettings();
    $options_key = $component . '_options';
    if (empty($settings[$options_key])) {
      return [];
    }
    $as_strings = array_map(static fn($item): string => (string) $item, $settings[$options_key]);
    return $this->optionListWithoutEmptyPlaceholders($as_strings);
  }

  /**
   * Removes placeholder markers such as empty select options from a list.
   *
   * @param array $options
   *   Raw option strings, typically from field storage settings.
   *
   * @return array
   *   The filtered list with placeholder entries removed.
   */
  protected function optionListWithoutEmptyPlaceholders(array $options): array {
    return array_values(array_filter($options, static fn($option): bool => !str_starts_with(trim((string) $option), '--')));
  }

}
