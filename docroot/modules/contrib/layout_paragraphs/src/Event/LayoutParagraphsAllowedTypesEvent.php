<?php

namespace Drupal\layout_paragraphs\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;

/**
 * Class definition for Layout Paragraphs Allowed Types event.
 *
 * Developers can subscribe to this event and alter the Layout Paragraphs
 * component types (aka Paragraph Types) that are rendered in the "Choose a
 * Component" popup. For example: if you wished to develop a module that limits
 * what Paragraph Types can be added in particular, specific regions/layout
 * combinations.
 */
class LayoutParagraphsAllowedTypesEvent extends Event {

  const EVENT_NAME = 'layout_paragraphs_allowed_types';

  /**
   * An array of component (paragraph) types.
   *
   * @var array
   */
  protected $types = [];

  /**
   * The layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layout;

  /**
   * The parent uuid.
   *
   * @var string
   */
  protected $parentUuid;

  /**
   * The region name.
   *
   * @var string
   */
  protected $region;

  /**
   * The context a new compoment is being added into.
   *
   * @var array
   */
  protected $context;

  /**
   * Class cosntructor.
   *
   * @param array $types
   *   An array of paragraph types.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout object.
   * @param string $parent_uuid
   *   The parent uuid.
   * @param string $region
   *   The region.
   */
  public function __construct(array $types, LayoutParagraphsLayout $layout, $context) {
    $this->types = $types;
    $this->layout = $layout;
    $this->context = $context;
    $this->parentUuid = $context['parent_uuid'];
    $this->region = $context['region'];
  }

  /**
   * Returns the array of types.
   *
   * @return array[]
   *   The types array.
   */
  public function getTypes() {
    return $this->types;
  }

  /**
   * Sets the types array.
   *
   * @param array $types
   *   The types array.
   *
   * @return $this
   */
  public function setTypes(array $types) {
    $this->types = $types;
    return $this;
  }

  /**
   * Get the parent uuid.
   *
   * @return string
   *   The parent uuid.
   */
  public function getParentUuid() {
    return $this->parentUuid;
  }

  /**
   * Get the region.
   *
   * @return string
   *   The region.
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * Get the context.
   *
   * @return array
   *   The context.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Get the layout.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsLayout
   *   The layout object.
   */
  public function getLayout() {
    return $this->layout;
  }

}
