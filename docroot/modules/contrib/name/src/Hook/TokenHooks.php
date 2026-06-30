<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\field\Entity\FieldConfig;
use Drupal\name\Plugin\Field\FieldType\NameItem;
use Drupal\name\Service\NameFormatterInterface;

/**
 * Formatted name field token metadata and replacements for core Token hooks.
 *
 * @internal
 */
class TokenHooks {

  public function __construct(
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly NameFormatterInterface $nameFormatter,
  ) {}

  /**
   * Implements hook_token_info_alter().
   *
   * Alters token info to register formatted name chains and browser entries.
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('token_info_alter')] // @phpstan-ignore attribute.notFound
  public function alterTokenInfo(array &$info): void {
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('name');
    $formats = $this->entityTypeManager
      ->getStorage('name_format')
      ->loadMultiple();
    foreach ($field_map as $entity_type_id => $fields) {
      if (!isset($info['tokens'][$entity_type_id])) {
        continue;
      }
      foreach ($fields as $field_name => $field_data) {
        $label = $field_name;
        $bundle_keys = array_keys($field_data['bundles']);
        if ($bundle_keys) {
          $field_config = FieldConfig::loadByName(
            $entity_type_id,
            reset($bundle_keys),
            $field_name,
          );
          if ($field_config) {
            $label = $field_config->getLabel();
          }
        }
        $pointer = $this->formattedPointer($field_name);
        $chain_type = $this->formattedChainType($entity_type_id, $field_name);
        $demo_components = [
          'title' => 'Mr.',
          'given' => 'John',
          'middle' => 'Q.',
          'family' => 'Public',
          'generational' => 'Sr.',
          'credentials' => 'PhD',
        ];
        $info['types'][$chain_type] = [
          'name' => t('Formatted name'),
          'description' => t(
            'Full name for @field using a saved name format. First value only; other deltas: [entity:field:delta:formatted:format].',
            ['@field' => $label],
          ),
          'needs-data' => $entity_type_id,
        ];
        foreach ($formats as $format) {
          $demo_formatted = (string) $this->nameFormatter->format(
            $demo_components,
            $format->id(),
          );
          $info['tokens'][$chain_type][$format->id()] = [
            'name' => $format->label(),
            'description' => t(
              '@field using the @format name format (@demo).',
              [
                '@field' => $label,
                '@format' => $format->label(),
                '@demo' => $demo_formatted,
              ],
            ),
          ];
        }

        // Nest "Formatted" under the same Token subtree as given, family, etc.,
        // when the Token module chained this field to a sub-type.
        $formatted_parent = [
          'name' => t('Formatted'),
          'description' => t(
            'Full name text using a name format (first value only). For other deltas use [entity:field:delta:formatted:format].',
          ),
          'type' => $chain_type,
        ];
        $use_nested_formatted = FALSE;
        $field_sub_type = $this->fieldSubType($info, $entity_type_id, $field_name);
        if ($field_sub_type !== NULL) {
          $info['tokens'][$field_sub_type]['formatted'] = $formatted_parent;
          $use_nested_formatted = TRUE;
        }
        if (!$use_nested_formatted) {
          $info['tokens'][$entity_type_id][$pointer] = [
            'name' => t('Formatted: @field', ['@field' => $label]),
            'description' => t(
              'Structured name for @field using a name format (expand to pick a format).',
              ['@field' => $label],
            ),
            'type' => $chain_type,
          ];
        }
      }
    }
  }

  /**
   * Implements hook_tokens().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('tokens')] // @phpstan-ignore attribute.notFound
  public function getChainReplacements(
    string $type,
    array $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata,
  ): array {
    $replacements = [];
    if (!str_starts_with($type, 'name_formatted|')) {
      return $replacements;
    }
    $pieces = explode('|', $type, 3);
    if (count($pieces) !== 3) {
      return $replacements;
    }
    [, $entity_type_id, $field_name] = $pieces;
    if (!isset($data[$entity_type_id]) || !$data[$entity_type_id] instanceof ContentEntityInterface) {
      return $replacements;
    }
    $entity = $data[$entity_type_id];
    foreach ($tokens as $format_id => $original) {
      $replacements[$original] = $this->formattedValue(
        $entity,
        $field_name,
        0,
        $format_id,
        $options,
        $bubbleable_metadata,
      );
    }
    return $replacements;
  }

  /**
   * Implements hook_tokens_alter().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('tokens_alter')] // @phpstan-ignore attribute.notFound
  public function alterReplacements(
    array &$replacements,
    array $context,
    BubbleableMetadata $bubbleable_metadata,
  ): void {
    $type = $context['type'];
    $tokens = $context['tokens'];
    $data = $context['data'];
    $options = $context['options'];
    if (!$tokens) {
      return;
    }
    if (!isset($data[$type]) || !$data[$type] instanceof ContentEntityInterface) {
      return;
    }
    $entity = $data[$type];

    foreach ($tokens as $name => $original) {
      $parsed = $this->parseFormattedName($name);
      if ($parsed === NULL) {
        continue;
      }
      [$field_name, $delta, $format_id] = $parsed;
      $replacements[$original] = $this->formattedValue(
        $entity,
        $field_name,
        $delta,
        $format_id,
        $options,
        $bubbleable_metadata,
      );
    }
  }

  /**
   * Resolves Token field chain sub-type for a name field, if registered.
   *
   * @param array $info
   *   Full token info from hook_token_info_alter().
   * @param string $entity_type_id
   *   Entity type id (e.g. node).
   * @param string $field_name
   *   Field machine name.
   *
   * @return string|null
   *   Token type id for the field item subtree, or NULL.
   */
  private function fieldSubType(array $info, string $entity_type_id, string $field_name): ?string {
    if (!isset($info['tokens'][$entity_type_id]) || !is_array($info['tokens'][$entity_type_id])) {
      return NULL;
    }
    $tokens = $info['tokens'][$entity_type_id];
    $candidates = [$field_name];
    foreach (array_keys($tokens) as $token_key) {
      if ($token_key === $field_name) {
        continue;
      }
      if (preg_match('/^' . preg_quote($field_name, '/') . ':\d+$/', $token_key)) {
        $candidates[] = $token_key;
      }
    }
    foreach ($candidates as $candidate) {
      if (!empty($tokens[$candidate]['type'])) {
        $sub_type = $tokens[$candidate]['type'];
        if (isset($info['tokens'][$sub_type]) && is_array($info['tokens'][$sub_type])) {
          return $sub_type;
        }
      }
    }
    return NULL;
  }

  /**
   * Parses a field token name for explicit name format output.
   *
   * @param string $name
   *   Token name without the entity type prefix, e.g.
   *   field_realname:formatted:full, formatted_field_realname:full,
   *   or field_realname:1:formatted:full.
   *
   * @return array{0: string, 1: int, 2: string}|null
   *   Field machine name, delta, format id; NULL if not a formatted token.
   */
  private function parseFormattedName(string $name): ?array {
    $legacy = $this->parseFormattedLegacyColon($name);
    if ($legacy !== NULL) {
      return $legacy;
    }
    return $this->parseFormattedPointerToken($name);
  }

  /**
   * Parses legacy colon syntax for formatted name field tokens.
   *
   * Matches {field}:formatted:{id} and {field}:{delta}:formatted:{id}.
   *
   * @return array{0: string, 1: int, 2: string}|null
   *   Field machine name, delta, format id; NULL if the name does not match.
   */
  private function parseFormattedLegacyColon(string $name): ?array {
    $parts = explode(':', $name);
    $formatted_index = array_search('formatted', $parts, TRUE);
    if ($formatted_index === FALSE || !isset($parts[$formatted_index + 1])) {
      return NULL;
    }
    $format_id = $parts[$formatted_index + 1];
    if ($formatted_index === 1) {
      return [$parts[0], 0, $format_id];
    }
    if ($formatted_index === 2 && is_numeric($parts[1])) {
      return [$parts[0], (int) $parts[1], $format_id];
    }
    return NULL;
  }

  /**
   * Parses chained formatted tokens: formatted_{field}:{format_id}.
   *
   * Used for token-browser grouping
   * (e.g. [node:formatted_field_realname:given]).
   *
   * @return array{0: string, 1: int, 2: string}|null
   *   Field machine name, delta, format id; NULL if the name does not match.
   */
  private function parseFormattedPointerToken(string $name): ?array {
    $parts = explode(':', $name, 2);
    if (count($parts) !== 2 || !str_starts_with($parts[0], 'formatted_')) {
      return NULL;
    }
    $field_name = substr($parts[0], strlen('formatted_'));
    if ($field_name === '') {
      return NULL;
    }
    return [$field_name, 0, $parts[1]];
  }

  /**
   * Machine token pointer for chained "formatted" subtree.
   */
  private function formattedPointer(string $field_name): string {
    return 'formatted_' . $field_name;
  }

  /**
   * Token type id for chained formatted replacements (hook_tokens).
   */
  private function formattedChainType(string $entity_type_id, string $field_name): string {
    return 'name_formatted|' . $entity_type_id . '|' . $field_name;
  }

  /**
   * Computes replacement text for a formatted name field token.
   */
  private function formattedValue(
    ContentEntityInterface $entity,
    string $field_name,
    int $delta,
    string $format_id,
    array $options,
    BubbleableMetadata $bubbleable_metadata,
  ): string {
    if (!$entity->hasField($field_name)) {
      return '';
    }
    $items_entity = $entity->get($field_name);
    if ($items_entity->getFieldDefinition()->getType() !== 'name') {
      return '';
    }
    $bubbleable_metadata->addCacheableDependency($entity);
    if ($items_entity instanceof CacheableDependencyInterface) {
      $bubbleable_metadata->addCacheableDependency($items_entity);
    }
    $format_storage = $this->entityTypeManager->getStorage('name_format');
    $used_format = $format_storage->load($format_id)
      ?: $format_storage->load('default');
    if ($used_format) {
      $bubbleable_metadata->addCacheableDependency($used_format);
    }
    if ($items_entity->isEmpty() || $delta < 0 || $delta >= $items_entity->count()) {
      return '';
    }
    $item = $items_entity->get($delta);
    if (!$item instanceof NameItem) {
      return '';
    }
    return (string) $this->nameFormatter->format(
      $item->filteredArray(),
      $format_id,
      $options['langcode'] ?? NULL,
    );
  }

}
