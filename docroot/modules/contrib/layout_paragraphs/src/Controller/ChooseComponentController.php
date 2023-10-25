<?php

namespace Drupal\layout_paragraphs\Controller;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_paragraphs\Utility\Dialog;
use Symfony\Component\HttpFoundation\Request;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutRefreshTrait;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;

/**
 * ChooseComponentController controller class.
 *
 * Returns a list of links for available component types that can
 * be added to a layout region.
 */
class ChooseComponentController extends ControllerBase {

  use AjaxHelperTrait;
  use LayoutParagraphsLayoutRefreshTrait;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Construct a Layout Paragraphs Editor controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Builds the component menu.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   *
   * @return array
   *   The build array.
   */
  public function list(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout) {
    $route_params = [
      'layout_paragraphs_layout' => $layout_paragraphs_layout->id(),
    ];
    $context = $this->getContextFromRequest($request);
    // If inserting a new item adjecent to a sibling component, the region
    // passed in the URL will be incorrect if the existing sibling component
    // was dragged into another region. In that case, always use the existing
    // sibling's region.
    if ($context['sibling_uuid']) {
      $sibling = $layout_paragraphs_layout->getComponentByUuid($context['sibling_uuid']);
      $context['region'] = $sibling->getRegion();
    }
    $types = $this->getAllowedComponentTypes($layout_paragraphs_layout, $context);
    // If there is only one type to render,
    // return the component form instead of a list of links.
    if (count($types) === 1) {
      return $this->componentForm(key($types), $layout_paragraphs_layout, $context);
    }
    else {
      return $this->componentMenu($types, $route_params, $context);
    }
  }

  /**
   * Returns a layout paragraphs component form using Ajax if appropriate.
   *
   * @param string $type_name
   *   The component (paragraph) type.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   * @param array $context
   *   The context for the new component.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   An ajax response or form render array.
   */
  protected function componentForm(string $type_name, LayoutParagraphsLayout $layout_paragraphs_layout, array $context) {
    $type = $this->entityTypeManager()->getStorage('paragraphs_type')->load($type_name);
    $form = $this->formBuilder()->getForm(
      $this->getInsertComponentFormClass(),
      $layout_paragraphs_layout,
      $type,
      $context['parent_uuid'],
      $context['region'],
      $context['sibling_uuid'],
      $context['placement']
    );
    if ($this->isAjax()) {
      $response = new AjaxResponse();
      $selector = Dialog::dialogSelector($layout_paragraphs_layout);
      $response->addCommand(new OpenDialogCommand($selector, $form['#title'], $form, Dialog::dialogSettings()));
      return $response;
    }
    return $form;
  }

  /**
   * Returns a rendered menu of component types.
   *
   * @param array $types
   *   The component types.
   *
   * @return array
   *   The component menu render array.
   */
  protected function componentMenu(array $types) {
    foreach ($types as &$type) {
      $type['url'] = $type['url_object']->toString();
      $type['link_attributes'] = new Attribute([
        'class' => ['use-ajax'],
      ]);
    }
    $section_components = array_filter($types, function ($type) {
      return $type['is_section'] === TRUE;
    });
    $content_components = array_filter($types, function ($type) {
      return $type['is_section'] === FALSE;
    });
    $empty_message = $this->config('layout_paragraphs.settings')->get('empty_message') ??
      $this->t('No components to add.');
    $component_menu = [
      '#title' => $this->t('Choose a component'),
      '#theme' => 'layout_paragraphs_builder_component_menu',
      '#attributes' => [
        'class' => ['lpb-component-list'],
      ],
      '#empty_message' => $empty_message,
      '#status_messages' => ['#type' => 'status_messages'],
      '#types' => [
        'layout' => $section_components,
        'content' => $content_components,
      ],
      '#attached' => [
        'library' => [
          'layout_paragraphs/component_list',
        ],
      ],
    ];
    return $component_menu;
  }

  /**
   * Returns an array that defines the context for the component being added.
   *
   * A context may have a parent_uuid and region, or a sibling_uuid and
   * placement. If the former, the item is being inserted in the region of a
   * layout component with the uuid of "parent_uuid". If the latter, the new
   * component is being inserted alongside the component with the uuid of
   * "sibling_uuid", based on the value in "placement" (before or after).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   The context into which the new component is being iserted.
   */
  protected function getContextFromRequest(Request $request) {
    return [
      'parent_uuid' => $request->query->get('parent_uuid', NULL),
      'region' => $request->query->get('region', NULL),
      'sibling_uuid' => $request->query->get('sibling_uuid', NULL),
      'placement' => $request->query->get('placement', NULL),
    ];
  }

  /**
   * Returns an array of allowed component types.
   *
   * Dispatches a LayoutParagraphsComponentMenuEvent object so the component
   * list can be manipulated based on the layout, the layout settings, the
   * parent uuid, and region.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout object.
   * @param string $parent_uuid
   *   The parent uuid of paragraph we are inserting a component into.
   * @param string $region
   *   The region we are inserting a component into.
   *
   * @return array[]
   *   Returns an array of allowed component types.
   */
  public function getAllowedComponentTypes(LayoutParagraphsLayout $layout, $context) {

    // @todo Document and add tests for what is happening here.
    $component_types = $this->getComponentTypes($layout, $context);
    $event = new LayoutParagraphsAllowedTypesEvent($component_types, $layout, $context);
    $this->eventDispatcher->dispatch($event, LayoutParagraphsAllowedTypesEvent::EVENT_NAME);
    return $event->getTypes();
  }

  /**
   * Returns an array of available component types.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout paragraphs layout.
   * @param array $context
   *   The context for the component to be added.
   *
   * @return array
   *   An array of available component types.
   */
  public function getComponentTypes(LayoutParagraphsLayout $layout, array $context) {

    $items = $layout->getParagraphsReferenceField();
    $settings = $items->getSettings()['handler_settings'];
    $sorted_bundles = $this->getSortedAllowedTypes($settings);
    $storage = $this->entityTypeManager()->getStorage('paragraphs_type');
    $types = [];
    foreach (array_keys($sorted_bundles) as $bundle) {
      if (TRUE === $this->entityTypeManager->getAccessControlHandler('paragraph')->createAccess($bundle)) {
        /** @var \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type */
        $paragraphs_type = $storage->load($bundle);
        $plugins = $paragraphs_type->getEnabledBehaviorPlugins();
        $section_component = isset($plugins['layout_paragraphs']);
        $path = '';
        // Get the icon and pass to Javascript.
        if (method_exists($paragraphs_type, 'getIconUrl')) {
          $path = $paragraphs_type->getIconUrl();
        }
        $route_params = [
          'layout_paragraphs_layout' => $layout->id(),
          'paragraph_type' => $paragraphs_type->id(),
        ];
        $query_params = $context;
        $types[$bundle] = [
          'id' => $paragraphs_type->id(),
          'label' => $paragraphs_type->label(),
          'image' => $path,
          'description' => $paragraphs_type->getDescription(),
          'is_section' => $section_component,
          'url_object' => Url::fromRoute('layout_paragraphs.builder.insert', $route_params, ['query' => $context]),
        ];
      }
    }
    return $types;
  }

  /**
   * Returns an array of sorted allowed component / paragraph types.
   *
   * @param array $settings
   *   The handler settings.
   *
   * @return array
   *   An array of sorted, allowed paragraph bundles.
   */
  protected function getSortedAllowedTypes(array $settings) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
    if (!empty($settings['target_bundles'])) {
      if (isset($settings['negate']) && $settings['negate'] == '1') {
        $bundles = array_diff_key($bundles, $settings['target_bundles']);
      }
      else {
        $bundles = array_intersect_key($bundles, $settings['target_bundles']);
      }
    }

    // Support for the paragraphs reference type.
    if (!empty($settings['target_bundles_drag_drop'])) {
      $drag_drop_settings = $settings['target_bundles_drag_drop'];
      $max_weight = count($bundles);

      foreach ($drag_drop_settings as $bundle_info) {
        if (isset($bundle_info['weight']) && $bundle_info['weight'] && $bundle_info['weight'] > $max_weight) {
          $max_weight = $bundle_info['weight'];
        }
      }

      // Default weight for new items.
      $weight = $max_weight + 1;
      foreach ($bundles as $machine_name => $bundle) {
        $return_bundles[$machine_name] = [
          'label' => $bundle['label'],
          'weight' => $drag_drop_settings[$machine_name]['weight'] ?? $weight,
        ];
        $weight++;
      }
    }
    else {
      $weight = 0;

      foreach ($bundles as $machine_name => $bundle) {
        $return_bundles[$machine_name] = [
          'label' => $bundle['label'],
          'weight' => $weight,
        ];

        $weight++;
      }
    }
    uasort($return_bundles, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $return_bundles;
  }

  /**
   * Returns the insert component form class.
   */
  protected function getInsertComponentFormClass() {
    return '\Drupal\layout_paragraphs\Form\InsertComponentForm';
  }

}
