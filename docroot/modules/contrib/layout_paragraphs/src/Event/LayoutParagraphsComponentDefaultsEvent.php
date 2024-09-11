<?php

namespace Drupal\layout_paragraphs\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * An event for altering the type and default values for new components.
 */
class LayoutParagraphsComponentDefaultsEvent extends Event {

  // This makes it easier for subscribers to reliably use our event name.
  const EVENT_NAME = 'layout_paragraphs_component_defaults';

  /**
   * Constructs the object.
   *
   * @param string $paragraphTypeId
   *   The paragraph type.
   * @param array $defaultValues
   *   The default values for the paragraph.
   */
  public function __construct(
    protected string $paragraphTypeId,
    protected array $defaultValues) {
  }

  /**
   * Sets the paragraph type.
   *
   * @param string $paragraph_type_id
   *   The paragraph type.
   *
   * @return $this
   */
  public function setParagraphTypeId(string $paragraph_type_id): self {
    $this->paragraphTypeId = $paragraph_type_id;
    return $this;
  }

  /**
   * Gets the paragraph type.
   *
   * @return string
   *   The paragraph type.
   */
  public function getParagraphTypeId(): string {
    return $this->paragraphTypeId;
  }

  /**
   * Sets the default values for the paragraph.
   *
   * @param array $defaultValues
   *   The default values for the paragraph.
   *
   * @return $this
   */
  public function setDefaultValues(array $defaultValues): self {
    $this->defaultValues = $defaultValues;
    return $this;
  }

  /**
   * Gets the default values for the paragraph.
   *
   * @return array
   *   The default values for the paragraph.
   */
  public function getDefaultValues(): array {
    return $this->defaultValues;
  }

}
