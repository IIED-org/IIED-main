<?php

namespace Drupal\layout_paragraphs;

use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a domain object for a Layout Paragraphs Section.
 *
 * A Layout Paragraphs Section is a Layout Paragraphs Component
 * with a Layout applied.
 *
 * See also:
 * - Drupal\layout_paragraphs\LayoutParagraphsComponent
 * - Drupal\layout_paragraphs\LayoutParagraphsLayout
 */
class LayoutParagraphsSection extends LayoutParagraphsComponent {

  /**
   * An array of components.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsComponent|LayoutParagraphsSection[]
   */
  protected $components;

  /**
   * Constructor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph this layout section is attached to.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsComponent[] $components
   *   An array of child components.
   */
  public function __construct(
    ParagraphInterface $paragraph,
    array $components = []
  ) {
    parent::__construct($paragraph);
    $this->components = $components;
  }

  /**
   * Wraps the paragraph is the correct component class.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return LayoutParagraphsComponent|LayoutParagraphsSection
   *   The component.
   */
  public function getComponent(ParagraphInterface $paragraph) {
    foreach ($this->components as $component) {
      if ($component->getEntity()->uuid() == $paragraph->uuid()) {
        return $component;
      }
    }
  }

  /**
   * Returns the child component with matching uuid.
   *
   * @param string $uuid
   *   The uuid to search for.
   *
   * @return LayoutParagraphsComponent
   *   The component.
   */
  public function getComponentByUuid($uuid) {
    foreach ($this->getComponents() as $component) {
      if ($component->getEntity()->uuid() == $uuid) {
        return $component;
      }
    }
  }

  /**
   * Get the components for a single region.
   *
   * @param string $region
   *   The region name.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsComponent[]
   *   An array of components.
   */
  public function getComponentsForRegion(string $region) {
    return array_filter($this->getComponents(), function (LayoutParagraphsComponent $component) use ($region) {
      return $component->getRegion() == $region;
    });
  }

  /**
   * Returns a list of all components for this collection.
   *
   * @return array
   *   An array of layout paragraph components.
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Returns the layout plugin id.
   *
   * @return string
   *   The layout id.
   */
  public function getLayoutId() {
    $settings = $this->getSettings();
    return $settings['layout'];
  }

  /**
   * Returns the layout plugin settings for the provided paragraph.
   *
   * @return array
   *   The settings array.
   */
  public function getLayoutConfiguration() {
    return $this->getSettings()['config'] ?? [];
  }

  /**
   * {@inheritDoc}
   */
  public function defaultSettings() {
    return [
      'layout' => '',
      'config' => [],
    ];
  }

}
