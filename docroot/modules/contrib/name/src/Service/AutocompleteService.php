<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\name\Utility\NameComponents;

/**
 * Helper for name field autocompletion results.
 */
class AutocompleteService implements AutocompleteInterface {

  /**
   * Name options provider.
   */
  protected NameOptionInterface $optionsProvider;

  /**
   * Entity type manager for field-data lookups.
   */
  protected ?EntityTypeManagerInterface $entityTypeManager;

  /**
   * Name field components.
   *
   * @var list<string>
   */
  protected array $allComponents = [
    'given',
    'middle',
    'family',
    'title',
    'credentials',
    'generational',
  ];

  /**
   * Constructs an AutocompleteService object.
   */
  public function __construct(NameOptionInterface $options_provider, ?EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->optionsProvider = $options_provider;
    // @phpstan-ignore-next-line
    $this->entityTypeManager = $entity_type_manager ?? \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getMatches(FieldDefinitionInterface $field, string $target, string $string): array {
    if ($string === '') {
      return [];
    }

    $settings = $this->normalizeAutocompleteSettings($field->getSettings());
    $plan = $this->buildAutocompletePlan(
      $settings,
      $this->resolveTargetComponents($target),
    );
    $input = $this->splitAutocompleteInput($string, $plan['separator']);

    if ($input === NULL || empty($plan['components'])) {
      return [];
    }

    ['base' => $base_string, 'test' => $test_string] = $input;
    $matches = [];
    $limit = 10;

    foreach (['title', 'generational'] as $source_component) {
      if ($limit <= 0 || empty($plan['source'][$source_component])) {
        continue;
      }
      $mode = $this->resolveMatchMode($settings, $source_component);
      $options = $this->optionsProvider->getOptions($field, $source_component);
      $this->collectOptionMatches(
        $options,
        $test_string,
        $mode,
        $base_string,
        $matches,
        $limit,
      );
    }

    // Per-component field-data lookup. Each component queries only its own
    // column of the field storage — a "given" request never reads "family".
    foreach ($plan['source']['data'] as $component) {
      if ($limit <= 0) {
        break;
      }
      $mode = $this->resolveMatchMode($settings, $component);
      $values = $this->findFieldValues($field, $component, $test_string, $limit, $mode);
      foreach ($values as $value) {
        $matches[$base_string . $value] = $value;
        $limit--;
        if ($limit <= 0) {
          break;
        }
      }
    }

    return $matches;
  }

  /**
   * Normalizes the required autocomplete settings for all components.
   *
   * @param array<string, mixed> $settings
   *   The field settings.
   *
   * @return array<string, mixed>
   *   The settings with normalized autocomplete source keys.
   */
  protected function normalizeAutocompleteSettings(array $settings): array {
    foreach ($this->allComponents as $component) {
      if (!isset($settings['autocomplete_source'][$component])) {
        $settings['autocomplete_source'][$component] = [];
      }
      $settings['autocomplete_source'][$component] = array_filter($settings['autocomplete_source'][$component]);
    }
    return $settings;
  }

  /**
   * Resolves an autocomplete target into an associative component map.
   *
   * @return array<string, string>
   *   A map keyed by component machine name.
   */
  protected function resolveTargetComponents(string $target): array {
    return match ($target) {
      'name' => $this->mapAssoc(['given', 'middle', 'family']),
      'name-all' => $this->mapAssoc($this->allComponents),
      'title', 'given', 'middle', 'family', 'credentials', 'generational' => [$target => $target],
      default => $this->resolveCompositeTargetComponents($target),
    };
  }

  /**
   * Resolves a hyphen-delimited target into valid core components.
   *
   * @return array<string, string>
   *   A map keyed by component machine name.
   */
  protected function resolveCompositeTargetComponents(string $target): array {
    $components = [];
    foreach (explode('-', $target) as $component) {
      if (array_key_exists($component, NameComponents::coreKeys())) {
        $components[$component] = $component;
      }
    }
    return $components;
  }

  /**
   * Builds the executable autocomplete plan for component and source lookups.
   *
   * @param array<string, mixed> $settings
   *   Normalized field settings.
   * @param array<string, string> $components
   *   Requested components keyed by component name.
   *
   * @return array{
   *   components: array<string, string>,
   *   source: array{
   *     title: array<int, string>,
   *     generational: array<int, string>,
   *     data: array<int, string>
   *   },
   *   separator: string
   *   }
   *   The actionable plan for source resolution and input splitting.
   */
  protected function buildAutocompletePlan(array $settings, array $components): array {
    $plan = [
      'components' => $components,
      'source' => [
        'title' => [],
        'generational' => [],
        'data' => [],
      ],
      'separator' => '',
    ];

    foreach ($plan['components'] as $component) {
      if (empty($settings['autocomplete_source'][$component])) {
        unset($plan['components'][$component]);
        continue;
      }

      $plan['separator'] = $this->appendSeparatorCharacters(
        $plan['separator'],
        (string) ($settings['autocomplete_separator'][$component] ?? ''),
      );
      $found_source = FALSE;

      foreach ((array) $settings['autocomplete_source'][$component] as $source) {
        if (($source === 'title' || $source === 'generational') && $component !== $source) {
          continue;
        }
        if (!array_key_exists($source, $plan['source'])) {
          continue;
        }
        $found_source = TRUE;
        $plan['source'][$source][] = $component;
      }

      if (!$found_source) {
        unset($plan['components'][$component]);
      }
    }

    return $plan;
  }

  /**
   * Adds unique separator characters from one component into the set.
   */
  protected function appendSeparatorCharacters(string $separator, string $component_separator): string {
    if ($component_separator === '') {
      $component_separator = ' ';
    }

    for ($i = 0; $i < strlen($component_separator); $i++) {
      if (strpos($separator, $component_separator[$i]) === FALSE) {
        $separator .= $component_separator[$i];
      }
    }

    return $separator;
  }

  /**
   * Wraps preg_split to allow substitution in tests.
   *
   * @return list<string>|false
   *   The split result, or FALSE on PCRE failure.
   */
  protected function pregSplitPieces(string $pattern, string $subject): array|false {
    return \preg_split($pattern, $subject);
  }

  /**
   * Splits autocomplete input into base prefix and searchable token.
   *
   * @return array{base: string, test: string}|null
   *   The parsed input parts, or NULL when no usable split can be performed.
   */
  protected function splitAutocompleteInput(string $string, string $separator): ?array {
    if ($separator === '') {
      return NULL;
    }

    $pieces = $this->pregSplitPieces(
      '/[' . \preg_quote($separator, '/') . ']+/',
      $string,
    );
    if (empty($pieces)) {
      return NULL;
    }

    $test_string = mb_strtolower((string) array_pop($pieces));
    if ($test_string === '') {
      return NULL;
    }

    return [
      'base' => mb_substr($string, 0, mb_strlen($string) - mb_strlen($test_string)),
      'test' => $test_string,
    ];
  }

  /**
   * Adds matching option values while honoring the shared result limit.
   *
   * @param array<string, mixed> $options
   *   The option list keyed by stored value.
   * @param string $test_string
   *   The lowercase token to match.
   * @param string $mode
   *   The match mode.
   * @param string $base_string
   *   The prefix to prepend to matched option keys.
   * @param array<string, string> $matches
   *   The current match list.
   * @param int $limit
   *   The remaining match limit.
   */
  protected function collectOptionMatches(array $options, string $test_string, string $mode, string $base_string, array &$matches, int &$limit): void {
    foreach ($options as $key => $option) {
      $value_key = (string) $key;
      if (!$this->stringMatches($value_key, $test_string, $mode)
        && !$this->stringMatches((string) $option, $test_string, $mode)) {
        continue;
      }
      $matches[$base_string . $value_key] = $value_key;
      $limit--;
      if ($limit <= 0) {
        return;
      }
    }
  }

  /**
   * Resolves the effective autocomplete match mode for a single component.
   *
   * @param array<string, mixed> $settings
   *   The field settings array from FieldDefinitionInterface::getSettings().
   * @param string $component
   *   The component machine name (for example, "given").
   *
   * @return string
   *   Either "starts_with" or "contains". Falls back to "starts_with" for any
   *   legacy configuration that predates these settings.
   */
  protected function resolveMatchMode(array $settings, string $component): string {
    $override = $settings['autocomplete_match_overrides'][$component] ?? '';
    if ($override === 'contains' || $override === 'starts_with') {
      return $override;
    }
    return ($settings['autocomplete_match'] ?? 'starts_with') === 'contains'
      ? 'contains'
      : 'starts_with';
  }

  /**
   * Applies the resolved match mode to an in-memory string comparison.
   */
  protected function stringMatches(string $haystack, string $needle, string $mode): bool {
    if ($needle === '') {
      return FALSE;
    }
    $position = mb_strpos(mb_strtolower($haystack), $needle);
    if ($position === FALSE) {
      return FALSE;
    }
    return $mode === 'contains' ? TRUE : $position === 0;
  }

  /**
   * Collects matching field item values from loaded entities.
   *
   * @param array<int|string, FieldableEntityInterface> $entities
   *   The entities to inspect.
   * @param string $field_name
   *   The field machine name.
   * @param string $component
   *   The name field component machine name.
   * @param string $needle
   *   The lowercase search term.
   * @param int $limit
   *   The maximum number of unique matches to return.
   * @param string $mode
   *   The match mode.
   *
   * @return array<string, string>
   *   Matching values keyed by value.
   */
  protected function collectEntityFieldMatches(
    array $entities,
    string $field_name,
    string $component,
    string $needle,
    int $limit,
    string $mode,
  ): array {
    $matches = [];
    foreach ($entities as $entity) {
      if (!$entity->hasField($field_name)) {
        continue;
      }
      foreach ($entity->get($field_name) as $item) {
        $value = $item->{$component} ?? NULL;
        if (!is_string($value) || $value === '') {
          continue;
        }
        if (!$this->stringMatches($value, $needle, $mode)) {
          continue;
        }
        $matches[$value] = $value;
        if (count($matches) >= $limit) {
          return $matches;
        }
      }
    }
    return $matches;
  }

  /**
   * {@inheritdoc}
   */
  public function mapAssoc(array $values): array {
    return array_combine($values, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
    $invalid_args = $limit <= 0
      || $term === ''
      || !in_array($component, $this->allComponents, TRUE);
    if ($invalid_args) {
      return [];
    }
    if ($this->entityTypeManager === NULL) {
      return [];
    }

    $entity_type_id = $field->getTargetEntityTypeId();
    $field_name     = $field->getName();
    $invalid_field  = $entity_type_id === NULL
      || $entity_type_id === ''
      || $field_name === '';
    if ($invalid_field) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
    }
    catch (\Exception $e) {
      return [];
    }

    $property_path = $field_name . '.' . $component;
    $range         = max($limit * 4, $limit);
    $operator      = $mode === 'contains' ? 'CONTAINS' : 'STARTS_WITH';

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition($property_path, $term, $operator)
      ->sort($property_path)
      ->range(0, $range)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->collectEntityFieldMatches(
      $storage->loadMultiple($ids),
      $field_name,
      $component,
      mb_strtolower($term),
      $limit,
      $mode,
    );
  }

}
