<?php

namespace Drupal\search_api_autocomplete_test\Plugin\search_api_autocomplete\search;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api_autocomplete\Attribute\SearchApiAutocompleteSearch;
use Drupal\search_api_autocomplete\Search\SearchPluginBase;
use Drupal\search_api_test\TestPluginTrait;

/**
 * Defines a test search plugin class.
 */
#[SearchApiAutocompleteSearch(
  id: 'search_api_autocomplete_test',
  label: new TranslatableMarkup('Autocomplete test module search'),
  description: new TranslatableMarkup('Test autocomplete search'),
  group_label: new TranslatableMarkup('Test search'),
  group_description: new TranslatableMarkup('Search used for tests.'),
  index: 'autocomplete_search_index',
)]
class TestSearch extends SearchPluginBase implements PluginFormInterface {

  use TestPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $form, $form_state);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      call_user_func($override, $this, $form, $form_state);
      return;
    }
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery($keys, array $data = []) {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this, $keys, $data);
    }
    return $this->search->getIndex()->query()->keys($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->logMethodCall(__FUNCTION__, func_get_args());
    if ($override = $this->getMethodOverride(__FUNCTION__)) {
      return call_user_func($override, $this);
    }
    if (!empty($this->configuration['dependencies'])) {
      return $this->configuration['dependencies'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $remove = $this->getReturnValue(__FUNCTION__, FALSE);
    if ($remove) {
      unset($this->configuration['dependencies']);
    }
    return $remove;
  }

}
