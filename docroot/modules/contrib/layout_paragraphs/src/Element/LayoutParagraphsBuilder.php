<?php

namespace Drupal\layout_paragraphs\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;
use Drupal\layout_paragraphs\LayoutParagraphsSection;
use Drupal\layout_paragraphs\Utility\Dialog;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a render element for building the Layout Builder UI.
 *
 * @RenderElement("layout_paragraphs_builder")
 *
 * @internal
 *   Plugin classes are internal.
 */
class LayoutParagraphsBuilder extends RenderElement implements ContainerFactoryPluginInterface {

  /**
   * The layout paragraphs tempstore service.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Layouts Manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Indicates whether the element is in translation mode.
   *
   * @var bool
   */
  protected $isTranslating;

  /**
   * The layout paragraphs layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layoutParagraphsLayout;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LayoutParagraphsLayoutTempstoreRepository $tempstore_repository,
    EntityTypeManagerInterface $entity_type_manager,
    LayoutPluginManagerInterface $layout_plugin_manager,
    RendererInterface $renderer,
    EntityTypeBundleInfoInterface $entity_type_bundle_info) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempstore = $tempstore_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->renderer = $renderer;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.core.layout'),
      $container->get('renderer'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Properties:
   * - #layout_paragraphs_layout: a LayoutParagraphsLayout instance.
   * - #uuid: if provided, the uuid of the single paragraph to render.
   * - #is_translating: if translating content.
   */
  public function getInfo() {
    return [
      '#layout_paragraphs_layout' => NULL,
      '#uuid' => NULL,
      '#theme' => 'layout_paragraphs_builder',
      '#is_translating' => NULL,
      '#pre_render' => [
        [$this, 'preRender'],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders the UI.
   *
   * @todo Better inline comments for all functionality in this method.
   */
  public function preRender($element) {
    $this->layoutParagraphsLayout = $this->tempstore->get($element['#layout_paragraphs_layout']);
    $this->isTranslating = $element['#is_translating'] ?? FALSE;
    $element_uuid = $element['#uuid'];
    $preview_view_mode = $this->layoutParagraphsLayout->getSetting('preview_view_mode', 'default');

    $element['#layout_paragraphs_layout'] = $this->layoutParagraphsLayout;
    $element['#components'] = [];
    if ($this->isTranslating()) {
      $element['#translation_warning'] = $this->translationWarning();
    }
    // Build a flat list of component build arrays.
    foreach ($this->layoutParagraphsLayout->getComponents() as $component) {
      /** @var \Drupal\layout_paragraphs\LayoutParagraphsComponent $component */
      $element['#components'][$component->getEntity()->uuid()] = $this->buildComponent($component, $preview_view_mode);
    }

    // Nest child components inside their respective sections and regions.
    foreach ($this->layoutParagraphsLayout->getComponents() as $component) {
      /** @var \Drupal\layout_paragraphs\LayoutParagraphsComponent $component */
      $uuid = $component->getEntity()->uuid();
      if ($component->isLayout()) {
        $section = $this->layoutParagraphsLayout->getLayoutSection($component->getEntity());
        $layout_plugin_instance = $this->layoutPluginInstance($section);
        foreach (array_keys($element['#components'][$uuid]['regions']) as $region_name) {
          foreach ($section->getComponentsForRegion($region_name) as $child_component) {
            $child_uuid = $child_component->getEntity()->uuid();
            $element['#components'][$uuid]['regions'][$region_name][$child_uuid] =& $element['#components'][$child_uuid];
          }
        }
        $element['#components'][$uuid]['regions'] = $layout_plugin_instance->build($element['#components'][$uuid]['regions']);
        $element['#components'][$uuid]['regions']['#weight'] = 1000;
      }
    }

    // If an element #uuid is provided, render the matching element.
    // This is used in cases where a single component needs
    // to be rendered - for example, as part of an AJAX response.
    if ($element_uuid) {
      if (isset($element['#components'][$element_uuid])) {
        return [
          'build' => $element['#components'][$element_uuid],
        ];
      }
    }

    $element['#attributes'] = [
      'class' => [
        'lp-builder',
        'lp-builder-' . $this->layoutParagraphsLayout->id(),
      ],
      'data-lpb-ui-id' => $this->layoutParagraphsLayout->id(),
      'data-lpb-id' => $this->layoutParagraphsLayout->id(),
    ] + ($element['#attributes'] ?? []);
    $element['#attached']['library'] = ['layout_paragraphs/builder'];
    $element['#attached']['drupalSettings']['lpBuilder'][$this->layoutParagraphsLayout->id()] = $this->layoutParagraphsLayout->getSettings();
    $element['#is_empty'] = $this->layoutParagraphsLayout->isEmpty();
    $element['#empty_message'] = $this->layoutParagraphsLayout->getSetting('empty_message', $this->t('Start adding content.'));
    $element['#root_components'] = [];
    foreach ($this->layoutParagraphsLayout->getRootComponents() as $component) {
      /** @var \Drupal\layout_paragraphs\LayoutParagraphsComponent $component */
      $uuid = $component->getEntity()->uuid();
      $element['#root_components'][$uuid] =& $element['#components'][$uuid];
    }
    if (count($element['#root_components'])) {
      $element['#attributes']['class'][] = 'has-components';
    }
    else {
      if ($this->layoutParagraphsLayout->getSetting('require_layouts', FALSE)) {
        $this->addJsUiElement(
          $element,
          $this->doRender($this->insertSectionButton(['layout_paragraphs_layout' => $this->layoutParagraphsLayout->id()], [], 0, ['center'])),
          'insert'
        );
      }
      else {
        $this->addJsUiElement(
          $element,
          $this->doRender($this->insertComponentButton(['layout_paragraphs_layout' => $this->layoutParagraphsLayout->id()], [], 0, ['center'])),
          'insert'
        );
      }
    }
    return $element;
  }

  /**
   * Returns the build array for a single layout component.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsComponent $component
   *   The component to render.
   * @param string $preview_view_mode
   *   The view mode to use for rendering paragraphs.
   *
   * @return array
   *   The build array.
   */
  protected function buildComponent(LayoutParagraphsComponent $component, $preview_view_mode = 'default') {
    $entity = $component->getEntity();
    $entity->_layoutParagraphsBuilder = TRUE;
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $build = $view_builder->view($entity, $preview_view_mode, $entity->language()->getId());
    $build['#post_render'] = [
      [$this, 'postRenderComponent'],
    ];
    $build['#attributes']['data-uuid'] = $entity->uuid();
    $build['#attributes']['data-type'] = $entity->bundle();
    $build['#attributes']['data-lpb-ui-id'] = $entity->uuid() ?? 'new-' . $entity->bundle();
    $build['#attributes']['class'][] = 'js-lpb-component';
    $build['#layout_paragraphs_component'] = TRUE;
    if ($entity->isNew()) {
      $build['#attributes']['class'][] = 'is_new';
    }

    $url_params = [
      'layout_paragraphs_layout' => $this->layoutParagraphsLayout->id(),
    ];
    $query_params = [
      'sibling_uuid' => $entity->uuid(),
    ];
    if ($parent_uuid = $component->getParentUuid()) {
      $query_params['parent_uuid'] = $parent_uuid;
    }
    if ($region = $component->getRegion()) {
      $query_params['region'] = $region;
    }

    $controls = [
      '#theme' => 'layout_paragraphs_builder_controls',
      '#attributes' => [
        'class' => [
          'lpb-controls',
        ],
      ],
      '#uuid' => $entity->uuid(),
      '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
      '#edit_access' => $this->editAccess($entity),
      '#duplicate_access' => $this->duplicateAccess($entity) && $this->checkCardinality(),
      '#delete_access' => $this->deleteAccess($entity),
    ];
    $build['#attached']['drupalSettings']['lpBuilder']['uiElements'][$entity->uuid()] = [];
    $this->addJsUiElement($build, $this->doRender($controls), 'controls', 'prepend');

    if ($this->createAccess() && $this->checkCardinality()) {
      if (!$component->getParentUuid() && $this->layoutParagraphsLayout->getSetting('require_layouts')) {
        $this->addJsUiElement(
          $build,
          $this->doRender($this->insertSectionButton($url_params, $query_params + ['placement' => 'before'], -10000, ['before'])),
          'insert_before',
          'prepend'
        );
        $this->addJsUiElement(
          $build,
          $this->doRender($this->insertSectionButton($url_params, $query_params + ['placement' => 'after'], 10000, ['after'])),
          'insert_after',
          'append'
        );
      }
      else {
        $this->addJsUiElement(
          $build,
          $this->doRender($this->insertComponentButton($url_params, $query_params + ['placement' => 'before'], -10000, ['before'])),
          'insert_before',
          'prepend'
        );
        $this->addJsUiElement(
          $build,
          $this->doRender($this->insertComponentButton($url_params, $query_params + ['placement' => 'after'], -10000, ['after'])),
          'insert_after',
          'append'
        );
      }
    }

    if ($component->isLayout()) {
      $section = $this->layoutParagraphsLayout->getLayoutSection($entity);
      $layout_instance = $this->layoutPluginInstance($section);
      $region_names = $layout_instance->getPluginDefinition()->getRegionNames();

      $build['#attributes']['class'][] = 'lpb-layout';
      $build['#attributes']['data-layout'] = $section->getLayoutId();
      $build['#layout_plugin_instance'] = $layout_instance;
      $build['regions'] = [];
      foreach ($region_names as $region_name) {
        $url_params = [
          'layout_paragraphs_layout' => $this->layoutParagraphsLayout->id(),
        ];
        $query_params = [
          'parent_uuid' => $entity->uuid(),
          'region' => $region_name,
        ];
        $build['regions'][$region_name] = [
          '#attributes' => [
            'class' => [
              'js-lpb-region',
            ],
            'data-region' => $region_name,
            'data-region-uuid' => $entity->uuid() . '-' . $region_name,
            'data-lpb-ui-id' => $entity->uuid() . '-' . $region_name,
          ],
        ];
        if ($this->createAccess() && $this->checkCardinality()) {
          $this->addJsUiElement(
            $build['regions'][$region_name],
            $this->doRender($this->insertComponentButton($url_params, $query_params, 10000, ['center'])),
            'insert'
          );
        }
      }
    }
    return $build;
  }

  /**
   * Filters problematic markup from rendered component.
   *
   * @param mixed $content
   *   The rendered content.
   * @param array $element
   *   The render element array.
   *
   * @return mixed
   *   The filtered content.
   */
  public function postRenderComponent($content, array $element) {
    if (strpos($content, '<form') !== FALSE) {
      // Because the Layout Paragraphs Builder is often rendered within a form,
      // we need to strip out any form tags, "name" attributes, and "required"
      // attributes to prevent Drupal from attempting to process the form when
      // the parent entity is saved.
      // @see https://www.drupal.org/project/layout_paragraphs/issues/3263715
      // First, replace form tags with divs.
      $search = [
        '<form',
        '</form>',
      ];
      $replace = [
        '<div',
        '</div>',
      ];
      $content = str_replace($search, $replace, $content);
      // Strip out "name" attributes.
      $content = preg_replace('/(<[^>]+) name\s*=\s*".*?"/i', '$1', $content);
      // Strip out "required" attributes.
      $content = preg_replace('/(<[^>]+) required\s*=\s*".*?"/i', '$1', $content);
    }
    return $content;
  }

  /**
   * Returns the render array for a insert component button.
   *
   * @param array[] $route_params
   *   The route parameters for the link.
   * @param array[] $query_params
   *   The query parameters for the link.
   * @param int $weight
   *   The weight of the button element.
   * @param array[] $classes
   *   A list of classes to append to the container.
   *
   * @return array
   *   The render array.
   */
  protected function insertComponentButton(array $route_params = [], array $query_params = [], int $weight = 0, array $classes = []) {
    return [
      '#theme' => 'layout_paragraphs_insert_component_btn',
      '#title' => Markup::create('<span class="visually-hidden">' . $this->t('Choose component') . '</span>'),
      '#weight' => $weight,
      '#attributes' => [
        'class' => array_merge(['lpb-btn--add', 'use-ajax'], $classes),
        'data-dialog-type' => 'dialog',
        'data-dialog-options' => Json::encode([
          'target' => Dialog::dialogId($this->layoutParagraphsLayout),
          'modal' => TRUE,
          'drupalAutoButtons' => FALSE,
          'dialogClass' => 'lpb-dialog',
        ]),
      ],
      '#url' => Url::fromRoute('layout_paragraphs.builder.choose_component', $route_params, ['query' => $query_params]),
    ];
  }

  /**
   * Returns the render array for a create section button.
   *
   * @param array[] $route_params
   *   The route parameters for the link.
   * @param array[] $query_params
   *   The query parameters for the link.
   * @param int $weight
   *   The weight of the button element.
   * @param array[] $classes
   *   A list of classes to append to the container.
   *
   * @return array
   *   The render array.
   */
  protected function insertSectionButton(array $route_params = [], array $query_params = [], int $weight = 0, array $classes = []) {
    return [
      '#theme' => 'layout_paragraphs_insert_component_btn',
      '#title' => Markup::create($this->t('Add section')),
      '#attributes' => [
        'class' => array_merge(['lpb-btn', 'use-ajax'], $classes),
        'data-dialog-type' => 'dialog',
        'data-dialog-options' => Json::encode(Dialog::dialogSettings($this->layoutParagraphsLayout)),
        'drupalAutoButtons' => FALSE,
        'dialogClass' => 'lpb-dialog',
      ],
      '#url' => Url::fromRoute('layout_paragraphs.builder.choose_component', $route_params, ['query' => $query_params]),
    ];
  }

  /**
   * Returns an array of dialog options.
   */
  protected function dialogOptions() {
    return [
      'modal' => TRUE,
      'target' => Dialog::dialogId($this->layoutParagraphsLayout),
    ];
  }

  /**
   * Builds a translation warning message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translation warning.
   */
  protected function translationWarning() {
    if ($this->isTranslating()) {
      if ($this->supportsAsymmetricTranslations()) {
        return $this->t('You are in translation mode. Changes will only affect the current language.');
      }
      else {
        return $this->t('You are in translation mode. You cannot add or remove items while translating. Reordering items will affect all languages.');
      }
    }
  }

  /**
   * Loads a layout plugin instance for a layout paragraph section.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsSection $section
   *   The section.
   */
  protected function layoutPluginInstance(LayoutParagraphsSection $section) {
    $layout_id = $section->getLayoutId();
    $layout_config = $section->getLayoutConfiguration();
    $layout_config['layout_paragraphs_section'] = $section;
    $layout_instance = $this->layoutPluginManager->createInstance($layout_id, $layout_config);
    return $layout_instance;
  }

  /**
   * Returns an AccessResult object.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return bool
   *   True if user can edit.
   */
  protected function editAccess(ParagraphInterface $paragraph) {
    return $paragraph->access('update');
  }

  /**
   * Returns an AccessResult object.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return bool
   *   True if user can edit.
   */
  protected function deleteAccess(ParagraphInterface $paragraph) {
    if ($this->isTranslating() && !($this->supportsAsymmetricTranslations())) {
      $access = new AccessResultForbidden('Cannot delete paragraphs while in translation mode.');
      return $access->isAllowed();
    }
    return $paragraph->access('delete');
  }

  /**
   * Returns an AccessResult object.
   *
   * @return bool
   *   True if user can create.
   */
  protected function createAccess() {
    $access = new AccessResultAllowed();
    if ($this->isTranslating() && !($this->supportsAsymmetricTranslations())) {
      $access = new AccessResultForbidden('Cannot add paragraphs while in translation mode.');
    }
    return $access->isAllowed();
  }

  /**
   * Returns an AccessResult object.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return bool
   *   True if user can duplicate.
   */
  protected function duplicateAccess(ParagraphInterface $paragraph) {
    if ($this->isTranslating() && !($this->supportsAsymmetricTranslations())) {
      $access = new AccessResultForbidden('Cannot duplicate paragraphs while in translation mode.');
      return $access->isAllowed();
    }
    return $paragraph->access('create');
  }

  /**
   * Returns TRUE if in translation context.
   *
   * @return bool
   *   TRUE if translating.
   */
  protected function isTranslating() {
    return $this->isTranslating;
  }

  /**
   * Whether or not to support asymmetric translations.
   *
   * @see https://www.drupal.org/project/paragraphs/issues/2461695
   * @see https://www.drupal.org/project/paragraphs/issues/2904705
   * @see https://www.drupal.org/project/paragraphs_asymmetric_translation_widgets
   *
   * @return bool
   *   True if asymmetric translation is supported.
   */
  protected function supportsAsymmetricTranslations() {
    return $this->layoutParagraphsLayout->getParagraphsReferenceField()->getFieldDefinition()->isTranslatable();
  }

  /**
   * Adds a UI element to the Javascript settings array.
   *
   * Builder UI elements are attached to components (paragraphs)
   * in Javascript so that the UI is correctly rendered even when
   * the component (paragraph) template has been customized and the
   * contents of the content array are no longer output.
   *
   * @param array $build
   *   The build array to attach JS settings to.
   * @param \Drupal\Core\Render\Markup $element
   *   The UI element.
   * @param string $key
   *   The Javascript object key to use for storing the element.
   * @param string $method
   *   The javascript method to use to attach $element to its container.
   */
  public function addJsUiElement(array &$build, Markup $element, string $key, string $method = 'append') {
    $id = $build['#attributes']['data-lpb-ui-id'];
    $build['#attributes']['data-has-js-ui-element'] = TRUE;
    $build['#attached']['drupalSettings']['lpBuilder']['uiElements'][$id][$key] = [
      'element' => $element,
      'method' => $method,
    ];
  }

  /**
   * Processes a render array to markup.
   *
   * @param array $render_array
   *   The render array to process.
   *
   * @return \Drupal\Core\Render\Markup
   *   The markup object.
   */
  public function doRender(array $render_array) {
    return $this->renderer->render($render_array);
  }

  /**
   * Checks if adding a component would exceed the field's cardinality limit.
   *
   * @return bool
   *   True if a component can be added without exceeding cardinality.
   */
  protected function checkCardinality() {
    $cardinality = $this->getCardinality();
    if ($cardinality > 0) {
      $count = $this->layoutParagraphsLayout->getParagraphsReferenceField()->count();
      return $cardinality > $count;
    }
    return TRUE;
  }

  /**
   * Gets the cardinality field setting for a Layout Paragraphs reference field.
   *
   * @return int
   *   The cardinality setting.
   */
  protected function getCardinality() {
    $field_name = $this->layoutParagraphsLayout->getFieldName();
    $field_config = $this->layoutParagraphsLayout->getEntity()->{$field_name}->getFieldDefinition();
    $field_definition = $field_config->getFieldStorageDefinition();
    return $field_definition->getCardinality();
  }

}
