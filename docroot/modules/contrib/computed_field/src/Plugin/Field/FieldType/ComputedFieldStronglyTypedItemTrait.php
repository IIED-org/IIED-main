<?php

namespace Drupal\computed_field\Plugin\Field\FieldType;

/**
 * Common methods for strongly typed Computed Field FieldType plugins.
 *
 * This trait is necessary for Computed Field FieldType plugins that override
 * executeCode() to return specific types.  Any code that requires the raw
 * result must get it from the parent.  An example of this is checking for empty
 * values, which fails if the type is no longer null or the empty string.
 */
trait ComputedFieldStronglyTypedItemTrait {

  /**
   * {@inheritdoc}
   */
  protected function getRawResult() {
    return parent::executeCode();
  }

}
