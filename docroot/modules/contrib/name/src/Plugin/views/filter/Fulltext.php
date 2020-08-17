<?php

namespace Drupal\name\Plugin\views\filter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by fulltext search.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("name_fulltext")
 */
class Fulltext extends FilterPluginBase {

  /**
   * Supported operations.
   */
  protected function operators() {
    return [
      'contains' => [
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'op_contains',
        'values' => 1,
      ],
      'word' => [
        'title' => $this->t('Contains any word'),
        'short' => $this->t('has word'),
        'method' => 'op_word',
        'values' => 1,
      ],
      'allwords' => [
        'title' => $this->t('Contains all words'),
        'short' => $this->t('has all'),
        'method' => 'op_word',
        'values' => 1,
      ],
    ];
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
   * Provide a simple textfield for equality.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $this->value,
      '#attributes' => ['title' => $this->t('Enter the name you wish to search for.')],
      '#title' => $this->isExposed() ? '' : $this->t('Value'),
    ];
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    // Don't filter on empty strings.
    if (empty($this->value[0])) {
      return;
    }
    $field = "$this->tableAlias.$this->realField";
    $fulltext_field = "LOWER(CONCAT(' ', COALESCE({$field}_title, ''), ' ', COALESCE({$field}_given, ''), ' ', COALESCE({$field}_middle, ''), ' ', COALESCE({$field}_family, ''), ' ', COALESCE({$field}_generational, ''), ' ', COALESCE({$field}_credentials, '')))";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($fulltext_field);
    }
  }

  /**
   * Contains operation.
   *
   * @param string $fulltext_field
   *   The db field.
   */
  public function op_contains($fulltext_field) {
    $value = Unicode::strtolower($this->value[0]);
    $value = str_replace(' ', '%', $value);
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$fulltext_field LIKE $placeholder", [$placeholder => '% ' . $value . '%']);
  }

  /**
   * The word operation.
   *
   * @param string $fulltext_field
   *   The db field.
   */
  public function op_word($fulltext_field) {
    $where = $this->operator == 'word' ? db_or() : db_and();
    $value = Unicode::strtolower($this->value[0]);

    $words = preg_split('/ /', $value, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words as $word) {
      $placeholder = $this->placeholder();
      $where->where("$fulltext_field LIKE $placeholder", [$placeholder => '% ' . db_like($word) . '%']);
    }

    $this->query->addWhere($this->options['group'], $where);
  }

}
