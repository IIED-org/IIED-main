<?php

namespace Drupal\views_json_source\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base filter handler for views_json_source.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_json_source_filter")
 */
class ViewsJsonFilter extends FilterPluginBase {

  /**
   * Option definition.
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['key'] = ['default' => ''];
    return $options;
  }

  /**
   * Operators.
   *
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  public function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'short' => $this->t('!='),
        'method' => 'opEqual',
        'values' => 1,
      ],
      'contains' => [
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'opContains',
        'values' => 1,
      ],
      'word' => [
        'title' => $this->t('Contains any word'),
        'short' => $this->t('has word'),
        'method' => 'opContainsWord',
        'values' => 1,
      ],
      'allwords' => [
        'title' => $this->t('Contains all words'),
        'short' => $this->t('has all'),
        'method' => 'opContainsWord',
        'values' => 1,
      ],
      'starts' => [
        'title' => $this->t('Starts with'),
        'short' => $this->t('begins'),
        'method' => 'opStartsWith',
        'values' => 1,
      ],
      'not_starts' => [
        'title' => $this->t('Does not start with'),
        'short' => $this->t('not_begins'),
        'method' => 'opNotStartsWith',
        'values' => 1,
      ],
      'ends' => [
        'title' => $this->t('Ends with'),
        'short' => $this->t('ends'),
        'method' => 'opEndsWith',
        'values' => 1,
      ],
      'not_ends' => [
        'title' => $this->t('Does not end with'),
        'short' => $this->t('not_ends'),
        'method' => 'opNotEndsWith',
        'values' => 1,
      ],
      'not' => [
        'title' => $this->t('Does not contain'),
        'short' => $this->t('!has'),
        'method' => 'opNotLike',
        'values' => 1,
      ],
      'shorterthan' => [
        'title' => $this->t('Length is shorter than'),
        'short' => $this->t('shorter than'),
        'method' => 'opShorterThan',
        'values' => 1,
      ],
      'longerthan' => [
        'title' => $this->t('Length is longer than'),
        'short' => $this->t('longer than'),
        'method' => 'opLongerThan',
        'values' => 1,
      ],
      'regular_expression' => [
        'title' => $this->t('Regular expression'),
        'short' => $this->t('regex'),
        'method' => 'opRegex',
        'values' => 1,
      ],
    ];
    // If the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'not empty' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  /**
   * Build strings from the operators() for 'select' options.
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }
    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // Exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(1)) ? 'value' : 'none';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    if ($which == 'all' || $which == 'value') {
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#size' => 30,
        '#default_value' => $this->value,
      ];
      if (!empty($this->options['expose']['placeholder'])) {
        $form['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
      }
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }

      if ($which == 'all') {
        // Setup #states for all operators with one value.
        foreach ($this->operatorValues(1) as $operator) {
          $form['value']['#states']['visible'][] = [
            $source => ['value' => $operator],
          ];
        }
      }
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = [
        '#type' => 'value',
        '#value' => NULL,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorValues($values = 1) {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * Options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['key'] = [
      '#title' => $this->t('Key Chooser'),
      '#description' => $this->t('choose a key'),
      '#type' => 'textfield',
      '#default_value' => $this->options['key'],
      '#required' => TRUE,
    ];
  }

  /**
   * Add this filter to the query.
   */
  public function query() {
    $this->query->addFilter($this);
  }

  /**
   * Generate the filter criteria.
   */
  public function generate() {
    $operator = $this->options['operator'];
    $key = $this->options['key'];
    $value = !empty($this->value) ? reset($this->value) : $this->options['value'];

    return [$key, $operator, $value];
  }

}
