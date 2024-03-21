<?php

namespace Drupal\give\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;

/**
 * A field that displays entity timestamp field data. Supports grouping.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_date_format_sql_field")
 *
 * @note: lifted from unmaintained drupal.org/project/views_date_format_sql
 */
class ViewsDateFormatSqlField extends EntityField {

  private $format;
  private $format_string;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format_date_sql'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['format_date_sql'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use SQL to format date'),
      '#description' => $this->t('Use the SQL databse to format the date. This enables date values to be used in grouping aggregation.'),
      '#default_value' => $this->options['format_date_sql'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Called to add the field to a query.
   *
   * By default, all needed data is taken from entities loaded by the query
   * plugin. Columns are added only if they are used in groupings.
   */
  public function query($use_groupby = FALSE) {
    if (empty($this->options['format_date_sql'])) {
      return parent::query($use_groupby);
    }

    $fields = $this->additional_fields;
    // No need to add the entity type.
    $entity_type_key = array_search('entity_type', $fields);
    if ($entity_type_key !== FALSE) {
      unset($fields[$entity_type_key]);
    }

    if ($use_groupby) {
      // Add the fields that we're actually grouping on.
      $options = array();
      if ($this->options['group_column'] != 'entity_id') {
        $options = array($this->options['group_column'] => $this->options['group_column']);
      }
      $options += is_array($this->options['group_columns']) ? $this->options['group_columns'] : array();

      // Go through the list and determine the actual column name from field api.
      $fields = array();
      $table_mapping = $this->getTableMapping();
      $field_definition = $this->getFieldStorageDefinition();

      foreach ($options as $column) {
        $fields[$column] = $table_mapping->getFieldColumnName($field_definition, $column);
      }

      $this->group_fields = $fields;
    }

    // Add additional fields (and the table join itself) if needed.
    $this->add_field_table($use_groupby);
    $this->ensureMyTable();
    $this->setDateFormat();

    // Add the field.
    $params = $this->options['group_type'] !== 'group' ? array('function' => $this->options['group_type']) : array();

    $formula = $this->query->getDateFormat("FROM_UNIXTIME($this->tableAlias.$this->realField)", $this->format_string);

    $this->field_alias = $this->query->addField(NULL, $formula, "{$this->tableAlias}_{$this->realField}", $params);
    $this->query->addGroupBy($this->field_alias);

    $this->aliases[$this->definition['field_name']] = $this->field_alias;
    $this->setDateFormat();

    // Let the entity field renderer alter the query if needed.
    $this->getEntityFieldRenderer()->query($this->query, $this->relationship);
  }

  /**
   * Sets date format from field options.
   */
  protected function setDateFormat() {
    if (empty($this->options['settings']['date_format'])) {
      $this->format = '';
      $this->format_string = '';
      return;
    }

    $this->format = $this->options['settings']['date_format'];
    if ($this->format === 'custom') {
      $this->format_string = !empty($this->options['settings']['custom_date_format'])
          ? $this->options['settings']['custom_date_format']
          : '';
    }
    else {
      /* @var DateFormat $formatter */
      $formatter = DateFormat::load($this->format);
      $this->format_string = empty($formatter) ? '' : $formatter->getPattern();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    if (empty($this->options['format_date_sql'])) {
      return parent::getValue($values, $field);
    }

    $entity = $this->getEntity($values);
    // Some bundles might not have a specific field, in which case the entity
    // (potentially a fake one) doesn't have it either.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
    $field_item_list = isset($entity->{$this->definition['field_name']}) ? $entity->{$this->definition['field_name']} : NULL;

    if (!isset($field_item_list)) {
      // Check empty date field for "empty rows".
      if (!empty($values->is_empty_row)) {
        // Render empty date.
        if (isset($this->field_alias) && !empty($values->{$this->field_alias})) {
          return $values->{$this->field_alias};
        }
      }

      // There isn't anything we can do without a valid field.
      return NULL;
    }

    $field_item_definition = $field_item_list->getFieldDefinition();

    $values = [];
    foreach ($field_item_list as $field_item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
      if ($field) {
        $values[] = $field_item->$field;
      }
      // Find the value using the main property of the field. If no main
      // property is provided fall back to 'value'.
      elseif ($main_property_name = $field_item->mainPropertyName()) {
        $values[] = $field_item->{$main_property_name};
      }
      else {
        $values[] = $field_item->value;
      }
    }

    /* @var DateFormatter $dateFormatter */
    $dateFormatter = \Drupal::service('date.formatter');

    if ($field_item_definition->getFieldStorageDefinition()->getCardinality() == 1) {
      $timestamp = reset($values);

      if (empty($this->format)) {
        return $timestamp;
      }

      return $dateFormatter->format($timestamp, $this->format, $this->format_string);
    }
    else {
      if (empty($this->format)) {
        return $values;
      }

      foreach ($values as &$value) {
        $value = $dateFormatter->format($value, $this->format, $this->format_string);
      }
      return $values;
    }
  }

  /**
   * Called to determine what to tell the clicksorter.
   */
  public function clickSort($order) {
    if (empty($this->options['format_date_sql'])) {
      return parent::clickSort($order);
    }

    // No column selected, can't continue.
    if (empty($this->options['click_sort_column'])) {
      return NULL;
    }

    $this->ensureMyTable();
    $field_storage_definition = $this->getFieldStorageDefinition();
    $column = $this->getTableMapping()->getFieldColumnName($field_storage_definition, $this->options['click_sort_column']);
    if (!isset($this->aliases[$column])) {
      // Column is not in query; add a sort on it (without adding the column).
      $this->aliases[$column] = $this->tableAlias . '.' . $column;
    }
    $this->query->addOrderBy(NULL, NULL, $order, $this->aliases[$column]);

    // Added to group ungrouped "timestamp" fields. Can occurs when
    // $this->addAdditionalFields is called with NULL $fields param.
    if ($this->view->display_handler->useGroupBy()
      && array_search($this->aliases[$column], $this->query->groupby, TRUE) === FALSE
    ) {
      $this->query->addGroupBy($this->aliases[$column]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    if (empty($this->options['format_date_sql'])) {
      return parent::getItems($values);
    }

    if (!$this->displayHandler->useGroupBy()) {
      $build_list = $this->getEntityFieldRenderer()->render($values, $this);
    }
    else {
      // Render date values from SQL result.
      $alias = $this->aliases[$this->definition['field_name']];
      //return [['rendered' => $values->{$alias}]];
      return [['rendered' => ['#markup' => Markup::create($values->{$alias})]]];
    }
    // Code from parent function.
    if (!$build_list) {
      return [];
    }

    if ($this->options['field_api_classes']) {
      return [['rendered' => $this->renderer->render($build_list)]];
    }

    // Render using the formatted data itself.
    $items = [];
    // Each item is extracted and rendered separately, the top-level formatter
    // render array itself is never rendered, so we extract its bubbleable
    // metadata and add it to each child individually.
    $bubbleable = BubbleableMetadata::createFromRenderArray($build_list);
    foreach (Element::children($build_list) as $delta) {
      BubbleableMetadata::createFromRenderArray($build_list[$delta])
        ->merge($bubbleable)
        ->applyTo($build_list[$delta]);
      $items[$delta] = [
        'rendered' => $build_list[$delta],
        // Add the raw field items (for use in tokens).
        'raw' => $build_list['#items'][$delta],
      ];
    }
    return $items;
  }

}
