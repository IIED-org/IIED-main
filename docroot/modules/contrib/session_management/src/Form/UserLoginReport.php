<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\session_management\Utilities;

class UserLoginReport extends ConfigFormBase
{

  public const SETTINGS = 'session_management.settings';

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a UserLoginReport object.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PagerManagerInterface $pager_manager, Connection $database, RendererInterface $renderer)
  {
    $this->pagerManager = $pager_manager;
    $this->database = $database;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('pager.manager'),
      $container->get('database'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId()
  {
    return 'user-login-report';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['libraries'] = [
      '#attached' => [
        'library' => [
          "session_management/session_management.mo_session",
        ],
      ],
    ];

    $premium_tag = '<a href="' . Url::fromRoute('session_management.licensing_form')->toString() . '" target="_blank" rel="noopener noreferrer"><b>[Premium]</b></a>';
    $form['#disabled'] = true;

    // Filter Section
    $form['filters'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#markup' => '<div class="session-report-filters-container">',
      '#description' => 'Below is sample data for the report, as this is a ' . $premium_tag . ' feature. Please upgrade to view the actual data.',
    ];

    // Name Autocomplete
    $form['filters']['name'] = [
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('name', ''),
      '#prefix' => '<div class="session-report-filters"><div class="child-filter-item">',
      '#suffix' => '</div>',
      '#attributes' => [
        'placeholder' => $this->t('Enter a comma-separated list of user names.'),
        'style' => 'font-size: revert;',
      ]
    ];

    // IP Address
    $form['filters']['ip_address'] = [
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('ip_address', ''),
      '#prefix' => '<div class="child-filter-item">',
      '#suffix' => '</div>',
      '#attributes' => [
        'placeholder' => $this->t('Enter an IP address.'),
        'style' => 'font-size: revert;',
      ]
    ];

    $form['filters']['sort_by'] = [
      '#type' => 'select',
      '#options' => [
        'login' => $this->t('Login'),
        'logout' => $this->t('Logout'),
        'ip_address' => $this->t('IP Address'),
        'user_agent' => $this->t('User Agent'),
      ],
      '#default_value' => 'login',
    ];

    // Submit button
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#submit' => ['::applyFilters'],
      '#prefix' => '<div class="child-filter-button">',
      '#suffix' => '</div>',
    ];

    // Reset button
    $form['filters']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetFilters'],
      '#prefix' => '<div class="child-filter-button">',
      '#suffix' => '</div></div>',
    ];

    // Get table data
    $table_data = $this->getTableData($form_state);

    // Table structure
    $header = [
      'name' => $this->t('Name'),
      'ip_address' => $this->t('IP Address'),
      'browser' => $this->t('Browser/Device'),
      'login' => $this->t('Login'),
      'logout' => $this->t('Logout'),
    ];

    $form['session_report_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $table_data['rows'],
      '#empty_text' => $this->t('No records found.'),
      '#sticky' => TRUE,
      '#responsive' => TRUE,
      '#attributes' => [
        'class' => ['session-report-table'],
      ],
    ];

    // Add pager
    $form['pager'] = [
      '#type' => 'pager',
      '#quantity' => 5,
    ];

    Utilities::addSupportButton($form, $form_state);

    return $form;
  }

  /**
   * Get table data with pagination.
   */
  protected function getTableData(FormStateInterface $form_state)
  {
    // Get current page from pager
    $current_page = $this->pagerManager->createPager(0, 10)->getCurrentPage();
    $items_per_page = 10;

    // Get filter values
    $name_filter = $form_state->getValue('name', '');
    $ip_filter = $form_state->getValue('ip_address', '');

    // Example data - in real implementation, this would come from database
    $all_data = [
      [
        'name' => 'John Doe',
        'ip_address' => '192.168.1.1',
        'browser' => 'Chrome',
        'login' => '08/05/2024 03:05:15 PM',
        'logout' => '09/05/2024 01:55:20 AM'
      ],
      [
        'name' => 'Jane Smith',
        'ip_address' => '192.168.1.2',
        'browser' => 'Firefox',
        'login' => '09/05/2024 09:01:00 PM',
        'logout' => '09/05/2024 09:08:15 PM'
      ],
      [
        'name' => 'Bob Johnson',
        'ip_address' => '192.168.1.3',
        'browser' => 'Safari',
        'login' => '10/05/2024 10:30:00 AM',
        'logout' => '10/05/2024 11:45:30 AM'
      ],
      [
        'name' => 'Alice Brown',
        'ip_address' => '192.168.1.4',
        'browser' => 'Edge',
        'login' => '11/05/2024 02:15:45 PM',
        'logout' => '11/05/2024 04:20:10 PM'
      ],
      [
        'name' => 'Charlie Wilson',
        'ip_address' => '192.168.1.5',
        'browser' => 'Chrome',
        'login' => '12/05/2024 08:00:00 AM',
        'logout' => '12/05/2024 05:30:00 PM'
      ],
    ];

    // Apply filters
    $filtered_data = $all_data;
    if (!empty($name_filter)) {
      $filtered_data = array_filter($filtered_data, function ($item) use ($name_filter) {
        return stripos($item['name'], $name_filter) !== FALSE;
      });
    }
    if (!empty($ip_filter)) {
      $filtered_data = array_filter($filtered_data, function ($item) use ($ip_filter) {
        return stripos($item['ip_address'], $ip_filter) !== FALSE;
      });
    }

    // Apply pagination
    $total_items = count($filtered_data);
    $offset = $current_page * $items_per_page;
    $paged_data = array_slice($filtered_data, $offset, $items_per_page);

    // Initialize pager
    $this->pagerManager->createPager($total_items, $items_per_page);

    // Convert to table rows
    $rows = [];
    foreach ($paged_data as $item) {
      $rows[] = [
        'name' => $item['name'],
        'ip_address' => $item['ip_address'],
        'browser' => $item['browser'],
        'login' => $item['login'],
        'logout' => $item['logout']
      ];
    }

    return [
      'rows' => $rows,
      'total' => $total_items,
    ];
  }

  /**
   * Apply filters submit handler.
   */
  public function applyFilters(array &$form, FormStateInterface $form_state)
  {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Reset filters submit handler.
   */
  public function resetFilters(array &$form, FormStateInterface $form_state)
  {
    $form_state->setValue('name', '');
    $form_state->setValue('ip_address', '');
    $form_state->setRebuild(TRUE);
  }

}
