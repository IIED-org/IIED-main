<?php

namespace Drupal\layout_paragraphs\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\field_group\FormatterHelper;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsStateResetCommand;
use Drupal\layout_paragraphs\Ajax\LayoutParagraphsInsertCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\inline_entity_form\WidgetSubmit;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Entity Reference with Layout field widget.
 *
 * @FieldWidget(
 *   id = "layout_paragraphs",
 *   label = @Translation("Layout Paragraphs"),
 *   description = @Translation("Layout builder for paragraphs."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   },
 * )
 */
class LayoutParagraphsWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Entity Type Manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Layouts Manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The entity that contains this field.
   *
   * @var \Drupal\Core\Entity\Entity
   */
  protected $host;

  /**
   * The name of the field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The Html Id of the wrapper element.
   *
   * @var string
   */
  protected $wrapperId;

  /**
   * The Html Id of the item form wrapper element.
   *
   * @var string
   */
  protected $itemFormWrapperId;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Indicates whether the current widget instance is in translation.
   *
   * @var bool
   */
  protected $isTranslating;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Core renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Core entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Layout\LayoutPluginManager $layout_plugin_manager
   *   Core layout plugin manager service.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Core language manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    Renderer $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    LayoutPluginManager $layout_plugin_manager,
    PluginFormFactoryInterface $plugin_form_manager,
    LanguageManager $language_manager,
    AccountProxyInterface $current_user,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->pluginFormFactory = $plugin_form_manager;
    $this->fieldName = $this->fieldDefinition->getName();
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->config = $config_factory;
    $this->moduleHandler = $module_handler;
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
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin_form.factory'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('entity_display.repository'),
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $parents = $form['#parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    if (isset($widget_state['items'][intval($delta)])) {
      $widget_state_item = $widget_state['items'][intval($delta)];
    }
    else {
      return [];
    }

    if (!isset($widget_state_item['entity'])) {
      return [];
    }

    if (isset($widget_state_item['is_new'])) {
      return [];
    }

    /** @var \Drupal\paragraphs\ParagraphInterface $entity */
    $entity = $widget_state_item['entity'];
    $layout_settings = $this->getLayoutSettings($entity);
    $layout = $layout_settings['layout'] ?? '';
    $config = $layout_settings['config'] ?? [];

    // These fields are manipulated via JS and interacting with the DOM.
    // We have to check the submitted form for their values.
    $region = $this->extractInput($form, $form_state, $delta, 'region', $layout_settings['region']);
    $this->setLayoutSetting($widget_state['items'][$delta]['entity'], 'region', $region);
    $parent_uuid = $this->extractInput($form, $form_state, $delta, 'parent_uuid', $layout_settings['parent_uuid']);
    $this->setLayoutSetting($widget_state['items'][$delta]['entity'], 'parent_uuid', $parent_uuid);
    $weight = $this->extractInput($form, $form_state, $delta, '_weight', $widget_state_item['weight']);

    $layout_instance = $layout
      ? $this->layoutPluginManager->createInstance($layout, $config)
      : FALSE;

    // Build the preview and render it in the form.
    $preview = [];
    if (isset($entity)) {
      $preview_view_mode = $this->getSetting('preview_view_mode');
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $preview = $view_builder->view($entity, $preview_view_mode, $entity->language()->getId());
      $preview['#cache']['max-age'] = 0;
      $preview['#attributes']['class'][] = Html::cleanCssIdentifier($entity->uuid() . '-preview');
    }

    $show_paragraphs_labels = $this->config->get('layout_paragraphs.settings')->get('show_paragraph_labels');
    $show_layout_labels = $this->config->get('layout_paragraphs.settings')->get('show_layout_labels');

    $element = [
      '#widget_item' => TRUE,
      '#type' => 'container',
      '#delta' => $delta,
      '#entity' => $entity,
      '#layout' => $layout,
      '#region' => $region,
      '#parent_uuid' => $parent_uuid,
      '#weight' => $weight,
      '#attributes' => [
        'class' => [
          'layout-paragraphs-item',
          'paragraph-' . $entity->uuid(),
        ],
        'id' => [
          $this->wrapperId . '--item-' . $delta,
        ],
      ],
      '_weight' => [
        '#type' => 'hidden',
        '#default_value' => $weight,
        '#weight' => -1000,
        '#attributes' => [
          'class' => ['layout-paragraphs-weight'],
        ],
      ],
      'preview' => $preview,
      'region' => [
        '#type' => 'hidden',
        '#attributes' => [
          'class' => ['layout-paragraphs-region'],
        ],
        '#default_value' => $region,
      ],
      // Used by DOM to set parent uuids for nested items.
      'uuid' => [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['layout-paragraphs-uuid']],
        '#value' => $entity->uuid(),
        // Must be at top for JS to work correctly.
        '#weight' => -999,
      ],
      'parent_uuid' => [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['layout-paragraphs-parent-uuid']],
        '#default_value' => $parent_uuid,
      ],
      'entity' => [
        '#type' => 'value',
        '#value' => $entity,
      ],
      // Edit and remove button.
      'actions' => [
        '#type' => 'container',
        '#weight' => -1000,
        '#attributes' => ['class' => ['layout-paragraphs-actions']],
        'edit' => [
          '#type' => 'submit',
          '#name' => 'edit_' . $this->wrapperId . '_' . $delta,
          '#value' => $this->t('Edit'),
          '#attributes' => ['class' => ['layout-paragraphs-edit']],
          '#limit_validation_errors' => [],
          '#submit' => [[$this, 'editItemSubmit']],
          '#delta' => $delta,
          '#ajax' => [
            'callback' => [$this, 'editItemAjax'],
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
        ],
        'remove' => [
          '#type' => 'submit',
          '#name' => 'remove_' . $this->wrapperId . '_' . $delta,
          '#value' => $this->t('Remove'),
          '#attributes' => ['class' => ['layout-paragraphs-remove']],
          '#limit_validation_errors' => [],
          '#submit' => [[$this, 'removeItemSubmit']],
          '#delta' => $delta,
          '#ajax' => [
            'callback' => [$this, 'removeItemAjax'],
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
        ],
      ],
      'label' => $show_paragraphs_labels ? [
        '#type' => 'label',
        '#title' => $entity->getParagraphType()->label,
        '#attributes' => ['class' => ['paragraph-type--label']],
      ] : [],
    ];

    // Nested elements for regions.
    if ($layout_instance) {
      $element['#layout_instance'] = $layout_instance;
      $element['#attributes']['class'][] = 'layout-paragraphs-layout';
      if ($show_layout_labels) {
        $label = $layout_instance->getPluginDefinition() ? $layout_instance->getPluginDefinition()
          ->getLabel()
          ->__toString() : [];
        $element['label'] = [
          '#type' => 'label',
          '#title' => $label,
          '#title_display' => $label,
          '#attributes' => ['class' => ['paragraph-layout--label']],
        ];
      }

      foreach ($layout_instance->getPluginDefinition()->getRegionNames() as $region_name) {
        $element['preview']['regions'][$region_name] = [
          '#attributes' => [
            'class' => [
              'layout-paragraphs-layout-region',
              'layout-paragraphs-layout-region--' . $region_name,
              $entity->uuid() . '-layout-region--' . $region_name,
            ],
            'id' => [
              $this->wrapperId . '--item-' . $delta . '-' . $region_name,
            ],
          ],
        ];
      }
    }

    // New items are rendered in layout but hidden.
    // This way we can track their weights, region names, etc.
    if (!empty($widget_state['items'][$delta]['is_new'])) {
      $element['#is_new'] = TRUE;
    }
    else {
      $element['#is_new'] = FALSE;
    }

    return $element;
  }

  /**
   * Returns the sorted allowed types for a entity reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   (optional) The field definition forwhich the allowed types should be
   *   returned, defaults to the current field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name with the
   *   following properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   */
  public function getAllowedTypes(FieldDefinitionInterface $field_definition = NULL) {

    $return_bundles = [];
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $handler = $selection_manager->getSelectionHandler($field_definition ?: $this->fieldDefinition);
    if ($handler instanceof ParagraphSelection) {
      $return_bundles = $handler->getSortedAllowedTypes();
    }
    // Support for other reference types.
    else {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($field_definition ? $field_definition->getSetting('target_type') : $this->fieldDefinition->getSetting('target_type'));
      $weight = 0;
      foreach ($bundles as $machine_name => $bundle) {
        if (empty($this->getSelectionHandlerSetting('target_bundles'))
          || in_array($machine_name, $this->getSelectionHandlerSetting('target_bundles'))) {

          $return_bundles[$machine_name] = [
            'label' => $bundle['label'],
            'weight' => $weight,
          ];

          $weight++;
        }
      }
    }

    return $return_bundles;
  }

  /**
   * Builds the main widget form array container/wrapper.
   *
   * Form elements for individual items are built by formElement().
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {

    $parents = $form['#parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    $this->wrapperId = trim(Html::getId(implode('-', $parents) . '-' . $this->fieldName . '-wrapper'), '-');
    $this->itemFormWrapperId = trim(Html::getId(implode('-', $parents) . '-' . $this->fieldName . '-form'), '-');

    $target_bundles = array_keys($this->getAllowedTypes());
    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    /** @var \Drupal\Core\Entity\ContentEntityInterface $host */
    $host = $items->getEntity();
    // Detect if we are translating.
    $this->initIsTranslating($form_state, $host);

    // Save items to widget state when the form first loads.
    if (!isset($widget_state['items'])) {
      $widget_state['items'] = [];
      $widget_state['open_form'] = FALSE;
      $widget_state['remove_item'] = FALSE;
      /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
      foreach ($items as $delta => $item) {
        if ($paragraph = $item->entity) {
          if ($item->entity instanceof ParagraphInterface) {
            $langcode = $form_state->get('langcode');
            if (!$this->isTranslating) {
              // Set the langcode if we are not translating.
              $langcode_key = $item->entity->getEntityType()->getKey('langcode');
              if ($item->entity->get($langcode_key)->value != $langcode) {
                // If a translation in the given language already exists,
                // switch to that. If there is none yet, update the language.
                if ($item->entity->hasTranslation($langcode)) {
                  $item->entity = $item->entity->getTranslation($langcode);
                }
                else {
                  $item->entity->set($langcode_key, $langcode);
                }
              }
            }
            else {
              // Add translation if missing for the target language.
              if (!$item->entity->hasTranslation($langcode)) {
                // Get the selected translation of the paragraph entity.
                $entity_langcode = $item->entity->language()->getId();
                $source = $form_state->get(['content_translation', 'source']);
                $source_langcode = $source ? $source->getId() : $entity_langcode;
                // Make sure the source language version is used if available.
                // Fetching the translation without this check could lead valid
                // scenario to have no paragraphs items in the source version of
                // to an exception.
                if ($item->entity->hasTranslation($source_langcode)) {
                  $entity = $item->entity->getTranslation($source_langcode);
                }
                // The paragraphs entity has no content translation source field
                // if no paragraph entity field is translatable,
                // even if the host is.
                if ($item->entity->hasField('content_translation_source')) {
                  // Initialise the translation with source language values.
                  $item->entity->addTranslation($langcode, $entity->toArray());
                  $translation = $item->entity->getTranslation($langcode);
                  $manager = \Drupal::service('content_translation.manager');
                  $manager->getTranslationMetadata($translation)
                    ->setSource($item->entity->language()->getId());
                }
              }
              // If any paragraphs type is translatable do not switch.
              if ($item->entity->hasField('content_translation_source')) {
                // Switch the paragraph to the translation.
                $item->entity = $item->entity->getTranslation($langcode);
              }
            }
          }

          $widget_state['items'][$delta] = [
            'entity' => $paragraph,
            'weight' => $delta,
          ];
        }
      }
    }
    // Handle asymmetric translation if field is translatable
    // by duplicating items for enabled languages.
    if ($items->getFieldDefinition()->isTranslatable()) {
      $langcode = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();

      foreach ($widget_state['items'] as $delta => $item) {
        if (empty($item['entity']) || $item['entity']->get('langcode')->value == $langcode) {
          continue;
        }
        /* @var \Drupal\Core\Entity\EntityInterface $duplicate */
        $duplicate = $item['entity']->createDuplicate();
        $duplicate->set('langcode', $langcode);
        $widget_state['items'][$delta]['entity'] = $duplicate;
      }
    }
    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);

    $elements = [
      '#field_name' => $this->fieldName,
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $title,
      '#description' => $description,
      '#attributes' => ['class' => ['layout-paragraphs-field']],
      '#type' => 'fieldset',
      '#parents' => $form['#parents'],
      '#id' => $this->wrapperId,
    ];
    for ($delta = 0; $delta < $widget_state['items_count']; $delta++) {
      $elements[$delta] = $this->formSingleElement($items, $delta, [], $form, $form_state);
    }
    $elements['#after_build'][] = [$this, 'buildLayouts'];

    // Add logic for new elements Add, if not in a translation context.
    if ($this->allowReferenceChanges()) {
      // Button to add new section and other paragraphs.
      $elements['add_more'] = [
        'actions' => [
          '#attributes' => ['class' => ['js-hide']],
          '#type' => 'container',
        ],
      ];
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
      $options = [];
      $types = [
        'layout' => [],
        'content' => [],
      ];
      $bundle_ids = $target_bundles;
      $target_type = $items->getSetting('target_type');
      $definition = $this->entityTypeManager->getDefinition($target_type);
      $storage = $this->entityTypeManager->getStorage($definition->getBundleEntityType());
      foreach ($bundle_ids as $bundle_id) {
        $type = $storage->load($bundle_id);
        $has_layout = count($this->getAvailableLayoutsByType($type)) > 0;

        $path = '';
        // Get the icon and pass to Javascript.
        if (method_exists($type, 'getIconUrl')) {
          $path = $type->getIconUrl();
        }
        $options[$bundle_id] = $bundle_info[$bundle_id]['label'];
        $types[($has_layout ? 'layout' : 'content')][] = [
          'id' => $bundle_id,
          'name' => $bundle_info[$bundle_id]['label'],
          'image' => $path,
          'title' => $this->t('Create new @name', ['@name' => $bundle_info[$bundle_id]['label']]),
        ];
      }
      $elements['add_more']['actions']['type'] = [
        '#title' => $this->t('Choose type'),
        '#type' => 'select',
        '#options' => $options,
        '#attributes' => ['class' => ['layout-paragraphs-item-type']],
      ];
      $elements['add_more']['actions']['item'] = [
        '#type' => 'submit',
        '#host' => $items->getEntity(),
        '#value' => $this->t('Create New'),
        '#submit' => [[$this, 'newItemSubmit']],
        '#element_validate' => [[$this, 'newItemValidate']],
        '#limit_validation_errors' => [array_merge($parents, [
          $this->fieldName,
          'add_more',
        ]),
        ],
        '#attributes' => ['class' => ['layout-paragraphs-add-item']],
        '#ajax' => [
          'callback' => [$this, 'editItemAjax'],
        ],
        '#name' => trim(implode('_', $parents) . '_' . $this->fieldName . '_add_item', '_'),
        '#element_parents' => $parents,
      ];
      // When adding a new element, the widget needs a way to track
      // (a) where in the DOM the new element should be added, and
      // (b) which method to use the insert the new element
      // (i.e. before, after, append).
      $elements['add_more']['actions']['dom_id'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['dom-id']],
      ];
      $elements['add_more']['actions']['insert_method'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['insert-method']],
      ];
      // Template for javascript behaviors.
      $elements['add_more']['menu'] = [
        '#type' => 'inline_template',
        '#template' => '
        <div class="layout-paragraphs-add-more-menu hidden">
          <h4 class="visually-hidden">Add Item</h4>
          <div class="layout-paragraphs-add-more-menu__search hidden">
            <input type="text" placeholder="{{ search_text }}" />
          </div>
          <div class="layout-paragraphs-add-more-menu__group">
            {% if types.layout %}
            <div class="layout-paragraphs-add-more-menu__group--layout">
            {% endif %}
            {% for type in types.layout %}
              <div class="layout-paragraphs-add-more-menu__item paragraph-type-{{type.id}} layout-paragraph">
                <a data-type="{{ type.id }}" href="#{{ type.id }}" title="{{ type.title }}">
                {% if type.image %}
                <img src="{{ type.image }}" alt ="" />
                {% endif %}
                <div>{{ type.name }}</div>
                </a>
              </div>
            {% endfor %}
            {% if types.layout %}
            </div>
            {% endif %}
            {% if types.content %}
            <div class="layout-paragraphs-add-more-menu__group--content">
            {% endif %}
            {% for type in types.content %}
              <div class="layout-paragraphs-add-more-menu__item paragraph-type-{{type.id}}">
                <a data-type="{{ type.id }}" href="#{{ type.id }}" title="{{ type.title }}">
                {% if type.image %}
                <img src="{{ type.image }}" alt ="" />
                {% endif %}
                <div>{{ type.name }}</div>
                </a>
              </div>
            {% endfor %}
            {% if types.content %}
            </div>
            {% endif %}
          </div>
        </div>',
        '#context' => [
          'types' => $types,
          'search_text' => $this->t('Search'),
        ],
      ];
    }
    else {
      // Add the #isTranslating attribute, if in a translation context.
      $elements['is_translating_warning'] = [
        '#type' => "html_tag",
        '#tag' => 'div',
        '#value' => t("This is Translation Context (editing a version not in the original language). <b>No new Layout Sections and Paragraphs can be added</b>."),
        '#weight' => -1100,
        '#attributes' => [
          'class' => ['is_translating_warning'],
        ],
      ];
      $elements['add_more'] = [
        'actions' => [
          '#isTranslating' => TRUE,
        ],
      ];
    }

    // Add the paragraph edit form if editing.
    if ($widget_state['open_form'] !== FALSE) {
      $this->entityForm($elements, $form_state, $form);
    }

    // Add remove confirmation form if we're removing.
    if ($widget_state['remove_item'] !== FALSE) {
      $this->removeForm($elements, $form_state, $form);
    }

    // Container for disabled / orphaned items.
    $elements['disabled'] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['layout-paragraphs-disabled-items']],
      '#weight' => 999,
      '#title' => $this->t('Disabled Items'),
      'description' => [
        '#markup' => '<div class="layout-paragraphs-disabled-items__description">' . $this->t('Drop items here that you want to keep disabled / hidden, without removing them permanently.') . '</div>',
      ],
      'items' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'layout-paragraphs-disabled-items__items',
          ],
        ],
      ],
    ];

    // Pass widget instance settings to JS.
    $elements['#attached']['drupalSettings']['layoutParagraphsWidgets'][$this->wrapperId] = [
      'wrapperId' => $this->wrapperId,
      'maxDepth' => $this->getSetting('nesting_depth'),
      'requireLayouts' => $this->getSetting('require_layouts'),
      'isTranslating' => $elements["add_more"]["actions"]["#isTranslating"] ?? NULL,
      'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      'itemsCount' => $this->activeItemsCount($widget_state['items']),
    ];
    // Add layout_paragraphs_widget library.
    $elements['#attached']['library'][] = 'layout_paragraphs/layout_paragraphs_widget';
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $elements = parent::form($items, $form, $form_state, $get_delta);
    // Signal to content_translation that this field should be treated as
    // multilingual and not be hidden, see
    // \Drupal\content_translation\ContentTranslationHandler::entityFormSharedElements().
    $elements['#multilingual'] = TRUE;
    return $elements;
  }

  /**
   * Restructures $elements array into layout.
   *
   * @param array $elements
   *   The widget elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The restructured with layouts.
   */
  public function buildLayouts(array $elements, FormStateInterface $form_state) {
    $tree = [
      '#parents' => [],
    ];
    $paragraph_elements = [];
    $elements['#items'] = [];
    foreach (Element::children($elements) as $index) {
      $element = $elements[$index];
      if (!empty($element['#widget_item'])) {
        $paragraph_elements[$element['#entity']->uuid()] = $element;
        // Maintain a hidden flat list of elements to easily locate items.
        $elements['#items'][$element['#entity']->uuid()] = $element;
        unset($elements[$index]);
      }
    }
    // Move any orphaned items to the disabled bin.
    foreach ($paragraph_elements as $index => $element) {
      $paragraph = $element['#entity'];
      $layout_settings = $this->getLayoutSettings($paragraph);
      $parent_uuid = $layout_settings['parent_uuid'];
      if ($parent_uuid && !isset($paragraph_elements[$parent_uuid])) {
        $paragraph_elements[$index]['#region'] = '_disabled';
      }
    }
    // Sort items by weight to make sure we're processing the correct order.
    uasort($paragraph_elements, function ($a, $b) {
      if ($a['#weight'] == $b['#weight']) {
        return 0;
      }
      return $a['#weight'] < $b['#weight'] ? -1 : 1;
    });
    while ($element = array_shift($paragraph_elements)) {
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $element['#entity'];
      if ($element['#layout']) {
        $tree[$entity->uuid()] = $this->buildLayout($element, $paragraph_elements, $elements['disabled']['items']);
      }
      else {
        $tree[$entity->uuid()] = $element;
      }
      // Move disabled items to disabled region.
      if ($element['#region'] == '_disabled') {
        $elements['disabled']['items'][$entity->uuid()] = $tree[$entity->uuid()];
        unset($tree[$entity->uuid()]);
      }
    }
    $elements['active_items'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['active-items'],
        'id' => $this->wrapperId . "--active-items",
      ],
      'items' => $tree,
      '#parents' => [],
    ];
    return $elements;
  }

  /**
   * Builds a single layout element.
   */
  public function buildLayout($layout_element, &$elements, &$disabled_items) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $layout_element['#entity'];
    $uuid = $entity->uuid();
    $layout_element['preview']['regions'] = ['#weight' => 100] + $layout_element['#layout_instance']->build($layout_element['preview']['regions']);
    $layout_element['preview']['regions']['#parents'] = $layout_element['#parents'];
    foreach ($elements as $index => $element) {
      if (!empty($element['#region']) && $element['#parent_uuid'] == $uuid) {
        /* @var \Drupal\Core\Entity\EntityInterface $child_entity */
        $child_entity = $element['#entity'];
        $child_uuid = $child_entity->uuid();
        $region = $element['#region'];
        // Recursive processing for layouts within layouts.
        if ($element['#layout']) {
          $sub_element = $this->buildLayout($element, $elements, $disabled_items);
        }
        else {
          $sub_element = $element;
        }
        unset($elements[$index]);
        if (isset($layout_element['preview']['regions'][$region])) {
          $layout_element['preview']['regions'][$region][$child_uuid] = $sub_element;
        }
        else {
          $disabled_items[$child_uuid] = $sub_element;
        }
      }
    }
    return $layout_element;

  }

  /**
   * Returns a flat array of layout labels keyed by layout ids.
   *
   * @param array $layout_groups
   *   Nested array of layout groups.
   *
   * @return array
   *   Flat array of layout labels.
   */
  private function layoutLabels(array $layout_groups) {
    $layouts = $this->layoutPluginManager->getSortedDefinitions();
    $layout_info = [];
    foreach ($layout_groups as $group) {
      foreach ($group as $layout_id => $layout_name) {
        $layout_info[$layout_id] = $layouts[$layout_id]->getLabel();
      }
    }
    return $layout_info;
  }

  /**
   * Builds an entity form for a paragraph item.
   *
   * Add form components in this rendering order:
   * 1. Layout selection, if this is a layout paragraph.
   * 2. The entity's fields from the form display.
   * 3. The layout plugin config form, if exists.
   * 4. The paragraph behaviors plugins form, if any exist.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The entity form element.
   */
  public function entityForm(array &$element, FormStateInterface $form_state, array &$form) {

    $parents = $element['#parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $delta = $widget_state['open_form'];

    /** @var \Drupal\paragraphs\Entity\Paragraph $entity */
    $entity = $widget_state['items'][$delta]['entity'];
    // Set correct default language for the entity.
    if ($this->isTranslating && $language = $form_state->get('langcode')) {
      $entity = $entity->getTranslation($language);
    }
    $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
    $bundle_label = $entity->type->entity->label();
    $element['entity_form'] = [
      '#entity' => $entity,
      '#prefix' => '<div class="layout-paragraphs-form entity-type-' . $entity->bundle() . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#parents' => array_merge($parents, [
        $this->fieldName,
        'entity_form',
        $delta,
      ]),
      '#weight' => 1000,
      '#delta' => $delta,
      '#display' => $display,
      '#attributes' => [
        'data-dialog-title' => [
          $entity->id() ? $this->t('Edit @type', ['@type' => $bundle_label]) : $this->t('Create new @type', ['@type' => $bundle_label]),
        ],
      ],
    ];

    // Support for Field Group module based on Paragraphs module.
    // @todo Remove as part of https://www.drupal.org/node/2640056
    if ($this->moduleHandler->moduleExists('field_group')) {
      $context = [
        'entity_type' => $entity->getEntityTypeId(),
        'bundle' => $entity->bundle(),
        'entity' => $entity,
        'context' => 'form',
        'display_context' => 'form',
        'mode' => $display->getMode(),
      ];
      field_group_attach_groups($element['entity_form'], $context);
      if (method_exists(FormatterHelper::class, 'formProcess')) {
        $element['entity_form']['#process'][] = [FormatterHelper::class, 'formProcess'];
      }
      elseif (function_exists('field_group_form_pre_render')) {
        $element['entity_form']['#pre_render'][] = 'field_group_form_pre_render';
      }
      elseif (function_exists('field_group_form_process')) {
        $element['entity_form']['#process'][] = 'field_group_form_process';
      }
    }

    $display->buildForm($entity, $element['entity_form'], $form_state);

    // Add layout selection form if "paragraphs layout" behavior is enabled.
    if ($this->isLayoutParagraph($entity)) {
      $available_layouts = $this->getAvailableLayouts($entity);
      $layout_settings = $this->getLayoutSettings($entity);
      $layout = !empty($layout_settings['layout']) ? $layout_settings['layout'] : key($available_layouts);
      $layout_plugin_config = $layout_settings['config'] ?? [];

      $element['entity_form']['layout_selection'] = [
        '#type' => 'container',
        '#weight' => -1000,
        'layout' => [
          '#type' => 'radios',
          '#title' => $this->t('Select a layout:'),
          '#options' => $available_layouts,
          '#default_value' => $layout,
          '#attributes' => [
            'class' => ['layout-paragraphs-layout-select'],
          ],
          '#required' => TRUE,
          '#after_build' => [[$this, 'buildLayoutRadios']],
        ],
        'update' => [
          '#type' => 'button',
          '#value' => $this->t('Update'),
          '#name' => 'update_layout',
          '#delta' => $delta,
          '#limit_validation_errors' => [
            array_merge($parents, [
              $this->fieldName,
              'entity_form',
              $delta,
              'layout_selection',
            ]),
          ],
          '#attributes' => [
            'class' => ['js-hide'],
          ],
          '#element_parents' => $parents,
        ],
      ];

      // Switching layouts should change the layout plugin options form
      // with Ajax for users with adequate permissions.
      if ($this->currentUser->hasPermission('edit layout paragraphs plugin config')) {
        $element['entity_form']['layout_selection']['layout']['#ajax'] = [
          'event' => 'change',
          'callback' => [$this, 'buildLayoutConfigurationFormAjax'],
          'trigger_as' => ['name' => 'update_layout'],
          'wrapper' => 'layout-config',
          'progress' => 'none',
        ];
        $element['entity_form']['layout_selection']['update']['#ajax'] = [
          'callback' => [$this, 'buildLayoutConfigurationFormAjax'],
          'wrapper' => 'layout-config',
          'progress' => 'none',
        ];
      }
      $element['entity_form']['layout_plugin_form'] = [
        '#prefix' => '<div id="layout-config">',
        '#suffix' => '</div>',
        '#access' => $this->currentUser->hasPermission('edit layout paragraphs plugin config'),
      ];

      // Add the layout configuration form if applicable.
      $layout_select_parents = array_merge($parents, [
        $this->fieldName,
        'entity_form',
        $delta,
        'layout_selection',
        'layout',
      ]);
      $updated_layout = $form_state->getValue($layout_select_parents) ?? $layout;

      if (!empty($updated_layout)) {
        try {
          $updated_layout_instance = $this->layoutPluginManager->createInstance($updated_layout, $layout_plugin_config);
          // If the user selects a new layout,
          // we provide a way for them to choose
          // what to do with items from regions
          // that no longer exist.
          if ($layout && $updated_layout != $layout) {
            $move_items = [];

            $original_layout = $this->layoutPluginManager->createInstance($layout);
            $original_definition = $original_layout->getPluginDefinition();
            $original_regions = $original_definition->getRegions();

            $updated_layout_definition = $updated_layout_instance->getPluginDefinition();
            $updated_regions = $updated_layout_definition->getRegions();
            $updated_regions_options = [];
            foreach ($updated_regions as $region_name => $region) {
              $updated_regions_options[$region_name] = $region['label'];
            }
            $updated_regions_options['_disabled'] = $this->t('Disabled');
            foreach ($original_regions as $region_name => $region) {
              if (!isset($updated_regions[$region_name]) && $this->hasChildren($entity, $widget_state['items'], $region_name)) {
                $move_items[$region_name] = [
                  '#type' => 'select',
                  '#wrapper_attributes' => ['class' => ['container-inline']],
                  '#title' => $this->t('Move items from the "@region" region to', ['@region' => $region['label']]),
                  '#options' => $updated_regions_options,
                ];
              }
            }
            if (count($move_items)) {
              $element['entity_form']['layout_selection']['move_items'] = [
                '#type' => 'fieldset',
                '#title' => $this->t('Move orphaned items'),
                '#description' => $this->t('The layout you selected has different regions than the previous one.'),
                'items' => $move_items,
              ];
            }
          }
          if ($layout_plugin = $this->getLayoutPluginForm($updated_layout_instance)) {
            $element['entity_form']['layout_plugin_form'] += [
              '#type' => 'details',
              '#title' => $this->t('Layout Configuration'),
              '#weight' => 999,
            ];
            $element['entity_form']['layout_plugin_form'] += $layout_plugin->buildConfigurationForm([], $form_state);
          }
        }
        catch (\Exception $e) {
          watchdog_exception('Layout Paragraphs, updating_layout', $e);
        }
      }
    }

    // Add behavior forms if applicable.
    $paragraphs_type = $entity->getParagraphType();
    // @todo: Check translation functionality.
    if ($paragraphs_type &&
      $this->currentUser->hasPermission('edit behavior plugin settings') &&
      (!$this->isTranslating || !$entity->isDefaultTranslationAffectedOnly()) &&
      $behavior_plugins = $paragraphs_type->getEnabledBehaviorPlugins()) {
      $has_behavior_plugin_form = FALSE;
      $element['entity_form']['behavior_plugins'] = [
        '#type' => 'details',
        '#title' => $this->t('Behaviors'),
        '#element_validate' => [[$this, 'validateBehaviors']],
        '#entity' => $entity,
        '#weight' => -99,
      ];
      foreach ($behavior_plugins as $plugin_id => $plugin) {
        $element['entity_form']['behavior_plugins'][$plugin_id] = ['#type' => 'container'];
        $subform_state = SubformState::createForSubform($element['entity_form']['behavior_plugins'][$plugin_id], $form, $form_state);
        $plugin_form = $plugin->buildBehaviorForm($entity, $element['entity_form']['behavior_plugins'][$plugin_id], $subform_state);
        if (!empty(Element::children($plugin_form))) {
          $element['entity_form']['behavior_plugins'][$plugin_id] = $plugin_form;
          $has_behavior_plugin_form = TRUE;
        }
      }
      if (!$has_behavior_plugin_form) {
        unset($element['entity_form']['behavior_plugins']);
      }
    }

    // Add save, cancel, etc.
    $element['entity_form'] += [
      'actions' => [
        '#weight' => 1000,
        '#type' => 'actions',
        '#attributes' => ['class' => ['layout-paragraphs-item-form-actions']],
        'submit' => [
          '#type' => 'submit',
          '#name' => 'save',
          '#value' => $this->t('Save'),
          '#delta' => $delta,
          '#uuid' => $entity->uuid(),
          '#limit_validation_errors' => [
            array_merge($parents, [
              $this->fieldName,
              'entity_form',
              $delta,
            ]),
          ],
          '#submit' => [
            [$this, 'saveItemSubmit'],
          ],
          '#ajax' => [
            'callback' => [$this, 'saveItemAjax'],
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
        ],
        'cancel' => [
          '#type' => 'submit',
          '#name' => 'cancel',
          '#value' => $this->t('Cancel'),
          '#limit_validation_errors' => [],
          '#delta' => $delta,
          '#submit' => [
            [$this, 'cancelItemSubmit'],
          ],
          '#attributes' => [
            'class' => ['layout-paragraphs-cancel', 'button--danger'],
          ],
          '#ajax' => [
            'callback' => [$this, 'closeDialogAjax'],
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
        ],
      ],
    ];

    $hide_untranslatable_fields = $entity->isDefaultTranslationAffectedOnly();
    foreach (Element::children($element['entity_form']) as $field) {
      if ($entity->hasField($field)) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        $field_definition = $entity->get($field)->getFieldDefinition();
        $translatable = $entity->{$field}->getFieldDefinition()->isTranslatable();

        // Do a check if we have to add a class to the form element. We need
        // those classes (paragraphs-content and paragraphs-behavior) to show
        // and hide elements, depending of the active perspective.
        // We need them to filter out entity reference revisions fields that
        // reference paragraphs, cause otherwise we have problems with showing
        // and hiding the right fields in nested paragraphs.
        $is_paragraph_field = FALSE;
        if ($field_definition->getType() == 'entity_reference_revisions') {
          // Check if we are referencing paragraphs.
          if ($field_definition->getSetting('target_type') == 'paragraph') {
            $is_paragraph_field = TRUE;
          }
        }

        if (!$translatable && $this->isTranslating && !$is_paragraph_field) {
          if ($hide_untranslatable_fields) {
            $element['entity_form'][$field]['#access'] = FALSE;
          }
          else {
            $element['entity_form'][$field]['widget']['#after_build'][] = [
              static::class,
              'addTranslatabilityClue',
            ];
          }
        }
      }
    }
    // Add compatibility for Inline Entity Form module.
    // See https://www.drupal.org/project/inline_entity_form/issues/3160809
    // and https://www.drupal.org/project/layout_paragraphs/issues/3160806
    $ief_widget_state = $form_state->get('inline_entity_form');
    if (!is_null($ief_widget_state)) {
      ElementSubmit::attach($element['entity_form'], $form_state);
      WidgetSubmit::attach($element['entity_form'], $form_state);
    }

    // Allow others modules to adjust the Layout Paragraph Element Dialog Form.
    $this->moduleHandler->alter('layout_paragraph_element_form', $element['entity_form'], $form_state, $form);

    return $element;
  }

  /**
   * Builds the remove item confirmation form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   * @param array $form
   *   The form array.
   */
  public function removeForm(array &$element, FormStateInterface $form_state, array &$form) {

    $parents = $element['#parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $delta = $widget_state['remove_item'];
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph_entity */
    $entity = $widget_state['items'][$delta]['entity'];

    $element['remove_form'] = [
      '#prefix' => '<div class="layout-paragraphs-form">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#entity' => $entity,
      '#attributes' => ['data-dialog-title' => [$this->t('Confirm removal')]],
      'message' => [
        '#type' => 'markup',
        '#markup' => $this->t('Are you sure you want to permanently remove this <b>@type?</b><br />This action cannot be undone.', ['@type' => $entity->type->entity->label()]),
      ],
      'actions' => [
        '#type' => 'actions',
        '#attributes' => ['class' => ['layout-paragraphs-item-form-actions']],
        'confirm' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#delta' => $delta,
          '#uuid' => $entity->uuid(),
          '#submit' => [[$this, 'removeItemConfirmSubmit']],
          '#ajax' => [
            'callback' => [$this, 'removeItemConfirmAjax'],
            'progress' => 'none',
          ],
          '#limit_validation_errors' => [
            array_merge($parents, [
              $this->fieldName,
              'remove_form',
            ]),
          ],
          '#element_parents' => $parents,
        ],
        'cancel' => [
          '#type' => 'submit',
          '#value' => $this->t('Cancel'),
          '#delta' => $delta,
          '#submit' => [[$this, 'removeItemCancelSubmit']],
          '#attributes' => [
            'class' => ['layout-paragraphs-cancel', 'button--danger'],
          ],
          '#ajax' => [
            'callback' => [$this, 'closeDialogAjax'],
            'progress' => 'none',
          ],
          '#element_parents' => $parents,
          '#limit_validation_errors' => [],
        ],
      ],
      '#delta' => $delta,
    ];
    if ($this->hasChildren($entity, $widget_state['items'])) {
      $element['remove_form']['nested_items'] = [
        '#type' => 'radios',
        '#title' => $this->t('This layout has nested items.'),
        '#options' => [
          'disable' => $this->t('Disable nested items'),
          'remove' => $this->t('Permanently remove nested items'),
        ],
        'disable' => [
          '#description' => $this->t('Nested items will be moved to the <b>Disabled Items</b> section and can be editied or restored.'),
        ],
        'remove' => [
          '#description' => $this->t('Nested items will be permanenty removed.'),
        ],
        '#default_value' => 'disable',
      ];
    }
  }

  /**
   * Add theme wrappers to layout selection radios.
   *
   * Theme function injects layout icons into radio buttons.
   */
  public function buildLayoutRadios($element) {
    foreach (Element::children($element) as $key) {
      $layout_name = $key;
      $definition = $this->layoutPluginManager->getDefinition($layout_name);
      $icon = $definition->getIcon(40, 60, 1, 0);
      $rendered_icon = $this->renderer->render($icon);
      $title = new FormattableMarkup('<span class="layout-select__item-icon">@icon</span><span class="layout-select__item-title">@title</span>', [
        '@title' => $element[$key]['#title'],
        '@icon' => $rendered_icon,
      ]);
      $element[$key]['#title'] = $title;
      $element[$key]['#wrapper_attributes']['class'][] = 'layout-select__item';
    }
    $element['#wrapper_attributes'] = ['class' => ['layout-select']];
    return $element;
  }

  /**
   * Extracts field value from form_state if it exists.
   */
  protected function extractInput(array $form, FormStateInterface $form_state, int $delta, $element_name, $default_value = '') {

    $input = $form_state->getUserInput();
    $parents = $form['#parents'];
    $key_exists = NULL;

    if (is_array($element_name)) {
      $element_path = array_merge($parents, [
        $this->fieldName,
        $delta,
      ],
        $element_name);
    }
    else {
      $element_path = array_merge($parents, [
        $this->fieldName,
        $delta,
        $element_name,
      ]);
    }

    $val = NestedArray::getValue($input, $element_path, $key_exists);
    if ($key_exists) {
      return Html::escape($val);
    }
    return $default_value;
  }

  protected function setUserInput(array $form, FormStateInterface &$form_state, int $delta, $element_name, $value) {
    $input = $form_state->getUserInput();
    $parents = $form['#parents'];

    if (is_array($element_name)) {
      $element_path = array_merge($parents, [
        $this->fieldName,
        $delta,
      ],
        $element_name);
    }
    else {
      $element_path = array_merge($parents, [
        $this->fieldName,
        $delta,
        $element_name,
      ]);
    }

    NestedArray::setValue($input, $element_path, $value);
    $form_state->setUserInput($input);
  }

  /**
   * Validate paragraph behavior form plugins.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   */
  public function validateBehaviors(array $element, FormStateInterface $form_state, array $form) {

    /** @var \Drupal\paragraphs\ParagraphInterface $entity */
    $entity = $element['#entity'];

    // Validate all enabled behavior plugins.
    $paragraphs_type = $entity->getParagraphType();
    if ($this->currentUser->hasPermission('edit behavior plugin settings')) {
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_values) {
        if (!empty($element[$plugin_id])) {
          $subform_state = SubformState::createForSubform($element[$plugin_id], $form_state->getCompleteForm(), $form_state);
          $plugin_values->validateBehaviorForm($entity, $element[$plugin_id], $subform_state);
        }
      }
    }
  }

  /**
   * New item validator - checks cardinality.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function newItemValidate(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Only check cardinality if the user clicked to add a new paragraph.
    if ($triggering_element['#array_parents'] == $element['#array_parents']) {
      $parents = $element['#element_parents'];
      $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
      $count = $this->activeItemsCount($widget_state['items']);
      $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

      if ($cardinality != -1 && $count >= $cardinality) {
        $form_state->setError($element, $this->t('You can only add @cardinality or fewer items.', ['@cardinality' => $cardinality]));
      }
    }
  }

  /**
   * Form submit handler - adds a new item and opens its edit form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function newItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    if (!empty($element['#bundle_id'])) {
      $bundle_id = $element['#bundle_id'];
    }
    else {
      $element_parents = $element['#parents'];
      array_splice($element_parents, -1, 1, 'type');
      $bundle_id = $form_state->getValue($element_parents);
    }

    try {
      $entity_type = $this->entityTypeManager->getDefinition('paragraph');
      $bundle_key = $entity_type->getKey('bundle');
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph_entity */
      $paragraph_entity = $this->entityTypeManager->getStorage('paragraph')
        ->create([
          $bundle_key => $bundle_id,
        ]);
      $paragraph_entity->setParentEntity($element['#host'], $this->fieldDefinition->getName());
      $widget_state['items'][] = [
        'entity' => $paragraph_entity,
        'is_new' => TRUE,
        'weight' => count($widget_state['items']),
      ];
      $widget_state['open_form'] = $widget_state['items_count'];
      $widget_state['items_count'] = count($widget_state['items']);

      static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
      $form_state->setRebuild();
    }
    catch (\Exception $e) {
      watchdog_exception('Layout Paragraphs, new Item Submit', $e);
    }
  }

  /**
   * Form submit handler - opens the edit form for an existing item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function editItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_state['open_form'] = $delta;

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - opens confirm removal form for an item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_state['remove_item'] = $delta;

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - removes/deletes an item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeItemConfirmSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $element_parents = $element['#parents'];
    $remove_form_parents = array_splice($element_parents, 0, -2);
    $nested_items_path = array_merge($remove_form_parents, ['nested_items']);
    $nested_items_value = $form_state->getValue($nested_items_path);
    $uuid = $element['#uuid'];
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    if ($nested_items_value == 'remove') {
      $this->removeChildren($widget_state['items'], $uuid);
    }
    unset($widget_state['items'][$delta]['entity']);
    $widget_state['remove_item'] = FALSE;
    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - cancels item removal and closes confirmation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeItemCancelSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];

    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    $widget_state['remove_item'] = FALSE;

    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - saves an item.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function saveItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];
    $element_array_parents = $element['#array_parents'];
    $item_array_parents = array_splice($element_array_parents, 0, -2);

    $item_form = NestedArray::getValue($form, $item_array_parents);
    /* @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $item_form['#display'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    // Remove is_new flag since we're saving the entity.
    unset($widget_state['items'][$delta]['is_new']);

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $widget_state['items'][$delta]['entity'];

    // Set correct default language for the entity.
    if ($this->isTranslating && $language = $form_state->get('langcode')) {
      $paragraph = $paragraph->getTranslation($language);
    }

    // Save field values to entity.
    $display->extractFormValues($paragraph, $item_form, $form_state);

    // Submit behavior forms.
    $paragraphs_type = $paragraph->getParagraphType();
    if ($this->currentUser->hasPermission('edit behavior plugin settings')) {
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_values) {
        $plugin_form = isset($item_form['behavior_plugins']) ? $item_form['behavior_plugins'][$plugin_id] : [];
        if (!empty($plugin_form) && !empty(Element::children($plugin_form))) {
          $subform_state = SubformState::createForSubform($item_form['behavior_plugins'][$plugin_id], $form_state->getCompleteForm(), $form_state);
          $plugin_values->submitBehaviorForm($paragraph, $item_form['behavior_plugins'][$plugin_id], $subform_state);
        }
      }
    }

    // Save paragraph back to widget state.
    $widget_state['items'][$delta]['entity'] = $paragraph;

    // Save layout settings.
    if (!empty($item_form['layout_selection']['layout'])) {

      $layout_settings = $this->getLayoutSettings($paragraph);
      $layout = $form_state->getValue($item_form['layout_selection']['layout']['#parents']);
      $layout_settings['layout'] = $layout;

      // Save layout config:
      if (!empty($item_form['layout_plugin_form'])) {
        try {

          $layout_instance = $this->layoutPluginManager->createInstance($layout);
          if ($this->getLayoutPluginForm($layout_instance)) {
            $subform_state = SubformState::createForSubform($item_form['layout_plugin_form'], $form_state->getCompleteForm(), $form_state);
            $layout_instance->submitConfigurationForm($item_form['layout_plugin_form'], $subform_state);
            $layout_settings['config'] = $layout_instance->getConfiguration();
          }

          $this->setLayoutSettings($paragraph, $layout_settings);
        }
        catch (\Exception $e) {
          watchdog_exception('Layout Paragraphs, Layout Instance generation', $e);
        }
      }

      // Handle orphaned items.
      if (isset($item_form['layout_selection']['move_items'])) {
        $move_items = $form_state->getValue($item_form['layout_selection']['move_items']['#parents']);
        if ($move_items && isset($move_items['items'])) {
          $parent_uuid = $paragraph->uuid();
          foreach ($move_items['items'] as $from_region => $to_region) {
            foreach ($widget_state['items'] as $delta => $item) {
              $layout_settings = $this->getLayoutSettings($item['entity']);
              if ($layout_settings['parent_uuid'] == $parent_uuid && $layout_settings['region'] == $from_region) {
                $this->setLayoutSetting($widget_state['items'][$delta]['entity'], 'region', $to_region);
                // We have to update user input directly
                // or the region setting will be
                // overwritten by the form.
                $path = array_merge($parents, [
                  $this->fieldName,
                  $delta,
                  'region',
                ]);
                $input = $form_state->getUserInput();
                NestedArray::setValue($input, $path, $to_region);
                $form_state->setUserInput($input);
              }
            }
          }
        }
      }
    }

    // Close the entity form.
    $widget_state['open_form'] = FALSE;
    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Form submit handler - cancels editing an item and closes form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelItemSubmit(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $delta = $element['#delta'];
    $widget_state = static::getWidgetState($parents, $this->fieldName, $form_state);

    // If canceling an item that hasn't been created yet, remove it.
    if (!empty($widget_state['items'][$delta]['is_new'])) {
      unset($widget_state['items'][$delta]['entity']);
    }
    $widget_state['open_form'] = FALSE;
    static::setWidgetState($parents, $this->fieldName, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to return the entire ERL element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function elementAjax(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_field = NestedArray::getValue($form, $field_state['array_parents']);
    $html_id = $this->entityFormHtmlId($field_state);

    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('#' . $html_id));
    $response->addCommand(new ReplaceCommand('#' . $this->wrapperId, $widget_field));
    $response->addCommand(new LayoutParagraphsStateResetCommand('#' . $this->wrapperId));
    return $response;
  }

  /**
   * Ajax callback to return the entire ERL element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function saveItemAjax(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $uuid = $triggering_element['#uuid'];
    $delta = $triggering_element['#delta'];
    $parents = $triggering_element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_field = NestedArray::getValue($form, $field_state['array_parents']);
    $html_id = $this->entityFormHtmlId($field_state);
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $entity_form = $widget_field['entity_form'];
      $selector = '#' . $this->wrapperId . ' .layout-paragraphs-form';
      $entity_form['status'] = [
        '#weight' => -100,
        '#type' => 'status_messages',
      ];
      $response->addCommand(new ReplaceCommand($selector, $entity_form));
    }
    else {
      $element = static::findElementByUuid($widget_field, $uuid);
      $path = array_merge($widget_field['#parents'], [
        'add_more',
        'actions',
      ]);
      $target_id = $form_state->getValue(array_merge($path, ['dom_id']));
      $insert_method = $form_state->getValue(array_merge($path, ['insert_method']));

      $settings = [
        'target_id' => $target_id,
        'insert_method' => $insert_method,
        'element_id' => $this->wrapperId . '--item-' . $delta,
      ];
      $response->addCommand(new LayoutParagraphsInsertCommand($settings, $element));
      $response->addCommand(new CloseDialogCommand('#' . $html_id));
      $response->addCommand(new SettingsCommand([
        'layoutParagraphsWidgets' => [
          $this->wrapperId => [
            'itemsCount' => $this->activeItemsCount($field_state['items']),
          ],
        ],
      ], TRUE));
    }
    $disabled_bin = $widget_field['disabled'];
    $response->addCommand(new ReplaceCommand('#' . $this->wrapperId . ' .layout-paragraphs-disabled-items', $disabled_bin));
    return $response;
  }

  /**
   * Ajax callback to return the entire ERL element.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function editItemAjax(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_field = NestedArray::getValue($form, $field_state['array_parents']);
    $entity_form = $widget_field['entity_form'];
    $html_id = $this->entityFormHtmlId($field_state);

    $dialog_options = [
      'dialogClass' => 'layout-paragraphs-dialog',
      'modal' => TRUE,
      'appendTo' => '#' . $this->wrapperId,
      'width' => 800,
      'drupalAutoButtons' => FALSE,
    ];

    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $content = [
        'status' => [
          '#weight' => -100,
          '#type' => 'status_messages',
        ],
      ];
      $response->addCommand(new OpenDialogCommand('#' . $html_id, $this->t('Unexpected Error'), $content, $dialog_options));
      $response->addCommand(new LayoutParagraphsStateResetCommand('#' . $this->wrapperId));
    }
    else {
      $response->addCommand(new AppendCommand('#' . $this->wrapperId, '<div id="' . $html_id . '"></div>'));
      $response->addCommand(new OpenDialogCommand('#' . $html_id, 'Edit Form', $entity_form, $dialog_options));
      $response->addCommand(new LayoutParagraphsStateResetCommand('#' . $this->wrapperId));
    }
    return $response;
  }

  /**
   * Ajax callback to remove an item - launches confirmation dialog.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function removeItemAjax(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $widget_field = NestedArray::getValue($form, $field_state['array_parents']);
    $entity_form = $widget_field['remove_form'];
    $entity = $entity_form['#entity'];
    $type = $entity->getParagraphType();

    $html_id = $this->entityFormHtmlId($field_state);

    $dialog_options = [
      'dialogClass' => 'layout-paragraphs-dialog',
      'modal' => TRUE,
      'appendTo' => '#' . $this->wrapperId,
      'width' => 800,
      'drupalAutoButtons' => FALSE,
    ];

    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand('#' . $this->wrapperId, '<div id="' . $html_id . '"></div>'));
    $response->addCommand(new OpenDialogCommand('#' . $html_id, $this->t('Remove @type', ['@type' => $type->label()]), $entity_form, $dialog_options));
    $response->addCommand(new LayoutParagraphsStateResetCommand('#' . $this->wrapperId));
    return $response;
  }

  /**
   * Ajax callback to remove an item - removes item from DOM.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response
   */
  public function removeItemConfirmAjax(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $uuid = $element['#uuid'];
    $parents = $element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $html_id = $this->entityFormHtmlId($field_state);
    $widget_field = NestedArray::getValue($form, $field_state['array_parents']);
    $disabled_bin = $widget_field['disabled'];

    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('.paragraph-' . $uuid));
    $response->addCommand(new CloseDialogCommand('#' . $html_id));
    $response->addCommand(new ReplaceCommand('#' . $this->wrapperId . ' .layout-paragraphs-disabled-items', $disabled_bin));
    $response->addCommand(new SettingsCommand([
      'layoutParagraphsWidgets' => [
        $this->wrapperId => [
          'itemsCount' => $this->activeItemsCount($field_state['items']),
        ],
      ],
    ], TRUE));
    return $response;
  }

  /**
   * Form submit handler - cancels item removal and closes confirmation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax Response.
   */
  public function closeDialogAjax(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#element_parents'];
    $field_state = static::getWidgetState($parents, $this->fieldName, $form_state);
    $html_id = $this->entityFormHtmlId($field_state);

    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('#' . $html_id));
    return $response;
  }

  /**
   * Ajax callback to return a layout plugin configuration form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   The Ajax Response.
   */
  public function buildLayoutConfigurationFormAjax(array $form, FormStateInterface $form_state) {

    $element = $form_state->getTriggeringElement();
    $parents = $element['#array_parents'];
    $parents = array_splice($parents, 0, -2);
    if ($entity_form = NestedArray::getValue($form, $parents)) {
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#' . $this->wrapperId . ' .layout-paragraphs-form', $entity_form));
      $response->addCommand(new InvokeCommand('#' . $this->wrapperId . ' .layout-paragraphs-form input:checked', 'focus'));
      $response->addCommand(new LayoutParagraphsStateResetCommand('#' . $this->wrapperId));
      return $response;
    }
    else {
      return [];
    }
  }

  /**
   * Returns count of active paragraph items.
   *
   * Deleted paragraph items are not deleted immediately,
   * but flagged for removal. This function returns the number
   * of items not flagged for removal.
   *
   * @param array $items
   *   The array of field items.
   */
  protected function activeItemsCount(array $items) {
    return array_reduce($items, function ($count, $item) {
      return isset($item['entity']) ? $count + 1 : $count;
    }, 0);
  }

  /**
   * Recursively search the build array for element with matching uuid.
   *
   * @param array $array
   *   Nested build array.
   * @param string $uuid
   *   The uuid of the element to find.
   *
   * @return array
   *   The matching element build array.
   */
  public static function findElementByUuid(array $array, string $uuid) {
    $element = FALSE;
    if (isset($array['active_items']['items']) && isset($array['disabled']['items'])) {
      return static::findElementByUuid($array['active_items']['items'] + $array['disabled']['items'], $uuid);
    }
    foreach ($array as $key => $item) {
      if (is_array($item)) {
        if (isset($item['#entity'])) {
          if ($item['#entity']->uuid() == $uuid) {
            return $item;
          }
        }
        if (isset($item['preview']['regions'])) {
          foreach (Element::children($item['preview']['regions']) as $region_name) {
            if ($element = static::findElementByUuid($item['preview']['regions'][$region_name], $uuid)) {
              return $element;
            }
          }
        }
      }
    }
    return $element;
  }

  /**
   * Search $items for children of $parent.
   *
   * @param \Drupal\Paragraphs\ParagraphInterface $parent
   *   The parent paragraph.
   * @param array $items
   *   An array of items to search.
   * @param string $region
   *   The region string.
   *
   * @return bool
   *   True if finds children.
   */
  public function hasChildren(ParagraphInterface $parent, array $items, string $region = '') {
    $uuid = $parent->uuid();
    foreach ($items as $item) {
      if (isset($item['entity'])) {
        $layout_settings = $this->getLayoutSettings($item['entity']);
        if ($region) {
          if ($layout_settings['region'] == $region && $layout_settings['parent_uuid'] == $uuid) {
            return TRUE;
          }
        }
        elseif ($layout_settings['parent_uuid'] == $uuid) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Recursively remove decendants from list of items.
   *
   * @param array $items
   *   The entire list of items.
   * @param string $uuid
   *   The parent/ancestor uuid.
   */
  protected function removeChildren(array &$items, string $uuid) {
    foreach ($items as $index => $item) {
      if (isset($item['entity'])) {
        /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
        $paragraph = $item['entity'];
        $layout_settings = $this->getLayoutSettings($paragraph);
        if ($layout_settings['parent_uuid'] == $uuid) {
          unset($items[$index]['entity']);
          $this->removeChildren($items, $paragraph->uuid());
        }
      }
    }
  }

  /**
   * Generates an ID for the entity form dialog container.
   *
   * @param array $field_state
   *   The field state with array_parents.
   *
   * @return string
   *   The HTML id.
   */
  private function entityFormHtmlId(array $field_state) {
    return trim(Html::getId(implode('-', $field_state['array_parents']) . '-entity-form'), '-');
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Gets the layout settings for a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return array
   *   The layout settings array.
   */
  protected function getLayoutSettings(ParagraphInterface $paragraph) {
    $defaults = [
      'parent_uuid' => '',
      'layout' => '',
      'region' => '',
      'config' => [],
    ];
    $behavior_settings = $paragraph->getAllBehaviorSettings();
    return ($behavior_settings['layout_paragraphs'] ?? []) + $defaults;
  }

  /**
   * Sets the layout settings for a given paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   * @param string $name
   *   The layout setting name.
   * @param mixed $value
   *   The layout setting value.
   */
  protected function setLayoutSetting(ParagraphInterface &$paragraph, string $name, $value) {
    $settings = $this->getLayoutSettings($paragraph);
    $settings[$name] = $value;
    $this->setLayoutSettings($paragraph, $settings);
  }

  /**
   * Sets the layout settings for a given paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   * @param array $layout_settings
   *   An array of layout settings.
   */
  protected function setLayoutSettings(ParagraphInterface &$paragraph, array $layout_settings) {
    $paragraph->setBehaviorSettings('layout_paragraphs', $layout_settings);
  }

  /**
   * Returns true if the paragraph can be used as a layout section.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return bool
   *   True if paragraph is a layout section.
   */
  protected function isLayoutParagraph(ParagraphInterface &$paragraph) {
    $available_layouts = $this->getAvailableLayouts($paragraph);
    return count($available_layouts) > 0;
  }

  /**
   * Returns an array of available layouts for a given paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return array
   *   An array of available layout plugins.
   */
  protected function getAvailableLayouts(ParagraphInterface &$paragraph) {
    $paragraphs_type = $paragraph->getParagraphType();
    return $this->getAvailableLayoutsByType($paragraphs_type);
  }

  /**
   * Returns an array of available layouts for a given paragraph type.
   *
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type
   *   The paragraph entity.
   *
   * @return array
   *   An array of available layout plugins.
   */
  protected function getAvailableLayoutsByType(ParagraphsTypeInterface $paragraphs_type) {
    $plugins = $paragraphs_type->getEnabledBehaviorPlugins();
    if (isset($plugins['layout_paragraphs'])) {
      $layout_paragraphs_plugin = $paragraphs_type->getBehaviorPlugin('layout_paragraphs');
      $config = $layout_paragraphs_plugin->getConfiguration();
      return $config['available_layouts'] ?? [];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->getFieldSetting('target_type');
    $element = parent::settingsForm($form, $form_state);
    $element['preview_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Preview view mode'),
      '#default_value' => $this->getSetting('preview_view_mode'),
      '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type_id),
      '#description' => $this->t('View mode for the referenced entity preview on the edit form. Automatically falls back to "default", if it is not enabled in the referenced entity type displays.'),
    ];
    $element['nesting_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum nesting depth'),
      '#options' => range(0, 10),
      '#default_value' => $this->getSetting('nesting_depth'),
      '#description' => $this->t('Choosing 0 will prevent nesting layouts within other layouts.'),
    ];
    $element['require_layouts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require paragraphs to be added inside a layout'),
      '#default_value' => $this->getSetting('require_layouts'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $entity_type = $this->getFieldSetting('target_type');
    $target_bundles = array_keys($this->getAllowedTypes());
    $definition = $this->entityTypeManager->getDefinition($entity_type);
    $storage = $this->entityTypeManager->getStorage($definition->getBundleEntityType());
    $has_layout = FALSE;
    try {
      if (!empty($target_bundles)) {
        foreach ($target_bundles as $target_bundle) {
          /** @var \Drupal\paragraphs\ParagraphsTypeInterface $type */
          $type = $storage->load($target_bundle);
          if (count($this->getAvailableLayoutsByType($type)) > 0) {
            $has_layout = TRUE;
            break;
          }
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('paragraphs layout widget, behaviour plugin', $e);
    }
    if (!$has_layout) {
      $field_name = $this->fieldDefinition->getLabel();
      $message = $this->t('To use layouts with the "@field_name" field, make sure you have enabled the "Paragraphs Layout" behavior for at least one target paragraph type.', ['@field_name' => $field_name]);
      $this->messenger()->addMessage($message, $this->messenger()::TYPE_WARNING);
    }
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Preview view mode: @preview_view_mode', ['@preview_view_mode' => $this->getSetting('preview_view_mode')]);
    $summary[] = $this->t('Maximum nesting depth: @max_depth', ['@max_depth' => $this->getSetting('nesting_depth')]);
    if ($this->getSetting('require_layouts')) {
      $summary[] = $this->t('Paragraphs <b>must be</b> added within layouts.');
    }
    else {
      $summary[] = $this->t('Layouts are optional.');
    }
    $summary[] = $this->t('Maximum nesting depth: @max_depth', ['@max_depth' => $this->getSetting('nesting_depth')]);
    return $summary;
  }

  /**
   * Default settings for widget.
   *
   * @return array
   *   The default settings array.
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'preview_view_mode' => 'default',
      'nesting_depth' => 0,
      'require_layouts' => 0,
    ];

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => &$item) {
      unset($values[$delta]['actions']);
      if (isset($item['entity']) && $item['entity'] instanceof ParagraphInterface) {
        /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph_entity */
        $paragraph_entity = $item['entity'];

        // Merge region and parent uuid into paragraph behavior settings.
        $behavior_settings = $this->getLayoutSettings($paragraph_entity);
        $new_behavior_settings = [
          'region' => $item['region'],
          'parent_uuid' => $item['parent_uuid'],
        ];
        $this->setLayoutSettings($paragraph_entity, $new_behavior_settings + $behavior_settings);

        $paragraph_entity->setNeedsSave(TRUE);
        $item['target_id'] = $paragraph_entity->id();
        $item['target_revision_id'] = $paragraph_entity->getRevisionId();
      }
    }
    return $values;
  }

  /**
   * Retrieves the plugin form for a given layout.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   The layout plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface|null
   *   The plugin form for the layout.
   */
  protected function getLayoutPluginForm(LayoutInterface $layout) {
    if ($layout instanceof PluginWithFormsInterface) {
      try {
        return $this->pluginFormFactory->createInstance($layout, 'configure');
      }
      catch (\Exception $e) {
        watchdog_exception('Erl, Layout Configuration', $e);
      }
    }

    if ($layout instanceof PluginFormInterface) {
      return $layout;
    }

    return NULL;
  }

  /**
   * Determine if widget is in translation.
   *
   * Initializes $this->isTranslating.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\ContentEntityInterface $host
   *   The host entity.
   */
  protected function initIsTranslating(FormStateInterface $form_state, ContentEntityInterface $host) {
    if ($this->isTranslating != NULL) {
      return;
    }
    $this->isTranslating = FALSE;
    if (!$host->isTranslatable()) {
      return;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return;
    }

    if (!empty($form_state->get('content_translation'))) {
      // Adding a language through the ContentTranslationController.
      $this->isTranslating = TRUE;
    }
    $langcode = $form_state->get('langcode');
    if ($host->hasTranslation($langcode) && $host->getTranslation($langcode)->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
  }

  /**
   * Checks if we can allow reference changes.
   *
   * @return bool
   *   TRUE if we can allow reference changes, otherwise FALSE.
   */
  protected function allowReferenceChanges() {
    return !$this->isTranslating;
  }

  /**
   * After-build callback for adding the translatability clue from the widget.
   *
   * ContentTranslationHandler::addTranslatabilityClue() adds an
   * "(all languages)" suffix to the widget title, replicate that here.
   *
   * @param array $element
   *   The Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element containing a translatability clue.
   */
  public static function addTranslatabilityClue(array $element, FormStateInterface $form_state) {
    static $suffix, $fapi_title_elements;

    // Widgets could have multiple elements with their own titles, so remove the
    // suffix if it exists, do not recurse lower than this to avoid going into
    // nested paragraphs or similar nested field types.
    // Elements which can have a #title attribute according to FAPI Reference.
    if (!isset($suffix)) {
      $suffix = ' <span class="translation-entity-all-languages">(' . t('all languages') . ')</span>';
      $fapi_title_elements = array_flip([
        'checkbox',
        'checkboxes',
        'date',
        'details',
        'fieldset',
        'file',
        'item',
        'password',
        'password_confirm',
        'radio',
        'radios',
        'select',
        'textarea',
        'textfield',
        'weight',
      ]);
    }

    // Update #title attribute for all elements that are allowed to have a
    // #title attribute according to the Form API Reference. The reason for this
    // check is because some elements have a #title attribute even though it is
    // not rendered; for instance, field containers.
    if (isset($element['#type']) && isset($fapi_title_elements[$element['#type']]) && isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    // If the current element does not have a (valid) title, try child elements.
    elseif ($children = Element::children($element)) {
      foreach ($children as $delta) {
        $element[$delta] = static::addTranslatabilityClue($element[$delta], $form_state);
      }
    }
    // If there are no children, fall back to the current #title attribute if it
    // exists.
    elseif (isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    return $element;
  }

}
