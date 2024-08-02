<?php

namespace Drupal\views_json_source\Plugin\views\field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Base field handler for views_json_source.
 *
 * @ViewsField("views_json_source_field")
 */
class ViewsJsonField extends FieldPluginBase {

  /**
   * The table alias.
   *
   * @var string
   */
  public $tableAlias = '';

  /**
   * Render.
   */
  public function render(ResultRow $values) {
    $key = $this->field_alias;

    if (!isset($values->$key)) {
      return '';
    }

    $values = $values->$key;

    return $this->renderField($values);
  }

  /**
   * Renders field.
   */
  public function renderField($value) {
    if ($this->options['trusted_html'] == 1) {
      return [
        '#markup' => Markup::create($value),
      ];
    }
    else {
      return Html::escape($value);
    }
  }

  /**
   * Option definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['key'] = ['default' => ''];
    $options['trusted_html'] = ['default' => 0];
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
    $form['trusted_html'] = [
      '#title' => $this->t('Trusted HTML'),
      '#description' => $this->t('This field is from a trusted source and contains raw HTML markup to render here. Use with caution.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['trusted_html'],
    ];
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // Add the field.
    $this->tableAlias = 'json';

    $this->field_alias = $this->query->addField(
      $this->tableAlias,
      $this->options['key'],
      '',
      $this->options
    );
  }

  /**
   * Called to determine what to tell the clicksorter.
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      $this->query->addOrderBy(NULL, $this->field_alias, $order);
    }
  }

}
