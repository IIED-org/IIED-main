<?php

namespace Drupal\layout_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;
use Drupal\layout_paragraphs\Plugin\Field\FieldWidget\LayoutParagraphsWidget;

/**
 * Layout Paragraphs field formatter.
 *
 * @FieldFormatter(
 *   id = "layout_paragraphs_builder",
 *   label = @Translation("Layout Paragraphs Builder (Experimental)"),
 *   description = @Translation("Renders editable paragraphs with layout."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class LayoutParagraphsBuilderFormatter extends LayoutParagraphsFormatter implements ContainerFactoryPluginInterface {

  /**
   * The tempstore.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $tempstore;

  /**
   * The Layout Paragraphs Builder Access service.
   *
   * @var \Drupal\layout_paragraphs\Access\LayoutParagraphsBuilderAccess
   */
  protected $layoutParagraphsBuilderAccess;

  /**
   * Account for the logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityDisplayRepositoryInterface $entity_display_repository, LayoutParagraphsLayoutTempstoreRepository $tempstore, AccessInterface $layout_paragraphs_builder_access, AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_display_repository);
    $this->tempstore = $tempstore;
    $this->layoutParagraphsBuilderAccess = $layout_paragraphs_builder_access;
    $this->account = $current_user->getAccount();
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
      $container->get('layout_paragraphs.tempstore_repository'),
      $container->get('layout_paragraphs.builder_access'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {

    $elements = [
      '#theme' => 'layout_paragraphs_builder_formatter',
      '#root_components' => parent::view($items, $langcode),
    ];
    $entity = $items->getEntity();
    /** @var \Drupal\Core\Entity\EntityDefintion $definition */
    $definition = $items->getFieldDefinition();
    $layout = new LayoutParagraphsLayout($items, $this->getSettings() + ['reference_field_view_mode' => $this->viewMode]);

    if (!$entity->id() || !$this->layoutParagraphsBuilderAccess->access($this->account, $layout)->isAllowed() || $entity->in_preview) {
      return $elements['#root_components'];
    }

    $elements['#link_text'] = $this->t('Edit @label', ['@label' => $definition->label()]);

    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $field_name = $items->getName();

    $lpb_classname = Html::cleanCssIdentifier('lpb-' . $entity_type . '-' . $entity_id . '-' . $field_name . '-' . $this->viewMode);
    $route_params = [
      'entity_type' => $entity_type,
      'entity' => $entity_id,
      'field_name' => $field_name,
      'view_mode' => $this->viewMode,
    ];
    $url_options = [
      'query' => [
        'lpb-classname' => $lpb_classname,
      ],
    ];
    if ($entity instanceof RevisionableInterface) {
      $url_options['query']['revision_id'] = $entity->getRevisionId();
    }
    $elements['#link_url'] = Url::fromRoute('layout_paragraphs.builder.formatter', $route_params, $url_options);
    $elements['#field_label'] = $definition->label();
    $elements['#type'] = 'container';
    $elements['#is_empty'] = count($layout->getRootComponents()) == 0;
    $elements['#attributes'] = [
      'class' => [
        'lpb-formatter',
        $lpb_classname,
      ],
      'data-lpb-id' => $layout->id(),
    ];
    $elements['#attached']['library'][] = 'layout_paragraphs/builder';
    $elements['#attached']['library'][] = 'layout_paragraphs/builder_form';
    return $elements;
  }

  /**
   * Replicates settings from Layout Paragraphs Widget.
   *
   * @see \Drupal\layout_paragraphs\Plugin\Field\FieldWidget\LayoutParagraphsWidget
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $widget = $this->widgetInstance();
    return $widget->settingsForm($form, $form_state);
  }

  /**
   * Replicates settings from Layout Paragraphs Widget.
   *
   * @see \Drupal\layout_paragraphs\Plugin\Field\FieldWidget\LayoutParagraphsWidget
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $widget = $this->widgetInstance();
    return $widget->settingsSummary();
  }

  /**
   * Replicates settings from Layout Paragraphs Widget.
   *
   * @see \Drupal\layout_paragraphs\Plugin\Field\FieldWidget\LayoutParagraphsWidget
   *
   * @return array
   *   The default settings array.
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    return LayoutParagraphsWidget::defaultSettings() + $defaults;
  }

  /**
   * Returns a layout paragraphs field widget with correct settings applied.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   The widget instance.
   */
  protected function widgetInstance() {
    $plugin_manager = \Drupal::service('plugin.manager.field.widget');
    $widget = $plugin_manager->getInstance([
      'field_definition' => $this->fieldDefinition,
      'form_mode' => 'layout_paragraphs_editor',
      'prepare' => TRUE,
      'configuration' => [
        'type' => 'layout_paragraphs',
        'settings' => $this->getSettings(),
        'third_party_settings' => [],
      ],
    ]);
    return $widget;
  }

}
