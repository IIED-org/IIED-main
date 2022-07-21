<?php

namespace Drupal\layout_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout Paragraphs field formatter.
 *
 * @FieldFormatter(
 *   id = "layout_paragraphs",
 *   label = @Translation("Layout Paragraphs"),
 *   description = @Translation("Renders paragraphs with layout."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class LayoutParagraphsFormatter extends EntityReferenceRevisionsEntityFormatter implements ContainerFactoryPluginInterface {

  /**
   * The layout plugin manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Constructs an EntityReferenceLayoutFormatter instance.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LoggerChannelFactoryInterface $logger_factory,
    EntityDisplayRepositoryInterface $entity_display_repository,
    LayoutPluginManager $layout_plugin_manager) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_display_repository);
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = $this->buildLayoutTree($items, $langcode);
    $elements = [];
    foreach ($build as $element) {
      $elements[] = $element;
    }
    return $elements;
  }

  /**
   * Builds the structure for the entire layout.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The items to render.
   * @param string $langcode
   *   The current language code.
   *
   * @return array
   *   A render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @see EntityReferenceRevisionsEntityFormatter::viewElements()
   */
  public function buildLayoutTree(FieldItemListInterface $items, $langcode) {
    $build = [];
    $containerUUID = FALSE;
    /** @var \Drupal\paragraphs\Entity\Paragraph $entity */
    $entities_to_view = $this->getEntitiesToView($items, $langcode);
    while ($entity = array_shift($entities_to_view)) {
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')->error(
          $this->t('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.',
            ['@entity_type' => $entity->getEntityTypeId(), '@entity_id' => $entity->id()])
        );
        return $build;
      }
      if ($entity->getBehaviorSetting('layout_paragraphs', 'layout', '')) {
        $containerUUID = $entity->uuid();
        $build[$containerUUID] = $this->buildLayoutContainer($entity, $entities_to_view);
      }
      else {
        $build[$entity->uuid()] = $this->buildEntityView($entity);
      }
      // Remove the item if it is disabled.
      if ('_disabled' == $entity->getBehaviorSetting('layout_paragraphs', 'region', '')) {
        unset($build[$entity->uuid()]);
      }
      $depth = 0;
    }
    return $build;
  }

  /**
   * Renders a paragraph entity.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The paragraph entity to render.
   *
   * @return array
   *   Returns a render array.
   */
  public function buildEntityView(ParagraphInterface $entity) {
    $view_mode = $this->getSetting('view_mode');
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    $render_array = $view_builder->view($entity, $view_mode, $entity->language()->getId());
    $render_array['#access'] = $this->checkAccess($entity);
    return $render_array;
  }

  /**
   * Builds the structure for a single layout paragraph item.
   *
   * Also adds elements for the layout instance and regions.
   * Regions will be populated with paragraphs further down the line,
   * then rendered in the layout.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $layout_entity
   *   The layout paragraph to render.
   * @param array $entities
   *   The array of remaining entities to process,
   *   adding to layout entity where applicable.
   *
   * @return array
   *   Returns a build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function buildLayoutContainer(ParagraphInterface $layout_entity, array &$entities) {
    /** @var Drupal\layout_paragraphs\Plugin\Field\FieldType\EntityReferenceLayoutRevisioned $referringItem */
    $layout = $layout_entity->getBehaviorSetting('layout_paragraphs', 'layout', '');
    $config = $layout_entity->getBehaviorSetting('layout_paragraphs', 'config', []);
    $layout_uuid = $layout_entity->uuid();
    if (!$this->layoutPluginManager->getDefinition($layout, FALSE)) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage($this->t('Layout `%layout_id` is unknown.', ['%layout_id' => $layout]), 'warning');
      return [];
    }
    $layout_instance = $this->layoutPluginManager->createInstance($layout, $config);
    $build = $this->buildEntityView($layout_entity);
    $build['regions'] = [];
    foreach ($entities as $index => $entity) {
      $parent_uuid = $entity->getBehaviorSetting('layout_paragraphs', 'parent_uuid', '');
      $region = $entity->getBehaviorSetting('layout_paragraphs', 'region', '');
      if ($parent_uuid == $layout_uuid && !empty($region)) {
        unset($entities[$index]);
        if ($entity->getBehaviorSetting('layout_paragraphs', 'layout', '')) {
          $build['regions'][$region][$entity->uuid()] = $this->buildLayoutContainer($entity, $entities);
        }
        else {
          $build['regions'][$region][$entity->uuid()] = $this->buildEntityView($entity);
        }
      }
    }
    $build['regions'] = ['#weight' => 1000] + $layout_instance->build($build['regions']);
    return $build;
  }

  /**
   * Returns the referenced entities for display.
   *
   * This implementation removes access checks so we can correctly
   * prevent rendering the children of unpublished layout sections
   * further down the line.
   *
   * See \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase::getEntitiesToView().
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = [];

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
        }
        // Add the referring item, in case the formatter needs it.
        $entity->_referringItem = $items[$delta];
        $entities[$delta] = $entity;
      }
    }

    return $entities;
  }

}
