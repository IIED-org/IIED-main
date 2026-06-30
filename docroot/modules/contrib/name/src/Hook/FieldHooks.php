<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Hook implementations for field API integration (Views, categories).
 *
 * @internal
 */
final class FieldHooks {

  /**
   * Implements hook_field_views_data().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('field_views_data')] // @phpstan-ignore attribute.notFound
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    // `views.field_data_provider` exists on Drupal 11.2+. Fall back to the
    // procedural helper on earlier versions. Use the global container so we
    // do not require the missing service to be resolvable via autowire.
    $data = DeprecationHelper::backwardsCompatibleCall(
      currentVersion:     \Drupal::VERSION,
      deprecatedVersion:  '11.2.0',
      // @phpstan-ignore-next-line globalDrupalDependencyInjection.useDependencyInjection
      currentCallable:    fn() => \Drupal::service('views.field_data_provider')
        ->defaultFieldImplementation($field_storage),
      deprecatedCallable: fn() => views_field_default_views_data($field_storage),
    );

    $field_name = $field_storage->getName();
    $field_type = $field_storage->getType();

    $columns = [
      'title'        => 'standard',
      'given'        => 'standard',
      'middle'       => 'standard',
      'family'       => 'standard',
      'generational' => 'standard',
      'credentials'  => 'standard',
    ];

    foreach ($data as $table_name => $table_data) {
      $data[$table_name][$field_name]['filter'] = [
        'field'       => $field_name,
        'table'       => $table_name,
        'field_name'  => $field_name,
        'id'          => 'name_fulltext',
        'allow_empty' => TRUE,
      ];

      if ($field_type === 'name') {
        // Add every name column as a view field for every name field.
        foreach ($columns as $column => $plugin_id) {
          $data[$table_name][$field_name . '_' . $column]['field'] = [
            'id'         => $plugin_id,
            'field_name' => $field_name,
            'property'   => $column,
          ];
        }
      }
    }

    return $data;
  }

  /**
   * Implements hook_field_type_category_info_alter().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('field_type_category_info_alter')] // @phpstan-ignore attribute.notFound
  public function fieldTypeCategoryInfoAlter(array &$definitions): void {
    // The `name` field type belongs in the `general` category, so the
    // libraries need to be attached using an alter hook.
    $definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY]['libraries'][] = 'name/field_ui';
  }

}
