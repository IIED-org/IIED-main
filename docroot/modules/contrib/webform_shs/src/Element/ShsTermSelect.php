<?php

namespace Drupal\webform_shs\Element;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Provides a webform element for an shs term select menu.
 *
 * @FormElement("webform_shs_term_select")
 */
class ShsTermSelect extends Select {

  /**
   * The list of term options.
   *
   * @var null|array
   */
  protected static ?array $options = NULL;

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
        '#vocabulary' => '',
        '#force_deepest' => FALSE,
        '#force_deepest_error' => '',
        '#cache_options' => FALSE,
        '#depth_labels' => [],
        '#addNewLabel' => '',
      ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form): array {
    self::setOptions($element);

    $element = parent::processSelect($element, $form_state, $complete_form);

    // Must convert this element['#type'] to a 'select' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'select';

    // AJAX errors occur when submitting the element config on the webform ui
    // path. \Drupal\shs\Plugin\Field\FieldWidget\OptionsShsWidget also stops
    // rendering on the field ui pages.
    $route = \Drupal::routeMatch()->getRouteObject();
    if (\Drupal::service('router.admin_context')->isAdminRoute($route)) {
      return $element;
    }

    $settings = [
      'required' => $element['#required'],
      'multiple' => $element['#webform_multiple'],
      'anyLabel' => $element['#empty_option'] ?? t('- None -'),
      'anyValue' => '_none',
      'force_deepest' => $element['#force_deepest'],
      'cache_options' => $element['#cache_options'],
      '#depth_labels' => [],
      'addNewLabel' => $element['#addNewLabel'] ?: t('Add another item'),
    ];

    /** @var \Drupal\shs\WidgetDefaults $widget_defaults */
    $widget_defaults = \Drupal::service('shs.widget_defaults');
    $bundle = $element['#vocabulary'];
    $cardinality = $element['#multiple'] ? -1 : 1;

    // Define default parents for the widget.
    $parents = $widget_defaults->getInitialParentDefaults($settings['anyValue'], $cardinality);
    if (!is_null($element['#value'])) {
      $parents = $widget_defaults->getParentDefaults($element['#value'], $settings['anyValue'], 'taxonomy_term', $cardinality);
    }

    $settings_shs = [
      'settings' => $settings,
      'labels' => $element['#depth_labels'],
      'bundle' => $bundle,
      'baseUrl' => 'shs-term-data',
      'cardinality' => $cardinality,
      'parents' => $parents,
      'defaultValue' => $element['#value'] ?? NULL,
    ];

    $hooks = [
      'shs_js_settings',
      sprintf('shs_%s_js_settings', $element['#webform_key']),
    ];
    // Allow other modules to override the settings.
    \Drupal::moduleHandler()->alter($hooks, $settings_shs, $bundle, $element['#webform_key']);

    $element['#shs'] = $settings_shs;
    $element['#shs']['classes'] = shs_get_class_definitions($element['#webform_key']);
    $element['#attributes']['class'][] = 'shs-enabled';
    $element['#attributes']['data-shs-selector'] = $element['#webform_key'];
    $element['#attached']['library'][] = 'shs/shs.form';
    $element['#attached']['drupalSettings']['shs'] = [$element['#webform_key'] => $element['#shs']];
    $element['#element_validate'][] = [self::class, 'validateForceDeepest'];

    return $element;
  }

  /**
   * Form API callback. Validate the force deepest option.
   *
   * @param array $element
   *   The element.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public static function validateForceDeepest(array &$element, FormStateInterface $form_state): void{
    if (empty($element['#force_deepest'])) {
      return;
    }

    if (!empty($element['#force_deepest_error'])) {
      $message = $element['#force_deepest_error'];
    }
    else {
      $message = t('You need to select a term from the deepest level in field @name.', ['@name' => $element['#title']]);
    }

    $value = $form_state->getValue($element['#name']);
    if (!is_array($value)) {
      $value = [$value];
    }

    foreach ($value as $element_value) {
      // If nothing was selected.
      if (($element['#shs']['settings']['anyValue'] === $element_value)) {
        // Skip this value row and check the next one.
        if (!$element['#required']) {
          continue;
        }
        // Ensure there were options to select from before setting the error.
        elseif (count($element['#options']) > 1) {
          $form_state->setError($element, $message);
          return;
        }
      }
      elseif (shs_term_has_children($element_value)) {
        $form_state->setError($element, $message);
        return;
      }

    }
  }

  public static function setOptions(array &$element): void {
    if (!empty($element['#options'])) {
      return;
    }

    if (!\Drupal::moduleHandler()->moduleExists('taxonomy') || empty($element['#vocabulary'])) {
      $element['#options'] = [];
      return;
    }

    $element['#options'] = static::getOptions($element);
  }

  /**
   * Get options of all terms in given vocabulary.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   An associative array of term options.
   *
   * @throws InvalidPluginDefinitionException|PluginNotFoundException
   */
  protected static function getOptions(array $element) {
    return self::buildOptions($element['#vocabulary'], !empty($element['#cache_options']));
  }

  /**
   * Return an option cache ID for provided vocabulary.
   *
   * @param string $vid
   *   Vocabulary id.
   *
   * @return string
   *   Cache ID.
   */
  public static function getOptionsCacheId(string $vid): string {
    return 'webform_shs:options:' . $vid;
  }

  /**
   * Invalidate options cache for provided vocabulary.
   *
   * @param string $vid
   *   Vocabulary id.
   */
  public static function invalidateOptionsCache(string $vid): void {
    $cid = self::getOptionsCacheId($vid);
    \Drupal::cache()->invalidate($cid);
  }

  /**
   * Build a list of term options for provided vocabulary.
   *
   * @param string $vid
   *   Vocabulary id.
   * @param bool $cache_options
   *   Should we use cache?
   *
   * @return array
   *   An associative array of terms, where keys are tid and values are
   *   term name.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function buildOptions(string $vid, bool $cache_options = FALSE): array {
    if ($cache_options) {
      $options = self::buildCachedTermOptions($vid);
    }
    else {
      $options = self::buildTermOptions($vid);
    }

    return $options;
  }

  /**
   * Build a cached list of term options for provided vocabulary.
   *
   * @param string $vid
   *   Vocabulary id.
   *
   * @return array
   *   Cached list of term options.
   *
   * @throws InvalidPluginDefinitionException|PluginNotFoundException
   */
  protected static function buildCachedTermOptions(string $vid): array
  {
    $cid = self::getOptionsCacheId($vid);

    if ($cache = \Drupal::cache()->get($cid)) {
      $options = $cache->data;
    }
    else {
      $options = self::buildTermOptions($vid);
      \Drupal::cache()->set($cid, $options, Cache::PERMANENT);
    }

    return $options;
  }

  /**
   * Build a list of term options for provided vocabulary.
   *
   * @param string $vid
   *   Vocabulary id.
   *
   * @return array
   *   List of term options.
   *
   * @throws InvalidPluginDefinitionException|PluginNotFoundException
   */
  protected static function buildTermOptions(string $vid): array {
    $options = [];

    /** @var TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $taxonomy_storage->loadTree($vid);

    foreach ($terms as $item) {
      $options[$item->tid] = $item->name;
    }

    return $options;
  }

}
