<?php

declare(strict_types=1);

namespace Drupal\views_bulk_operations\Traits;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Defines the Views UI options-form methods for the VBO bulk form field.
 */
trait ViewsBulkOperationsOptionsFormTrait {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['batch'] = ['default' => TRUE];
    $options['batch_size'] = ['default' => 10];
    $options['form_step'] = ['default' => TRUE];
    $options['ajax_loader'] = ['default' => FALSE];
    $options['buttons'] = ['default' => FALSE];
    $options['clear_on_exposed'] = ['default' => TRUE];
    $options['action_title'] = ['default' => $this->t('Action')];
    $options['selected_actions'] = ['default' => []];
    $options['show_multipage_selection_box'] = ['default' => 'default'];
    $options['show_select_all'] = ['default' => 'default'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(mixed &$form, FormStateInterface $form_state): void {
    // If the view type is not supported, suppress form display.
    // Also display information note to the user.
    if (\count($this->actions) === 0) {
      $form = [
        '#type' => 'item',
        '#title' => $this->t('NOTE'),
        '#markup' => $this->t('Views Bulk Operations will work only with normal entity views and contrib module views that are integrated. See \Drupal\views_bulk_operations\EventSubscriber\ViewsBulkOperationsEventSubscriber class for integration best practice.'),
        '#prefix' => '<div class="scroll">',
        '#suffix' => '</div>',
      ];
      parent::buildOptionsForm($form, $form_state);
      return;
    }

    $form['#attributes']['class'][] = 'views-bulk-operations-ui';
    $form['#attached']['library'][] = 'views_bulk_operations/adminUi';

    $form['batch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process in a batch operation'),
      '#default_value' => $this->options['batch'],
    ];

    $form['batch_size'] = [
      '#title' => $this->t('Batch size'),
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('Only applicable if results are processed in a batch operation.'),
      '#default_value' => $this->options['batch_size'],
    ];

    $form['form_step'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Configuration form on new page (configurable actions)'),
      '#default_value' => $this->options['form_step'],
      // Due to #2879310 this setting must always be at TRUE.
      '#access' => FALSE,
    ];

    $form['ajax_loader'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show ajax throbber.'),
      '#description' => $this->t('With this enabled, a throbber will be shown when an ajax petition from VBO is triggered.'),
      '#default_value' => $this->options['ajax_loader'],
    ];

    $form['buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display selectable actions as buttons.'),
      '#default_value' => $this->options['buttons'],
    ];

    $form['clear_on_exposed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear selection when exposed filters change.'),
      '#description' => $this->t('With this enabled, selection will be cleared every time exposed filters are changed, select all will select all rows with exposed filters applied and view total count will take exposed filters into account. When disabled, select all selects all results in the view with empty exposed filters and one can change exposed filters while selecting rows without the selection being lost.'),
      '#default_value' => $this->options['clear_on_exposed'],
    ];

    $form['show_multipage_selection_box'] = [
      '#type' => 'select',
      '#title' => $this->t('Show an "Items selected" details element'),
      '#description' => $this->t('The default behavior shows this control when there are multiple pages of results, or when VBO is not configured to clear selected items when exposed filters are changed and exposed filters are set.'),
      '#default_value' => $this->options['show_multipage_selection_box'],
      '#options' => [
        'default' => $this->t('Default'),
        'always_show' => $this->t('Always show'),
        'always_hide' => $this->t('Always hide'),
      ],
    ];

    $form['show_select_all'] = [
      '#type' => 'select',
      '#title' => $this->t('Show a "Select / Deselect all results (all pages)" checkbox'),
      '#description' => $this->t('The default behavior shows this checkbox when Format is not set to Table, when there are multiple pages of results, or when VBO is not configured to clear selected items when exposed filters are changed and exposed filters are set.'),
      '#default_value' => $this->options['show_select_all'],
      '#options' => [
        'default' => $this->t('Default'),
        'always_show' => $this->t('Always show'),
        'always_hide' => $this->t('Always hide'),
      ],
    ];

    $form['action_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action title'),
      '#default_value' => $this->options['action_title'],
      '#description' => $this->t('The title shown above the actions dropdown.'),
    ];

    $form['selected_actions'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Selected actions'),
      '#attributes' => ['class' => ['vbo-actions-widget']],
    ];

    // Load values for display.
    $form_values = $form_state->getValue(['options', 'selected_actions']);
    if (\is_null($form_values)) {
      $config_data = $this->options['selected_actions'];
      $selected_actions_data = [];
      foreach ($config_data as $item) {
        $selected_actions_data[$item['action_id']] = $item;
      }
    }
    else {
      $selected_actions_data = $form_values;
    }

    $table = [
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Weight'),
        $this->t('Title'),
      ],
      '#attributes' => [
        'id' => 'my-module-table',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'draggable-weight',
        ],
      ],
    ];

    // Set weights on actions - selected ones will always be first.
    $weight = -1000;
    foreach ($selected_actions_data as $id => $item) {
      if (!array_key_exists($id, $this->actions)) {
        continue;
      }
      $this->actions[$id]['weight'] = $weight++;
    }
    uasort($this->actions, [SortArray::class, 'sortByWeightElement']);

    $delta = 0;
    foreach ($this->actions as $id => $action) {
      $table[$delta] = [
        'data' => [],
      ];
      $table[$delta]['#attributes']['class'] = ['draggable'];
      $table[$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $action['weight'] ?? 0,
        '#attributes' => [
          'class' => [
            'draggable-weight',
          ],
        ],
      ];

      $table[$delta]['container'] = [
        '#type' => 'container',
      ];

      $table[$delta]['container']['action_id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
      $table[$delta]['container']['state'] = [
        '#type' => 'checkbox',
        '#title' => $action['label'],
        '#default_value' => \array_key_exists($id, $selected_actions_data),
        '#attributes' => ['class' => ['vbo-action-state']],
      ];

      $table[$delta]['container']['preconfiguration'] = [
        '#type' => 'details',
        '#title' => $this->t('Preconfiguration for "@action"', [
          '@action' => $action['label'],
        ]),
        '#states' => [
          'visible' => [
            \sprintf('[name="options[selected_actions][table][%d][container][state]"]', $delta) => ['checked' => TRUE],
          ],
        ],
      ];

      // Default label and action processing message overrides.
      $table[$delta]['container']['preconfiguration']['label_override'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Override label'),
        '#description' => $this->t('Leave empty for the default label.'),
        '#default_value' => $selected_actions_data[$id]['preconfiguration']['label_override'] ?? '',
      ];
      $table[$delta]['container']['preconfiguration']['message_override'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Override processing message'),
        '#description' => $this->t('Use the "@count" placeholder for number of processed items. Leave empty for the default message.'),
        '#default_value' => $selected_actions_data[$id]['preconfiguration']['message_override'] ?? '',
      ];

      // Also allow to force a default confirmation step for actions that don't
      // have it implemented.
      if ($action['confirm_form_route_name'] === '') {
        $table[$delta]['container']['preconfiguration']['add_confirmation'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Add confirmation step'),
          '#default_value' => $selected_actions_data[$id]['preconfiguration']['add_confirmation'] ?? FALSE,
        ];
        $table[$delta]['container']['preconfiguration']['confirm_help_text'] = [
          '#type' => 'textarea',
          '#rows' => 2,
          '#title' => $this->t('Confirmation step help text'),
          '#default_value' => $selected_actions_data[$id]['preconfiguration']['confirm_help_text'] ?? FALSE,
          '#description' => $this->t('Available placeholders: @placeholders.', [
            '@placeholders' => implode(', ', ['%action', '%count']),
          ]),
          '#states' => [
            'visible' => [
              ':input[name="options[selected_actions][table][' . $delta . '][container][preconfiguration][add_confirmation]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }

      // Load preconfiguration form if available.
      if (\method_exists($action['class'], 'buildPreConfigurationForm')) {
        if (
          !\array_key_exists($id, $selected_actions_data) ||
          !\array_key_exists('preconfiguration', $selected_actions_data[$id])
        ) {
          $selected_actions_data[$id]['preconfiguration'] = [];
        }
        $actionObject = $this->actionManager->createInstance($id);

        // Set the view so the configuration form can access to it.
        if ($this->view instanceof ViewExecutable) {
          if ($this->view->inited !== TRUE) {
            $this->view->initHandlers();
          }
          $actionObject->setView($this->view);
        }
        $table[$delta]['container']['preconfiguration'] = $actionObject->buildPreConfigurationForm($table[$delta]['container']['preconfiguration'], $selected_actions_data[$id]['preconfiguration'], $form_state);
      }

      $delta++;
    }
    $form['selected_actions']['table'] = $table;

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $selected_actions = &$form_state->getValue(['options', 'selected_actions']);
    if ($selected_actions === NULL) {
      return;
    }
    $selected_actions = $selected_actions['table'];
    $selected_actions = \array_filter($selected_actions, static fn ($action_data) => $action_data['container']['state'] !== 0);

    foreach ($selected_actions as &$item) {
      unset($item['weight']);
      $item = array_merge($item, $item['container']);
      unset($item['state']);
      unset($item['container']);
    }
    $selected_actions = array_values($selected_actions);
    parent::submitOptionsForm($form, $form_state);
  }

}
