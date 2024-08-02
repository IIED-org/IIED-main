<?php

namespace Drupal\layout_paragraphs\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Layout paragraphs widget.
 *
 * @FieldWidget(
 *   id = "layout_paragraphs",
 *   label = @Translation("Layout Paragraphs"),
 *   description = @Translation("Layout builder for paragraphs."),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference_revisions"
 *   },
 * )
 */
class LayoutParagraphsWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * The Entity Type Manager service property.
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
   * The layout paragraphs layout.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layoutParagraphsLayout;

  /**
   * The layout paragraphs layout tempstore storage key.
   *
   * @var string
   */
  protected $storageKey;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The source translation language id.
   *
   * @var string
   */
  protected $sourceLangcode;

  /**
   * The language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Indicates whether the element is in translation mode.
   *
   * @var bool
   */
  protected $isTranslating;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    LayoutParagraphsLayoutTempstoreRepository $tempstore,
    EntityTypeManagerInterface $entity_type_manager,
    LayoutPluginManagerInterface $layout_plugin_manager,
    FormBuilderInterface $form_builder,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ConfigFactoryInterface $config_factory,
    EntityRepositoryInterface $entity_repository
    ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->tempstore = $tempstore;
    $this->entityTypeManager = $entity_type_manager;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->formBuilder = $form_builder;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityRepository = $entity_repository;
    $this->config = $config_factory->get('layout_paragraphs.settings');

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
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.core.layout'),
      $container->get('form_builder'),
      $container->get('entity_display.repository'),
      $container->get('config.factory'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $parents = array_merge($form['#parents'], [
      $this->fieldDefinition->getName(),
      'layout_paragraphs_storage_key',
    ]);
    $layout_paragraphs_storage_key = NestedArray::getValue($input, $parents);
    // If the form is being rendered for the first time, create a new Layout
    // Paragraphs Layout instance, save it to tempstore, and store the key.
    if (empty($layout_paragraphs_storage_key)) {
      $this->layoutParagraphsLayout = new LayoutParagraphsLayout($items, $this->getSettings());
      $this->tempstore->set($this->layoutParagraphsLayout);
      $layout_paragraphs_storage_key = $this->tempstore->getStorageKey($this->layoutParagraphsLayout);
    }
    // On subsequent form renders, this loads the correct Layout Paragraphs
    // Layout from the tempstore using the storage key.
    else {
      $this->layoutParagraphsLayout = $this->tempstore->getWithStorageKey($layout_paragraphs_storage_key);
    }
    $this->initTranslations($form_state);
    $element += [
      '#type' => 'fieldset',
      '#title' => $this->fieldDefinition->getLabel(),
      'layout_paragraphs_builder' => [
        '#type' => 'layout_paragraphs_builder',
        '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
        '#is_translating' => $this->isTranslating($form_state),
      ],
      // Stores the Layout Paragraphs Layout storage key.
      'layout_paragraphs_storage_key' => [
        '#type' => 'hidden',
        '#default_value' => $layout_paragraphs_storage_key,
      ],
    ];
    if ($source = $form_state->get(['content_translation', 'source'])) {
      $element['layout_paragraphs_builder']['#source_langcode'] = $source->getId();
    }
    return $element;
  }

  /**
   * Determine if widget is in translation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget::initIsTranslating()
   */
  protected function isTranslating(FormStateInterface $form_state) {
    if ($this->isTranslating != NULL) {
      return $this->isTranslating;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $host */
    $host = $this->layoutParagraphsLayout->getEntity();
    $this->isTranslating = FALSE;
    if (!$host->isTranslatable()) {
      return $this->isTranslating;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return $this->isTranslating;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return $this->isTranslating;
    }

    // Support for
    // \Drupal\content_translation\Controller\ContentTranslationController.
    if (!empty($form_state->get('content_translation'))) {
      // Adding a translation.
      $this->isTranslating = TRUE;
    }
    $langcode = $form_state->get('langcode');
    if (isset($langcode) && $host->hasTranslation($langcode) && $host->getTranslation($langcode)->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
    return $this->isTranslating;
  }

  /**
   * Initialize translations for item list.
   *
   * Makes sure all components have a translation for the current
   * language and creates them if necessary.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function initTranslations(FormStateInterface $form_state) {
    if ($source = $form_state->get(['content_translation', 'source'])) {
      $this->sourceLangcode = $source->getId();
    }
    $this->langcode = $this->entityRepository
      ->getTranslationFromContext($this->layoutParagraphsLayout->getEntity())
      ->language()
      ->getId();
    $items = $this->layoutParagraphsLayout->getParagraphsReferenceField();
    /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
    foreach ($items as $delta => $item) {
      if (!empty($item->entity) && $item->entity instanceof ParagraphInterface) {
        // Now we're sure it's a paragraph:
        $paragraph = $item->entity;
        if (!$this->isTranslating($form_state)) {
          // Set the langcode if we are not translating.
          $langcode_key = $paragraph->getEntityType()->getKey('langcode');
          if ($paragraph->get($langcode_key)->value != $this->langcode) {
            // If a translation in the given language already exists,
            // switch to that. If there is none yet, update the language.
            if ($paragraph->hasTranslation($this->langcode)) {
              $paragraph = $paragraph->getTranslation($this->langcode);
            }
            else {
              $paragraph->set($langcode_key, $this->langcode);
            }
          }
        }
        else {
          // Add translation if missing for the target language,
          // if the paragraph is translatable at all:
          if ($paragraph->isTranslatable() && !$paragraph->hasTranslation($this->langcode)) {
            // Get the selected translation of the paragraph entity.
            $entity_langcode = $paragraph->language()->getId();
            $source_langcode = $this->sourceLangcode ?? $entity_langcode;
            // Make sure the source language version is used if available.
            // Fetching the translation without this check could lead valid
            // scenario to have no paragraphs items in the source version of
            // to an exception.
            if ($paragraph->hasTranslation($source_langcode)) {
              $paragraph = $paragraph->getTranslation($source_langcode);
            }
            // The paragraphs entity has no content translation source field
            // if no paragraph entity field is translatable,
            // even if the host is.
            if ($paragraph->hasField('content_translation_source')) {
              // Initialise the translation with source language values.
              $paragraph->addTranslation($this->langcode, $paragraph->toArray());
              $translation = $paragraph->getTranslation($this->langcode);
              $manager = \Drupal::service('content_translation.manager');
              $manager->getTranslationMetadata($translation)
                ->setSource($paragraph->language()->getId());
            }
          }
          // If any paragraphs type is translatable do not switch.
          if ($paragraph->isTranslatable() && $paragraph->hasField('content_translation_source')) {
            // Switch the paragraph to the translation.
            $paragraph = $paragraph->getTranslation($this->langcode);
          }
        }
        $items[$delta]->entity = $paragraph;
      }
    }
    $this->layoutParagraphsLayout->setParagraphsReferenceField($items);
    $this->tempstore->set($this->layoutParagraphsLayout);
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    // Load the correct layout paragraphs layout instnace using the value
    // passed in the layout_instance_id hidden field.
    $path = array_merge($form['#parents'], [$field_name]);
    $layout_paragraphs_storage_key = $form_state->getValue(array_merge($path, ['layout_paragraphs_storage_key']));
    if (!empty($layout_paragraphs_storage_key)) {
      $this->layoutParagraphsLayout = $this->tempstore->getWithStorageKey($layout_paragraphs_storage_key);
      $values = [];
      foreach ($this->layoutParagraphsLayout->getParagraphsReferenceField() as $item) {
        if ($item->entity) {
          $entity = $item->entity;
          // Set each paragraph langcode if we are not translating.
          if (!$this->isTranslating($form_state)) {
            $langcode_key = $entity->getEntityType()->getKey('langcode');
            $entity->set($langcode_key, $items->getLangcode());
          }
          $entity->setNeedsSave(TRUE);
          $values[] = [
            'entity' => $entity,
            'target_id' => $entity->id(),
            'target_revision_id' => $entity->getRevisionId(),
          ];
        }
      }
      $form_state->setValue($path, $values);
    }
    return parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->getFieldSetting('target_type');
    $element = parent::settingsForm($form, $form_state);
    $element['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type_id),
      '#required' => TRUE,
    );
    $element['preview_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Preview view mode'),
      '#default_value' => $this->getSetting('preview_view_mode'),
      '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type_id),
      '#description' => $this->t('View mode for the referenced entity preview on the edit form. Automatically falls back to "default" if it is not enabled in the referenced entity type displays.'),
    ];
    $element['form_display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form display mode'),
      '#default_value' => $this->getSetting('form_display_mode'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions($entity_type_id),
      '#description' => $this->t('The form display mode to use when rendering the paragraph form. Automatically falls back to "default", if it is not enabled in the referenced entity type displays.'),
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
    $element['empty_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder message to display when field is empty'),
      '#default_value' => $this->getSetting('empty_message'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $preview_view_mode = $this->getSetting('preview_view_mode');

    $summary[] = $this->t('Rendered as @mode', ['@mode' => $view_modes[$view_mode] ?? $view_mode]);
    $summary[] = $this->t('Preview view mode: @preview_mode', ['@preview_mode' => $view_modes[$preview_view_mode] ?? $preview_view_mode]);
    $summary[] = $this->t('Form display mode: @form_display_mode', ['@form_display_mode' => $this->getSetting('form_display_mode')]);
    $summary[] = $this->t('Maximum nesting depth: @max_depth', ['@max_depth' => $this->getSetting('nesting_depth')]);
    if ($this->getSetting('require_layouts')) {
      $summary[] = $this->t('Paragraphs <b>must be</b> added within layouts.');
    }
    else {
      $summary[] = $this->t('Layouts are optional.');
    }
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
      'empty_message' => '',
      'view_mode' => 'default',
      'preview_view_mode' => 'default',
      'form_display_mode' => 'default',
      'nesting_depth' => 0,
      'require_layouts' => 0,
    ];
    return $defaults;
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

}
