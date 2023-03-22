<?php

namespace Drupal\tablefield\Element;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a form element for tabular data.
 *
 * @FormElement("tablefield")
 */
class Tablefield extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#cols' => 5,
      '#rows' => 5,
      '#lock' => FALSE,
      '#locked_cells' => [],
      '#input_type' => 'textfield',
      '#rebuild' => FALSE,
      '#import' => FALSE,
      '#process' => [
        [$class, 'processTablefield'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#addrow' => FALSE,
      '#add_row' => 0,
    ];
  }

  /**
   * Processes a checkboxes form element.
   */
  public static function processTablefield(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#parents'];
    $value = is_array($element['#value']) ? $element['#value'] : [];

    // Check if the input_type is one of the allowed types.
    $input_type = in_array($element['#input_type'], ['textarea', 'textfield']) ? $element['#input_type'] : 'textfield';

    // String to uniquely identify DOM elements.
    $id = implode('-', $element['#parents']);

    // This is being set in rebuild and import ajax calls.
    $storage = NestedArray::getValue($form_state->getStorage(), $element['#parents']);
    // Fetch addrow value.
    if ($storage && isset($storage['tablefield']['rebuild'])) {
      $element['#cols'] = $storage['tablefield']['rebuild']['cols'];
      $element['#rows'] = $storage['tablefield']['rebuild']['rows'];
    }

    $element['#tree'] = TRUE;

    $element['tablefield'] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['form-tablefield']],
      '#prefix' => '<div id="tablefield-' . $id . '-wrapper">',
      '#suffix' => '</div>',
    ];

    $element['tablefield']['table'] = [
      '#type' => 'table',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-rows-weight',
        ],
      ],
    ];
    // Assign value.
    $rows = isset($element['#rows']) ? $element['#rows'] : \Drupal::config('tablefield.settings')->get('rows');
    $cols = isset($element['#cols']) ? $element['#cols'] : \Drupal::config('tablefield.settings')->get('cols');

    $table = $value['tablefield']['table'] ?? $value;
    $weightedRows = [];
    for ($i = 0; $i < $rows; $i++) {
      $weight = $table[$i]['weight'] ?? $i;
      $weightedRows[$i] = [
        // This element is required to give the drag handle something to attach
        // to. Normally this would just be the first column of the table, but
        // attempting to have the drag handle and textfield share a cell broke
        // the layout. Additionally it can't share with the 'weight' column
        // since that column is hidden and thus the drag handles would be too.
        'spacer' => ['#markup' => ''],
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => [
            'class' => ['table-rows-weight'],
          ],
          '#delta' => $rows,
        ],
        '#weight' => $weight,
      ];

      $draggable = TRUE;
      for ($ii = 0; $ii < $cols; $ii++) {
        if (!empty($element['#locked_cells'][$i][$ii]) && !empty($element['#lock'])) {
          $draggable = FALSE;
          $weightedRows[$i][$ii] = [
            '#type' => 'item',
            '#value' => $element['#locked_cells'][$i][$ii],
            '#title' => $element['#locked_cells'][$i][$ii],
          ];
        }
        else {
          $cell_value = isset($value[$i][$ii]) ? $value[$i][$ii] : '';
          $weightedRows[$i][$ii] = [
            '#type' => $input_type,
            '#maxlength' => 2048,
            '#size' => 0,
            '#attributes' => [
              'class' => ['tablefield-row-' . $i, 'tablefield-col-' . $ii],
              'style' => 'width:100%',
            ],
            '#default_value' => $cell_value,
          ];
        }
      }

      // Only allow the row to be dragged if it does not contain locked cells.
      // See https://www.drupal.org/project/tablefield/issues/2868077.
      if ($draggable) {
        $weightedRows[$i]['#attributes']['class'][] = 'draggable';
      }
    }

    // Sort rows by weight. This step is required so that the table stays
    // properly ordered when doing ajax operations.
    uasort($weightedRows, ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
    $element['tablefield']['table'] += $weightedRows;

    // To change number of rows.
    if (!empty($element['#addrow'])) {
      $element['tablefield']['addrow']['row_value'] = [
        '#title' => t('How many rows'),
        '#type' => 'hidden',
        '#default_value' => $rows,
        '#value' => $rows,
      ];
      $element['tablefield']['addrow']['addrow'] = [
        '#type' => 'submit',
        '#value' => t('Add Row'),
        '#name' => 'tablefield-addrow-' . $id,
        '#attributes' => [
          'class' => ['tablefield-addrow'],
        ],
        '#submit' => [[get_called_class(), 'submitCallbackRebuild']],
        '#limit_validation_errors' => [
          array_merge($parents, ['tablefield', 'rebuild', 'cols']),
          array_merge($parents, ['tablefield', 'rebuild', 'rows']),
          array_merge($parents, ['tablefield', 'rebuild', 'rebuild']),
        ],
        '#ajax' => [
          'callback' => 'Drupal\tablefield\Element\Tablefield::ajaxCallbackRebuild',
          'progress' => ['type' => 'throbber', 'message' => NULL],
          'wrapper' => 'tablefield-' . $id . '-wrapper',
          'effect' => 'fade',
        ],
      ];
    }

    // If no rebuild, we pass along the rows/cols as a value. Otherwise, we will
    // provide form elements to specify the size and ajax rebuild.
    if (empty($element['#rebuild'])) {
      $element['tablefield']['rebuild'] = [
        '#type' => 'value',
        'cols' => [
          '#type' => 'value',
          '#value' => $cols,
        ],
        'rows' => [
          '#type' => 'value',
          '#value' => $rows,
        ],
      ];
    }
    else {
      $element['tablefield']['rebuild'] = [
        '#type' => 'details',
        '#title' => t('Change number of rows/columns.'),
        '#open' => FALSE,
      ];
      $element['tablefield']['rebuild']['cols'] = [
        '#title' => t('How many Columns'),
        '#type' => 'number',
        '#size' => 5,
        '#default_value' => $cols,
        '#min' => 1,
      ];

      $element['tablefield']['rebuild']['rows'] = [
        '#title' => t('How many Rows'),
        '#type' => 'number',
        '#size' => 5,
        '#default_value' => $rows,
        '#min' => 1,
      ];
      $element['tablefield']['rebuild']['rebuild'] = [
        '#type' => 'submit',
        '#value' => t('Rebuild Table'),
        '#name' => 'tablefield-rebuild-' . $id,
        '#attributes' => [
          'class' => ['tablefield-rebuild'],
        ],
        '#submit' => [[get_called_class(), 'submitCallbackRebuild']],
        '#limit_validation_errors' => [
          array_merge($parents, ['tablefield', 'rebuild', 'cols']),
          array_merge($parents, ['tablefield', 'rebuild', 'rows']),
          array_merge($parents, ['tablefield', 'rebuild', 'rebuild']),
        ],
        '#ajax' => [
          'callback' => 'Drupal\tablefield\Element\Tablefield::ajaxCallbackRebuild',
          'progress' => ['type' => 'throbber', 'message' => NULL],
          'wrapper' => 'tablefield-' . $id . '-wrapper',
          'effect' => 'fade',
        ],
      ];
    }

    // Allow import of a csv file.
    if (!empty($element['#import'])) {
      $element['tablefield']['import'] = [
        '#type' => 'details',
        '#title' => t('Import from CSV'),
        '#open' => FALSE,
      ];
      $element['tablefield']['import']['csv'] = [
        '#name' => 'files[' . $id . ']',
        '#title' => 'File upload',
        '#type' => 'file',
      ];

      $element['tablefield']['import']['import'] = [
        '#type' => 'submit',
        '#value' => t('Upload CSV'),
        '#name' => 'tablefield-import-' . $id,
        '#attributes' => [
          'class' => ['tablefield-rebuild'],
        ],
        '#submit' => [[get_called_class(), 'submitCallbackRebuild']],
        '#limit_validation_errors' => [
          array_merge($parents, ['tablefield', 'import', 'csv']),
          array_merge($parents, ['tablefield', 'import', 'import']),
        ],
        '#ajax' => [
          'callback' => 'Drupal\tablefield\Element\Tablefield::ajaxCallbackRebuild',
          'progress' => ['type' => 'throbber', 'message' => NULL],
          'wrapper' => 'tablefield-' . $id . '-wrapper',
          'effect' => 'fade',
        ],
      ];
    }

    return $element;
  }

  /**
   * AJAX callback to rebuild the number of rows/columns.
   *
   * The basic idea is to descend down the list of #parent elements of the
   * triggering_element in order to locate the tablefield inside of the $form
   * array.
   *
   * That is the element that we need to return.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function ajaxCallbackRebuild(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Go as deep as 'tablefield' key, but stop there (two more keys follow).
    $parents = array_slice($triggering_element['#array_parents'], 0, -2, TRUE);
    $rebuild = NestedArray::getValue($form, $parents);

    // We don't want to re-send the format/_weight options.
    unset($rebuild['format']);
    unset($rebuild['_weight']);

    // Set row value to default only if there is Add Row button clicked.
    $op = (string) $triggering_element['#value'];
    if ($op === 'Add Row') {
      $rebuild['rebuild']['rows']['#value'] = $rebuild['rebuild']['rows']['#default_value'];
    }

    return $rebuild;
  }

  /**
   * Submit handler.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function submitCallbackRebuild(array $form, FormStateInterface $form_state) {
    // Check what triggered this. We might need to rebuild or to import.
    $triggering_element = $form_state->getTriggeringElement();

    $id = implode('-', array_slice($triggering_element['#parents'], 0, -3, TRUE));
    $parents = array_slice($triggering_element['#parents'], 0, -2, TRUE);
    $value = $form_state->getValue($parents);

    if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield-rebuild-' . $id || isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield-addrow-' . $id) {
      $parents[] = 'rebuild';
      if (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield-addrow-' . $id) {
        $value['rebuild']['rows']++;
      }
      NestedArray::setValue($form_state->getStorage(), $parents, $value['rebuild']);

      \Drupal::messenger()->addStatus(t('Table structure rebuilt.'), FALSE);
    }
    elseif (isset($triggering_element['#name']) && $triggering_element['#name'] == 'tablefield-import-' . $id) {
      // Import CSV.
      $imported_tablefield = static::importCsv($id);

      if ($imported_tablefield) {
        $form_state->setValue($parents, $imported_tablefield);

        $input = $form_state->getUserInput();
        NestedArray::setValue($input, $parents, $imported_tablefield);
        $form_state->setUserInput($input);

        $parents[] = 'rebuild';
        NestedArray::setValue($form_state->getStorage(), $parents, $imported_tablefield['rebuild']);
      }
    }

    $form_state->setRebuild();
  }

  /**
   * Helper function to import data from a CSV file.
   *
   * @param string $form_field_name
   *   Field name.
   *
   * @return mixed
   *   Table array or FALSE.
   */
  private static function importCsv($form_field_name) {
    $files = \Drupal::request()->files->get('files');
    $file_upload = $files[$form_field_name];

    if (empty($file_upload)) {
      \Drupal::messenger()->addError(t('Select a CSV file to upload.'));
      return FALSE;
    }

    if ($file_upload->getClientOriginalExtension() != 'csv') {
      \Drupal::messenger()->addError(t('Only files with the following extensions are allowed: %files-allowed.', ['%files-allowed' => 'csv']));
      return FALSE;
    }

    if (!empty($file_upload) && $handle = fopen($file_upload->getPathname(), 'r')) {
      // Checking the encoding of the CSV file to be UTF-8.
      $encoding = 'UTF-8';
      if (function_exists('mb_detect_encoding')) {
        $file_contents = file_get_contents($file_upload->getPathname());
        $encodings = ['UTF-8', 'ISO-8859-1', 'WINDOWS-1251'];
        \Drupal::moduleHandler()->alter('tablefield_encodings', $encodings);
        $encodings_list = implode(',', $encodings);
        $encoding = mb_detect_encoding($file_contents, $encodings_list);
      }

      // Populate CSV values.
      $tablefield = [];
      $max_cols = 0;
      $rows = 0;

      $separator = \Drupal::config('tablefield.settings')->get('csv_separator');
      while (($csv = fgetcsv($handle, 0, $separator)) != FALSE) {
        foreach ($csv as $value) {
          $tablefield['table'][$rows][] = self::convertEncoding($value, $encoding);
        }
        $cols = count($csv);
        if ($cols > $max_cols) {
          $max_cols = $cols;
        }
        $rows++;
      }
      fclose($handle);

      $tablefield['rebuild']['cols'] = $max_cols;
      $tablefield['rebuild']['rows'] = $rows;

      \Drupal::messenger()->addMessage(t('Successfully imported @file', ['@file' => $file_upload->getClientOriginalName()]));
      return $tablefield;
    }

    \Drupal::messenger()->addError(t('There was a problem importing @file.', ['@file' => $file_upload->getClientOriginalName()]));
    return FALSE;
  }

  /**
   * Helper function to detect and convert strings not in UTF-8 to UTF-8.
   *
   * @param string $data
   *   The string which needs converting.
   * @param string $encoding
   *   The encoding of the CSV file.
   *
   * @return string
   *   UTF encoded string.
   */
  protected static function convertEncoding($data, $encoding) {
    // Converting UTF-8 to UTF-8 will not work.
    if ($encoding == 'UTF-8') {
      return $data;
    }

    // Try convert the data to UTF-8.
    if ($encoded_data = Unicode::convertToUtf8($data, $encoding)) {
      return $encoded_data;
    }

    // Fallback on the input data.
    return $data;
  }

}
