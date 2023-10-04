<?php

namespace Drupal\layout_paragraphs;

use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a domain object for a single Layout Paragraphs Component.
 *
 * A Layout Paragraphs Component wraps a paragraph entity and
 * provides APIs for working with the paragraph in the context of layouts.
 *
 * See also:
 * - Drupal\layout_paragraphs\LayoutParagraphsSection
 * - Drupal\layout_paragraphs\LayoutParagraphsLayout
 */
class LayoutParagraphsComponent {

  /**
   * The paragraph entity.
   *
   * @var \Drupal\paragraphs\ParagraphInterface
   */
  protected $paragraph;

  /**
   * Class constructor.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   */
  public function __construct(ParagraphInterface $paragraph) {
    $this->paragraph = $paragraph;
  }

  /**
   * Gets the region for the component.
   *
   * @return string
   *   The region.
   */
  public function getRegion() {
    return $this->getSetting('region');
  }

  /**
   * Static wrapper for isLayout().
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph to check if is layout.
   *
   * @return bool
   *   True if paragraph is a layout.
   */
  public static function isLayoutComponent(ParagraphInterface $paragraph) {
    $instance = new static($paragraph);
    return $instance->isLayout();
  }

  /**
   * Returns true if this component has a layout applied.
   *
   * @return bool
   *   True if is layout.
   */
  public function isLayout() {
    return !empty($this->getSetting('layout'));
  }

  /**
   * Returns true if this component has a layout applied.
   *
   * @return bool
   *   True if is layout.
   */
  public function hasLayout() {
    return !empty($this->getSetting('layout'));
  }

  /**
   * Returns true if disabled.
   *
   * @return bool
   *   True if disabled.
   */
  public function isDisabled() {
    return $this->getSetting('region') == '_disabled';
  }

  /**
   * Static wrapper for isRoot().
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return bool
   *   True if this item has no parent.
   */
  public static function isRootComponent(ParagraphInterface $paragraph) {
    $component = new static($paragraph);
    return $component->isRoot();
  }

  /**
   * A "root" component is rendered at the top level.
   *
   * @return bool
   *   True if component is a root element.
   */
  public function isRoot() {
    return !$this->getParentUuid() && !$this->isDisabled();
  }

  /**
   * Returns the parent component if one exists.
   *
   * @return \Drupal\paragraphs\ParagraphInterface|false
   *   The parent paragraph or false if doesn't exist.
   */
  public function getParentUuid() {
    return $this->getSetting('parent_uuid');
  }

  /**
   * Returns the wrapped paragraph entity.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The paragraph entity.
   */
  public function getEntity() {
    return $this->paragraph;
  }

  /**
   * Returns a single layout paragraph setting.
   *
   * @param string $key
   *   The setting to return.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($key) {
    $settings = $this->getSettings();
    return $settings[$key] ?? NULL;
  }

  /**
   * Returns the layout paragraph's behavior settings.
   *
   * @return array
   *   The settings array.
   */
  public function getSettings() {
    $behaviors_settings = $this->paragraph->getAllBehaviorSettings();
    $layout_behavior_settings = $behaviors_settings['layout_paragraphs'] ?? [];
    $defaults = $this->defaultSettings();
    return $layout_behavior_settings + $defaults;
  }

  /**
   * Sets the layout paragraph's behavior settings.
   *
   * @param array $settings
   *   The layout settings.
   */
  public function setSettings(array $settings) {
    $behaviors_settings = $this->paragraph->getAllBehaviorSettings();
    $layout_behavior_settings = $behaviors_settings['layout_paragraphs'] ?? [];
    $layout_behavior_settings = array_merge($layout_behavior_settings, $settings);
    $this->paragraph->setBehaviorSettings('layout_paragraphs', $layout_behavior_settings);
    $this->paragraph->setNeedsSave(TRUE);
  }

  /**
   * Returns an array of default settings.
   *
   * @return array
   *   The default settings.
   */
  protected function defaultSettings() {
    return [
      'region' => '',
      'parent_uuid' => '',
    ];
  }

}
