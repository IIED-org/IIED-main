<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Resolves preferred/alternative field reference values for name formatting.
 *
 * @internal
 */
class AdditionalComponentService implements AdditionalComponentInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * Gets the rendered or label value for an additional component reference.
   */
  public function getAdditionalComponent(FieldItemListInterface $items, mixed $key_value, mixed $sep_value): string {
    if (!$key_value) {
      return '';
    }

    $parent = $items->getEntity();
    $key    = (string) $key_value;

    if ($key === '_self') {
      return $this->resolveSelf($parent);
    }

    if (!$parent instanceof FieldableEntityInterface) {
      return '';
    }

    if (str_starts_with($key, '_self_property')) {
      return $this->resolveSelfProperty($parent, $key);
    }

    return $this->resolveField($parent, $key, $sep_value);
  }

  /**
   * Resolves the parent entity label.
   */
  private function resolveSelf(EntityInterface $parent): string {
    return (string) ($parent->label() ?: '');
  }

  /**
   * Resolves a scalar value from a parent entity property.
   */
  private function resolveSelfProperty(FieldableEntityInterface $parent, string $key): string {
    $property = str_replace('_self_property_', '', $key);

    try {
      $item = $parent->get($property);
    }
    catch (\InvalidArgumentException $e) {
      return '';
    }

    return empty($item->value) ? '' : (string) $item->value;
  }

  /**
   * Resolves labels or rendered values from a parent field.
   */
  private function resolveField(FieldableEntityInterface $parent, string $key, mixed $sep_value): string {
    if (!$parent->hasField($key)) {
      return '';
    }

    $target_items = $parent->get($key);
    if ($target_items->isEmpty() || !$target_items->access('view')) {
      return '';
    }

    $field  = $target_items->getFieldDefinition();
    $values = match ($field->getType()) {
      'entity_reference' => $this->collectEntityReferenceLabels($target_items),
      default => $this->collectRenderedValues($parent, $target_items),
    };

    if (!$values) {
      return '';
    }

    $separator = Html::decodeEntities(trim(strip_tags((string) $sep_value)));
    return implode($separator, $values);
  }

  /**
   * Collects labels from viewable referenced entities.
   *
   * @return string[]
   *   The collected entity labels.
   */
  private function collectEntityReferenceLabels(FieldItemListInterface $target_items): array {
    $values = [];

    foreach ($target_items as $item) {
      if (empty($item->entity) || !$item->entity->access('view')) {
        continue;
      }

      if ($label = $item->entity->label()) {
        $values[] = $label;
      }
    }

    return $values;
  }

  /**
   * Collects rendered, unescaped values from field items.
   *
   * @return string[]
   *   The collected rendered values.
   */
  private function collectRenderedValues(FieldableEntityInterface $parent, FieldItemListInterface $target_items): array {
    $values       = [];
    $view_builder = $this->entityTypeManager->getViewBuilder($parent->getEntityTypeId());

    foreach ($target_items as $item) {
      try {
        $renderable = $view_builder->viewFieldItem($item, ['label' => 'hidden']);
        /** @var \Drupal\Component\Render\MarkupInterface|string $value */
        $value = (string) $this->renderer->render($renderable);
      }
      catch (\Exception $e) {
        continue;
      }

      // Remove any markup, but decode entities as the parser requires raw
      // unescaped strings.
      if ($value = trim(strip_tags($value))) {
        $values[] = Html::decodeEntities($value);
      }
    }

    return $values;
  }

}
