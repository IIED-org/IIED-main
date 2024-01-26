<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Render\Renderer;
use Drupal\views\Plugin\views\display\Page as ViewsPage;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * The base plugin to create DS code fields.
 */
abstract class TokenBase extends DsFieldBase {

  /**
   * The Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The LanguageManager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal core Render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Display Suite field plugin.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, Token $token_service, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, Renderer $renderer, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->token = $token_service;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->content();
    $format = $this->format();
    // Get the current code for current language.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $replacement_data = $this->getTokenReplacements();
    $value = $this->token->replace($content, $replacement_data, ['langcode' => $langcode, 'clear' => TRUE]);

    // Empty string in token fields treated as empty field.
    if ($value === '') {
      return [];
    }

    return [
      '#type' => 'processed_text',
      '#text' => $value,
      '#format' => $format,
      '#filter_types_to_skip' => [],
      '#langcode' => $langcode,
    ];
  }

  /**
   * Returns the format of the code field.
   */
  protected function format() {
    return 'plain_text';
  }

  /**
   * Returns the value of the code field.
   */
  protected function content() {
    return '';
  }

  /**
   * Get token replacements.
   *
   * @return array
   *   The token replacements values.
   */
  protected function getTokenReplacements() {
    $replacement_data = [
      $this->getEntityTypeId() => $this->entity(),
    ];
    // Add extra tokens if available.
    if ($this->moduleHandler->moduleExists('views')) {
      if ($this->isUseGlobalViewToken()) {
        if (($views_page_render = ViewsPage::getPageRenderArray())
          && ($current_view = Views::getView($views_page_render['#view_id']))
          && $current_view->access($views_page_render['#display_id'])
          && $current_view->setDisplay($views_page_render['#display_id'])) {
          $current_view->render();
          $replacement_data['view'] = $current_view;
        }
      }
      else {
        // Use the view attached to the entity, if any.
        // @see Drupal\views\Entity\Render\TranslationLanguageRenderer::preRender().
        if (isset($this->entity()->view) && ($this->entity()->view instanceof ViewExecutable)) {
          $replacement_data['view'] = $this->entity()->view;
        }
      }
    }
    if ($this->useGlobalEntity()) {
      if ($page_entity = $this->getCurrentGlobalEntity()) {
        if ($this->forceGlobalEntity()) {
          $replacement_data[$page_entity->getEntityTypeId()] = $page_entity;
        }
        else {
          if (empty($replacement_data[$page_entity->getEntityTypeId()])) {
            $replacement_data[$page_entity->getEntityTypeId()] = $page_entity;
          }
        }
      }
    }
    return $replacement_data;
  }

  /**
   * Determine if the use global view token option is enabled.
   *
   * @return bool
   *   The option value.
   */
  protected function isUseGlobalViewToken() {
    return !empty($this->pluginDefinition['properties']['use_global_view_token']);
  }

  /**
   * Determine if the use global entity token option is enabled.
   *
   * @return bool
   *   The option value.
   */
  protected function useGlobalEntity() {
    return !empty($this->pluginDefinition['properties']['use_global_entity']);
  }

  /**
   * Determine if the use global entity token option is enabled.
   *
   * @return bool
   *   The option value.
   */
  protected function forceGlobalEntity() {
    return !empty($this->pluginDefinition['properties']['force_global_entity']);
  }

  /**
   * Retuns the current entity, if found or null.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object or null.
   */
  protected function getCurrentGlobalEntity() {
    $entity = NULL;
    // First check if we are on a node revision, this route doesn't return
    // a node object and if patch from drupal issue #2730631 landed,
    // it would be the current node, not the revsion being viewed.
    // We also add a check in case node_revision becomes an node
    // object some day.
    $node_revision = $this->routeMatch->getParameter('node_revision');
    if ($node_revision instanceof NodeInterface) {
      $entity = $node_revision;
    }
    elseif (is_numeric($node_revision) && $node_revision > 0) {
      $entity = $this->entityTypeManager->getStorage('node')->loadRevision($node_revision);
    }
    // Not a revision, lets do it the normal way.
    if ($entity === NULL) {
      $route_params = $this->routeMatch->getParameters();
      // We only want the first node, usually there is only one.
      foreach ($route_params as $route_param) {
        if ($route_param instanceof EntityInterface) {
          $entity = $route_param;
          break;
        }
      }
    }
    return $entity;
  }

}
