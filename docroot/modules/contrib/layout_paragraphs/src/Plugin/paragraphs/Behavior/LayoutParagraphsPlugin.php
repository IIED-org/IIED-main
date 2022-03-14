<?php

namespace Drupal\layout_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to define grid based layouts.
 *
 * @ParagraphsBehavior(
 *   id = "layout_paragraphs",
 *   label = @Translation("Layout Paragraphs"),
 *   description = @Translation("Integrates paragraphs with layout discovery and layout API."),
 *   weight = 0
 * )
 */
class LayoutParagraphsPlugin extends ParagraphsBehaviorBase {

  /**
   * The layout plugin manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * ParagraphsLayoutPlugin constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   This plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The grid discovery service.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityFieldManager $entity_field_manager,
      LayoutPluginManagerInterface $layout_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'available_layouts' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->layoutPluginManager->getLayoutOptions();
    $available_layouts = $this->configuration['available_layouts'];
    $form['available_layouts'] = [
      '#title' => $this->t('Available Layouts'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => array_keys($available_layouts),
      '#size' => count($options) < 8 ? count($options) * 2 : 10,
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('available_layouts'))) {
      $form_state->setErrorByName('available_layouts', $this->t('You must select at least one layout.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $available_layouts = array_filter($form_state->getValue('available_layouts'));
    foreach ($available_layouts as $layout_name) {
      $layout = $this->layoutPluginManager->getDefinition($layout_name);
      $this->configuration['available_layouts'][$layout_name] = $layout->getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {

  }

}
