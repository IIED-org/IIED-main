<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations that expose Name module's own widget layouts.
 *
 * @internal
 */
final class WidgetLayoutHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_name_widget_layouts().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('name_widget_layouts')] // @phpstan-ignore attribute.notFound
  public function nameWidgetLayouts(): array {
    return [
      'stacked' => [
        'label' => $this->t('Stacked'),
      ],
      'inline' => [
        'label'              => $this->t('Inline'),
        'library'            => [
          'name/widget.inline',
        ],
        'wrapper_attributes' => [
          'class' => ['form--inline', 'clearfix'],
        ],
      ],
    ];
  }

}
