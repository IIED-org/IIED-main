<?php

namespace Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder;

use Drupal\Core\Field\FieldItemList;
use Drupal\facets_pretty_paths\Coder\CoderPluginBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;

/**
 * List item facets pretty paths coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "list_item_coder",
 *   label = @Translation("List item + id"),
 *   description = @Translation("Use list item with value id, e.g. /color/<strong>blue-2</strong>")
 * )
 */
class ListItemCoder extends CoderPluginBase {

  /**
   * Encode an id into an alias.
   *
   * @param string $id
   *   Value id.
   *
   * @return string
   *   An alias.
   */
  public function encode($id) {
    /** @var \Drupal\facets\Entity\Facet $facet */
    $facet = $this->configuration['facet'];
    $field = $facet->getFieldIdentifier();

    $definition = $facet->getFacetSource()->getDataDefinition($field);
    $options = $definition->getSetting('allowed_values') ?? [];
    if (!array_key_exists($id, $options)) {
      return $id;
    }
    $label = $options[$id];
    $label = \Drupal::service('pathauto.alias_cleaner')->cleanString($label);
    return $label . '-' . $id;
  }

  /**
   * Decodes an alias back to an id.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   An id.
   */
  public function decode($alias) {
    $exploded = explode('-', $alias);
    $id = array_pop($exploded);

    return $id;
  }

}
