<?php

namespace Drupal\layout_paragraphs;

use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Class definition for a layout paragraphs service.
 */
class LayoutParagraphsRendererService {

  /**
   * The layout plugin manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of parent entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected static $parentEntities;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(LayoutPluginManagerInterface $layout_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Renders a single Layout Paragraph Section for the provided paragraph.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   The component render array.
   */
  public function renderLayoutSection(array &$build, ParagraphInterface $paragraph, string $view_mode = 'default') {
    if (!LayoutParagraphsComponent::isLayoutComponent($paragraph)) {
      // @todo Throw an exception if $paragraph does not have a layout applied.
      return [];
    }
    if ($paragraph->_referringItem) {
      $layout = new LayoutParagraphsLayout($paragraph->_referringItem->getParent());
    }
    else {
      $parent_entity = $this->getParentEntity($paragraph);
      $field_name = $paragraph->get('parent_field_name')->value;
      $layout = new LayoutParagraphsLayout($parent_entity->$field_name);
    }
    $section = $layout->getLayoutSection($paragraph);
    return $this->buildLayoutSection($section, $view_mode);
  }

  /**
   * Build the render array for Layout Paragraph Section.
   *
   * @param LayoutParagraphsSection $section
   *   The layout paragraph section.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   The build array.
   */
  public function buildLayoutSection(LayoutParagraphsSection $section, $view_mode = '') {

    $view_builder = $this->entityTypeManager->getViewBuilder('paragraph');
    $config = $section->getLayoutConfiguration();
    $config['layout_paragraphs_section'] = $section;
    $layout = $this
      ->layoutPluginManager
      ->createInstance($section->getLayoutId(), $config);

    // Map rendered paragraphs into their respective regions.
    $regions = $layout->getPluginDefinition()->getRegions();
    foreach (array_keys($regions) as $region) {
      $regions[$region] = array_map(function ($component) use ($view_builder, $view_mode) {
        $entity = $component->getEntity();
        $access = $entity->access('view', NULL, TRUE);
        if ($access->isAllowed()) {
          return $view_builder->view($component->getEntity(), $view_mode);
        }
      }, $section->getComponentsForRegion($region));
    }
    return $layout->build($regions);
  }

  /**
   * Returns the parent entity for a given paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function getParentEntity(ParagraphInterface $paragraph) {
    $type = $paragraph->get('parent_type')->value;
    $id = $paragraph->get('parent_id')->value;
    if (!isset(static::$parentEntities["{$type}:{$id}"])) {
      static::$parentEntities["{$type}:{$id}"] = $paragraph->getParentEntity();
    }
    return static::$parentEntities["{$type}:{$id}"];
  }

}
