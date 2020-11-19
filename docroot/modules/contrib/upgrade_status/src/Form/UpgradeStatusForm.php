<?php

namespace Drupal\upgrade_status\Form;

use Composer\Semver\Semver;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\upgrade_status\DeprecationAnalyzer;
use Drupal\upgrade_status\ProjectCollector;
use Drupal\upgrade_status\ScanResultFormatter;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class UpgradeStatusForm extends FormBase {

  /**
   * The project collector service.
   *
   * @var \Drupal\upgrade_status\ProjectCollector
   */
  protected $projectCollector;

  /**
   * Available releases store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|mixed
   */
  protected $releaseStore;

  /**
   * The scan result formatter service.
   *
   * @var \Drupal\upgrade_status\ScanResultFormatter
   */
  protected $resultFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The deprecation analyzer.
   *
   * @var \Drupal\upgrade_status\DeprecationAnalyzer
   */
  protected $deprecationAnalyzer;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $destination;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('upgrade_status.project_collector'),
      $container->get('keyvalue.expirable'),
      $container->get('upgrade_status.result_formatter'),
      $container->get('renderer'),
      $container->get('logger.channel.upgrade_status'),
      $container->get('module_handler'),
      $container->get('upgrade_status.deprecation_analyzer'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Constructs a Drupal\upgrade_status\Form\UpgradeStatusForm.
   *
   * @param \Drupal\upgrade_status\ProjectCollector $project_collector
   *   The project collector service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactory $key_value_expirable
   *   The expirable key/value storage.
   * @param \Drupal\upgrade_status\ScanResultFormatter $result_formatter
   *   The scan result formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\upgrade_status\DeprecationAnalyzer $deprecation_analyzer
   *   The deprecation analyzer.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Routing\RedirectDestination $destination
   *   The destination service.
   */
  public function __construct(
    ProjectCollector $project_collector,
    KeyValueExpirableFactory $key_value_expirable,
    ScanResultFormatter $result_formatter,
    RendererInterface $renderer,
    LoggerInterface $logger,
    ModuleHandler $module_handler,
    DeprecationAnalyzer $deprecation_analyzer,
    StateInterface $state,
    DateFormatter $date_formatter,
    RedirectDestination $destination
  ) {
    $this->projectCollector = $project_collector;
    $this->releaseStore = $key_value_expirable->get('update_available_releases');
    $this->resultFormatter = $result_formatter;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
    $this->deprecationAnalyzer = $deprecation_analyzer;
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_upgrade_status_summary_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'upgrade_status/upgrade_status.admin';

    $analyzer_ready = TRUE;
    try {
      $this->deprecationAnalyzer->initEnvironment();
    }
    catch (\Exception $e) {
      $analyzer_ready = FALSE;
      $this->messenger()->addError($e->getMessage());
    }

    $environment = $this->buildEnvironmentChecks();
    $form['summary'] = $this->buildResultSummary($environment['status']);
    unset($environment['status']);

    $form['environment'] = [
      '#type' => 'details',
      '#title' => $this->t('Drupal core and hosting environment'),
      '#description' => $this->t('<a href=":upgrade">Upgrades to Drupal 9 are supported from Drupal 8.8.x and Drupal 8.9.x</a>. It is suggested to update to the latest Drupal 8 version available. <a href=":platform">Several hosting platform requirements have been raised for Drupal 9</a>.', [':upgrade' => 'https://www.drupal.org/docs/9/how-to-prepare-your-drupal-7-or-8-site-for-drupal-9/upgrading-a-drupal-8-site-to-drupal-9', ':platform' => 'https://www.drupal.org/docs/9/how-drupal-9-is-made-and-what-is-included/environment-requirements-of-drupal-9']),
      '#open' => TRUE,
      '#attributes' => ['class' => ['upgrade-status-summary-environment']],
      'data' => $environment,
      '#tree' => TRUE,
    ];

    // Gather project list with metadata.
    $projects = $this->projectCollector->collectProjects();
    $next_steps = $this->projectCollector->getNextStepInfo();
    foreach ($next_steps as $next_step => $step_label) {
      $sublist = [];
      foreach ($projects as $name => $project) {
        if ($project->info['upgrade_status_next'] == $next_step) {
          $sublist[$name] = $project;
        }
      }
      if (!empty($sublist)) {
        $form[$next_step] = [
          '#type' => 'details',
          '#title' => $step_label[0],
          '#description' => $step_label[1],
          '#open' => TRUE,
          '#attributes' => ['class' => ['upgrade-status-summary', 'upgrade-status-next-step-' . $next_step]],
          'data' => $this->buildProjectList($sublist, $next_step),
          '#tree' => TRUE,
        ];
      }
    }

    $form['drupal_upgrade_status_form']['action']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Scan selected'),
      '#weight' => 2,
      '#button_type' => 'primary',
      '#disabled' => !$analyzer_ready,
    ];
    $form['drupal_upgrade_status_form']['action']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export selected as HTML'),
      '#weight' => 5,
      '#submit' => [[$this, 'exportReport']],
      '#disabled' => !$analyzer_ready,
    ];
    $form['drupal_upgrade_status_form']['action']['export_ascii'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export selected as text'),
      '#weight' => 6,
      '#submit' => [[$this, 'exportReportASCII']],
      '#disabled' => !$analyzer_ready,
    ];

    return $form;
  }

  /**
   * Builds a list and status summary of projects.
   *
   * @param \Drupal\Core\Extension\Extension[] $projects
   *   Array of extensions representing projects.
   * @param string $next_step
   *   The machine name of the suggested next step to take for these projects.
   *
   * @return array
   *   Build array.
   */
  protected function buildProjectList(array $projects, string $next_step) {
    $header = [
      'project'  => ['data' => $this->t('Project'), 'class' => 'project-label'],
      'type'     => ['data' => $this->t('Type'), 'class' => 'type-label'],
      'status'   => ['data' => $this->t('Status'), 'class' => 'status-label'],
      'version'  => ['data' => $this->t('Local version'), 'class' => 'version-label'],
      'ready'    => ['data' => $this->t('Local 9-ready'), 'class' => 'ready-label'],
      'result'   => ['data' => $this->t('Local scan result'), 'class' => 'scan-info'],
      'updatev'  => ['data' => $this->t('Drupal.org version'), 'class' => 'updatev-info'],
      'update9'  => ['data' => $this->t('Drupal.org 9-ready'), 'class' => 'update9-info'],
      'issues'   => ['data' => $this->t('Drupal.org issues'), 'class' => 'issue-info'],
      'plan'     => ['data' => $this->t('Plan'), 'class' => 'plan-info'],
    ];
    $build['list'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#weight' => 20,
      '#options' => [],
    ];
    foreach ($projects as $name => $extension) {
      $option = [
        '#attributes' => ['class' => 'project-' . $name],
      ];
      $option['project'] = [
        'data' => [
          'label' => [
            '#type' => 'html_tag',
            '#tag' => 'label',
            '#value' => $extension->info['name'],
            '#attributes' => [
              'for' => 'edit-' . $next_step . '-data-list-' . str_replace('_', '-', $name),
            ],
          ],
        ],
        'class' => 'project-label',
      ];
      $option['type'] = [
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => $extension->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM ? $this->t('Custom') : $this->t('Contributed'),
          ],
        ]
      ];
      $option['status'] = [
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => empty($extension->status) ? $this->t('Uninstalled') : $this->t('Installed'),
          ],
        ]
      ];

      // Start of local version/readiness columns.
      $option['version'] = [
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => !empty($extension->info['version']) ? $extension->info['version'] : $this->t('N/A'),
          ],
        ]
      ];
      $option['ready'] = [
        'class' => 'status-info ' . (!empty($extension->info['upgrade_status_9_compatible']) ? 'status-info-compatible' : 'status-info-incompatible'),
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => !empty($extension->info['upgrade_status_9_compatible']) ? $this->t('Compatible') : $this->t('Incompatible'),
          ],
        ]
      ];

      $report = $this->projectCollector->getResults($name);
      $result_summary = !empty($report) ? $this->t('No problems found') : $this->t('N/A');
      if (!empty($report['data']['totals']['file_errors'])) {
        $result_summary = $this->formatPlural(
          $report['data']['totals']['file_errors'],
          '@count problem',
          '@count problems'
        );
        $option['result'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $result_summary,
            '#url' => Url::fromRoute('upgrade_status.project', ['project_machine_name' => $name]),
            '#attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 1024,
                'height' => 568,
              ]),
            ],
          ],
          'class' => 'scan-result',
        ];
      }
      else {
        $option['result'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => $result_summary,
            ],
          ],
          'class' => 'scan-result',
        ];
      }

      // Start of drupal.org data columns.
      $updatev = $this->t('Not applicable');
      if (!empty($extension->info['upgrade_status_update_link'])) {
        $option['updatev'] = [
          'data' => [
            'link' => [
              '#type' => 'link',
              '#title' => $extension->info['upgrade_status_update_version'],
              '#url' => Url::fromUri($extension->info['upgrade_status_update_link']),
            ],
          ]
        ];
        unset($updatev);
      }
      elseif (!empty($extension->info['upgrade_status_update'])) {
        $updatev = $this->t('Unavailable');
        if ($extension->info['upgrade_status_update'] == ProjectCollector::UPDATE_NOT_CHECKED) {
          $updatev = $this->t('Unchecked');
        }
        elseif ($extension->info['upgrade_status_update'] == ProjectCollector::UPDATE_ALREADY_INSTALLED) {
          $updatev = $this->t('Up to date');
        }
      }
      if (!empty($updatev)) {
        $option['updatev'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => $updatev,
            ],
          ]
        ];
      }
      $update_class = 'status-info-na';
      $update_info = $this->t('Not applicable');
      if (isset($extension->info['upgrade_status_update'])) {
        switch ($extension->info['upgrade_status_update']) {
          case ProjectCollector::UPDATE_NOT_AVAILABLE:
            $update_info = $this->t('Unavailable');
            $update_class = 'status-info-na';
            break;
          case ProjectCollector::UPDATE_NOT_CHECKED:
            $update_info = $this->t('Unchecked');
            $update_class = 'status-info-unchecked';
            break;
          case ProjectCollector::UPDATE_AVAILABLE:
          case ProjectCollector::UPDATE_ALREADY_INSTALLED:
            if ($extension->info['upgrade_status_update_compatible']) {
              $update_info = $this->t('Compatible');
              $update_class = 'status-info-compatible';
            }
            else {
              $update_info = $this->t('Incompatible');
              $update_class = 'status-info-incompatible';
            }
            break;
        }
      }
      $option['update9'] = [
        'class' => 'status-info ' . $update_class,
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => $update_info,
          ],
        ]
      ];
      if ($extension->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM) {
        $option['issues'] = $option['plan'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => $this->t('Not applicable'),
            ],
          ]
        ];
      }
      else {
        $plan = (string) $this->projectCollector->getPlan($name);
        $option['issues'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              // Use the project name from the info array instead of $key.
              // $key is the local name, not necessarily the project name.
              '#markup' => '<a href="https://drupal.org/project/issues/' . $extension->info['project'] . '?text=Drupal+9&status=All">' . $this->t('Issues') . '</a>',
            ],
          ]
        ];
        $plan = (string) $this->projectCollector->getPlan($name);
        $option['plan'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => !empty($plan) ? $plan : $this->t('N/A'),
            ],
          ]
        ];
      }
      $build['list']['#options'][$name] = $option;
    }

    return $build;
  }

  /**
   * Build a result summary table for quick overview display to users.
   *
   * @param bool $environment_status
   *   The status of the environment. Whether to put it into the Fix or Relax columns.
   *
   * @return array
   *   Render array.
   */
  protected function buildResultSummary($environment_status = TRUE) {
    $projects = $this->projectCollector->collectProjects();
    $next_steps = $this->projectCollector->getNextStepInfo();

    $last = $this->state->get('update.last_check') ?: 0;
    if ($last == 0) {
      $last_checked = $this->t('Never checked');
    }
    else {
      $time = $this->dateFormatter->formatTimeDiffSince($last);
      $last_checked = $this->t('Last checked @time ago', ['@time' => $time]);
    }
    $update_time = [
      [
        '#type' => 'link',
        '#title' => $this->t('Check available updates'),
        '#url' => Url::fromRoute('update.manual_status', [], ['query' => $this->destination->getAsArray()]),
      ],
      [
        '#type' => 'markup',
        '#markup' => ' (' . $last_checked . ')',
      ],
    ];

    $header = [
      ProjectCollector::SUMMARY_ANALYZE => ['data' => $this->t('Gather data'), 'class' => 'summary-' . ProjectCollector::SUMMARY_ANALYZE],
      ProjectCollector::SUMMARY_ACT => ['data' => $this->t('Fix incompatibilities'), 'class' => 'status-' . ProjectCollector::SUMMARY_ACT],
      ProjectCollector::SUMMARY_RELAX => ['data' => $this->t('Relax'), 'class' => 'status-' . ProjectCollector::SUMMARY_RELAX],
    ];
    $build = [
      '#type' => 'table',
      '#attributes' => ['class' => ['upgrade-status-overview']],
      '#header' => $header,
      '#rows' => [
        [
          'data' => [
            ProjectCollector::SUMMARY_ANALYZE => ['data' => []],
            ProjectCollector::SUMMARY_ACT => ['data' => []],
            ProjectCollector::SUMMARY_RELAX => ['data' => []],
          ]
        ]
      ],
    ];
    foreach ($header as $key => $value) {
      $cell_data = $cell_items = [];
      foreach($next_steps as $next_step => $step_label) {
        // If this next step summary belongs in this table cell, collect it.
        if ($step_label[2] == $key) {
          foreach ($projects as $name => $project) {
            if ($project->info['upgrade_status_next'] == $next_step) {
              @$cell_data[$next_step]++;
            }
          }
        }
      }
      if ($key == ProjectCollector::SUMMARY_ANALYZE) {
        // If neither Composer Deploy nor Git Deploy are available and installed, suggest installing one.
        if (empty($projects['git_deploy']->status) && empty($projects['composer_deploy']->status)) {
          $cell_items[] = [
            '#markup' => $this->t('Install <a href=":composer_deploy">Composer Deploy</a> or <a href=":git_deploy">Git Deploy</a> as appropriate for accurate update recommendations', [':composer_deploy' => 'https://drupal.org/project/composer_deploy', ':git_deploy' => 'https://drupal.org/project/git_deploy'])
          ];
        }
        // Add available update info.
        $cell_items[] = $update_time;
      }
      if (($key == ProjectCollector::SUMMARY_ACT) && !$environment_status) {
        $cell_items[] = [
          '#markup' => '<a href="#edit-environment" class="upgrade-status-summary-label">' . $this->t('Environment is incompatible') . '</a>',
        ];
      }

      if (count($cell_data)) {
        foreach ($cell_data as $next_step => $count) {
          $cell_items[] = [
            '#markup' => '<a href="#edit-' . $next_step . '" class="upgrade-status-summary-label upgrade-status-summary-label-' . $next_step . '">' . $this->formatPlural($count, '@type: 1 project', '@type: @count projects', ['@type' => $next_steps[$next_step][0]]) . '</a>',
          ];
        }
      }

      if ($key == ProjectCollector::SUMMARY_ANALYZE) {
        $cell_items[] = [
          '#markup' => 'Select any of the projects to rescan as needed below',
        ];
      }
      if ($key == ProjectCollector::SUMMARY_RELAX) {
        // Calculate how done is this site assuming the environment as
        // "one project" for simplicity.
        $done_count = (!empty($cell_data[ProjectCollector::NEXT_RELAX]) ? $cell_data[ProjectCollector::NEXT_RELAX] : 0) + (int) $environment_status;
        $percent = round($done_count / (count($projects) + 1) * 100);
        $build['#rows'][0]['data'][$key]['data'][] = [
          '#type' => 'markup',
          '#allowed_tags' => ['svg', 'path', 'text'],
          '#markup' => <<<MARKUP
        <div class="upgrade-status-result-chart">
        <svg viewBox="0 0 36 36" class="upgrade-status-result-circle">
          <path class="circle-bg"
            d="M18 2.0845
              a 15.9155 15.9155 0 0 1 0 31.831
              a 15.9155 15.9155 0 0 1 0 -31.831"
          />
          <path class="circle"
            stroke-dasharray="{$percent}, 100"
            d="M18 2.0845
              a 15.9155 15.9155 0 0 1 0 31.831
              a 15.9155 15.9155 0 0 1 0 -31.831"
          />
          <text x="18" y="20.35" class="percentage">{$percent}%</text>
        </svg>
      </div>
MARKUP
        ];
        if ($environment_status) {
          $cell_items[] = [
            '#markup' => '<a href="#edit-environment" class="upgrade-status-summary-label">' . $this->t('Environment checks passed') . '</a>',
          ];
        }
        $cell_items[] = [
          '#markup' => 'Once entirely compatible, make sure to remove Upgrade Status from the site before updating to Drupal 9',
        ];
      }
      if (count($cell_items)) {
        $build['#rows'][0]['data'][$key]['data'][] = [
          '#theme' => 'item_list',
          '#items' => $cell_items,
        ];
      }
      else {
        $build['#rows'][0]['data'][$key]['data'][] = [
          '#type' => 'markup',
          '#markup' => $this->t('N/A'),
        ];
      }
    }
    return $build;
  }

  /**
   * Builds a list of environment checks.
   *
   * @return array
   *   Build array. The overall environment status (TRUE or FALSE) is indicated
   *   in the 'status' key.
   */
  protected function buildEnvironmentChecks() {
    $status = TRUE;
    $header = [
      'requirement' => ['data' => $this->t('Requirement'), 'class' => 'requirement-label'],
      'status' => ['data' => $this->t('Status'), 'class' => 'status-info'],
    ];
    $build['data'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
    ];

    // Check Drupal version. Link to update if available.
    $core_version_info = [
      '#type' => 'markup',
      '#markup' => $this->t('Version @version and up to date.', ['@version' => \Drupal::VERSION]),
    ];
    $has_core_update = FALSE;
    $core_update_info = $this->releaseStore->get('drupal');
    if (isset($core_update_info['releases']) && is_array($core_update_info['releases'])) {
      // Find the latest release that are higher than our current and is not beta/alpha/rc.
      foreach ($core_update_info['releases'] as $version => $release) {
        $major_version = explode('.', $version)[0];
        if ((version_compare($version, \Drupal::VERSION) > 0) && empty($release['version_extra']) && $major_version === '8') {
          $link = $core_update_info['link'] . '/releases/' . $version;
          $core_version_info = [
            '#type' => 'link',
            '#title' => $this->t('Version @current allows to upgrade but @new is available.', ['@current' => \Drupal::VERSION, '@new' => $version]),
            '#url' => Url::fromUri($link),
          ];
          $has_core_update = TRUE;
          break;
        }
      }
    }
    if (version_compare(\Drupal::VERSION, '8.8.0') >= 0) {
      if (!$has_core_update) {
        $class = 'no-known-error';
      }
      else {
        $class = 'known-warning';
      }
    }
    else {
      $status = FALSE;
      $class = 'known-error';
    }
    $build['data']['#rows'][] = [
      'class' => $class,
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Drupal core should be 8.8.x or 8.9.x'),
        ],
        'status' => [
          'data' => $core_version_info,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check PHP version.
    $version = PHP_VERSION;
    if (version_compare($version, '7.3.0') >= 0) {
      $class = 'no-known-error';
    }
    else {
      $class = 'known-error';
      $status = FALSE;
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('PHP version should be at least 7.3.0'),
        ],
        'status' => [
          'data' => $this->t('Version @version', ['@version' => $version]),
          'class' => 'status-info',
        ],
      ]
    ];

    // Check database version.
    $database = \Drupal::database();
    $type = $database->databaseType();
    $version = $database->version();

    // MariaDB databases report as MySQL. Detect MariaDB separately based on code from
    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Database%21Driver%21mysql%21Connection.php/function/Connection%3A%3AgetMariaDbVersionMatch/9.0.x
    // See also https://www.drupal.org/node/3119156 for test values.
    if ($type == 'mysql') {
      // MariaDB may prefix its version string with '5.5.5-', which should be
      // ignored.
      // @see https://github.com/MariaDB/server/blob/f6633bf058802ad7da8196d01fd19d75c53f7274/include/mysql_com.h#L42.
      $regex = '/^(?:5\\.5\\.5-)?(\\d+\\.\\d+\\.\\d+.*-mariadb.*)/i';
      preg_match($regex, $version, $matches);
      if (!empty($matches[1])) {
        $type = 'MariaDB';
        $version = $matches[1];
        $requirement = $this->t('When using MariaDB, minimum version is 10.3.7');
        if (version_compare($version, '10.3.7') >= 0) {
          $class = 'no-known-error';
        }
        elseif (version_compare($version, '10.1.0') >= 0) {
          $class = 'known-warning';
          $requirement .= ' ' . $this->t('Alternatively, <a href=":driver">install the MariaDB 10.1 driver for Drupal 9</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql56']);
        }
        else {
          $status = FALSE;
          $class = 'known-error';
          $requirement .= ' ' . $this->t('Once updated to at least 10.1, you can also <a href=":driver">install the MariaDB 10.1 driver for Drupal 9</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql56']);
        }
      }
      else {
        $type = 'MySQL or Percona Server';
        $requirement = $this->t('When using MySQL/Percona, minimum version is 5.7.8');
        if (version_compare($version, '5.7.8') >= 0) {
          $class = 'no-known-error';
        }
        elseif (version_compare($version, '5.6.0') >= 0) {
          $class = 'known-warning';
          $requirement .= ' ' . $this->t('Alternatively, <a href=":driver">install the MySQL 5.6 driver for Drupal 9</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql56']);
        }
        else {
          $status = FALSE;
          $class = 'known-error';
          $requirement .= ' ' . $this->t('Once updated to at least 5.6, you can also <a href=":driver">install the MySQL 5.6 driver for Drupal 9</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql56']);
        }
      }
    }
    elseif ($type == 'pgsql') {
      $type = 'PostgreSQL';
      $requirement = $this->t('When using PostgreSQL, minimum version is 10 <a href=":trgm">with the pg_trgm extension</a> (The extension is not checked here).', [':trgm' => 'https://www.postgresql.org/docs/10/pgtrgm.html']);
      if (version_compare($version, '10') >= 0) {
        $class = 'no-known-error';
      }
      else {
        $status = FALSE;
        $class = 'known-error';
      }
    }
    elseif ($type == 'sqlite') {
      $type = 'SQLite';
      $requirement = $this->t('When using SQLite, minimum version is 3.26');
      if (version_compare($version, '3.26') >= 0) {
        $class = 'no-known-error';
      }
      else {
        $status = FALSE;
        $class = 'known-error';
      }
    }

    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => [
            '#type' => 'markup',
            '#markup' => $requirement
          ],
        ],
        'status' => [
          'data' => $type . ' ' . $version,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check Apache. Logic is based on system_requirements() code.
    $request_object = \Drupal::request();
    $software = $request_object->server->get('SERVER_SOFTWARE');
    if (strpos($software, 'Apache') !== FALSE && preg_match('!^Apache/([\d\.]+) !', $software, $found)) {
      $version = $found[1];
      if (version_compare($version, '2.4.7') >= 0) {
        $class = 'no-known-error';
      }
      else {
        $status = FALSE;
        $class = 'known-error';
      }
      $label = $this->t('Version @version', ['@version' => $version]);
    }
    else {
      $class = '';
      $label = $this->t('Version cannot be detected or not using Apache, check manually.');
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('When using Apache, minimum version is 2.4.7'),
        ],
        'status' => [
          'data' => $label,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check Drush. We only detect site-local drush for now.
    if (class_exists('\\Drush\\Drush')) {
      $version = call_user_func('\\Drush\\Drush::getMajorVersion');
      if (version_compare($version, '10') >= 0) {
        $class = 'no-known-error';
      }
      else {
        $status = FALSE;
        $class = 'known-error';
      }
      $label = $this->t('Version @version', ['@version' => $version]);
    }
    else {
      $class = '';
      $label = $this->t('Version cannot be detected, check manually.');
    }
    $build['data']['#rows'][] = [
      'class' => $class,
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('When using Drush, minimum version is 10'),
        ],
        'status' => [
          'data' => $label,
          'class' => 'status-info',
        ],
      ]
    ];

    // Save the overall status indicator in the build array. It will be
    // popped off later to be used in the summary table.
    $build['status'] = $status;

    return $build;
  }


  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Reset extension lists for better Drupal 9 compatibility info.
    $this->projectCollector->resetLists();

    $operations = $list = [];
    $projects = $this->projectCollector->collectProjects();
    $submitted = $form_state->getValues();
    $next_steps = $this->projectCollector->getNextStepInfo();
    foreach ($next_steps as $next_step => $step_label) {
      if (!empty($submitted[$next_step]['data']['list'])) {
        foreach ($submitted[$next_step]['data']['list'] as $item) {
          if (isset($projects[$item])) {
            $list[] = $projects[$item];
          }
        }
      }
    }

    // It is not possible to make an HTTP request to this same webserver
    // if the host server is PHP itself, because it is single-threaded.
    // See https://www.php.net/manual/en/features.commandline.webserver.php
    $use_http = php_sapi_name() != 'cli-server';
    $php_server = !$use_http;
    if ($php_server) {
      // Log the selected processing method for project support purposes.
      $this->logger->notice('Starting Upgrade Status on @count projects without HTTP sandboxing because the built-in PHP webserver does not allow for that.', ['@count' => count($list)]);
    }
    else {
      // Attempt to do an HTTP request to the frontpage of this Drupal instance.
      // If that does not work then we'll not be able to process projects over
      // HTTP. Processing projects directly is less safe (in case of PHP fatal
      // errors the batch process may halt), but we have no other choice here
      // but to take a chance.
      list($error, $message, $data) = static::doHttpRequest('upgrade_status_request_test', 'upgrade_status_request_test');
      if (empty($data) || !is_array($data) || ($data['message'] != 'Request test success')) {
        $use_http = FALSE;
        $this->logger->notice('Starting Upgrade Status on @count projects without HTTP sandboxing. @error', ['@error' => $message, '@count' => count($list)]);
      }
    }

    if ($use_http) {
      // Log the selected processing method for project support purposes.
      $this->logger->notice('Starting Upgrade Status on @count projects with HTTP sandboxing.', ['@count' => count($list)]);
    }

    foreach ($list as $item) {
      $operations[] = [
        static::class . '::parseProject',
        [$item, $use_http]
      ];
    }
    if (!empty($operations)) {
      // Allow other modules to alter the operations to be run.
      $this->moduleHandler->alter('upgrade_status_operations', $operations, $form_state);
    }
    if (!empty($operations)) {
      $batch = [
        'title' => $this->t('Scanning projects'),
        'operations' => $operations,
        'finished' => static::class . '::finishedParsing',
      ];
      batch_set($batch);
    }
    else {
      $this->messenger()->addError('No projects selected to scan.');
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $format
   *   Either 'html' or 'ascii' depending on what the format should be.
   */
  public function exportReport(array &$form, FormStateInterface $form_state, string $format = 'html') {
    $extensions = [];
    $projects = $this->projectCollector->collectProjects();
    $submitted = $form_state->getValues();
    $next_steps = $this->projectCollector->getNextStepInfo();
    foreach ($next_steps as $next_step => $step_label) {
      if (!empty($submitted[$next_step]['data']['list'])) {
        foreach ($submitted[$next_step]['data']['list'] as $item) {
          if (isset($projects[$item])) {
            $type = $projects[$item]->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM ? 'custom' : 'contrib';
            $extensions[$type][$item] =
              $format == 'html' ?
                $this->resultFormatter->formatResult($projects[$item]) :
                $this->resultFormatter->formatAsciiResult($projects[$item]);
          }
        }
      }
    }

    if (empty($extensions)) {
      $this->messenger()->addError('No projects selected to export.');
      return;
    }

    $build = [
      '#theme' => 'upgrade_status_'. $format . '_export',
      '#projects' => $extensions
    ];

    $fileDate = $this->resultFormatter->formatDateTime(0, 'html_datetime');
    $extension = $format == 'html' ? '.html' : '.txt';
    $filename = 'upgrade-status-export-' . $fileDate . $extension;

    $response = new Response($this->renderer->renderRoot($build));
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $form_state->setResponse($response);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function exportReportASCII(array &$form, FormStateInterface $form_state) {
    $this->exportReport($form, $form_state, 'ascii');
  }

  /**
   * Batch callback to analyze a project.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to analyze.
   * @param bool $use_http
   *   Whether to use HTTP to execute the processing or execute locally. HTTP
   *   processing could fail in some container setups. Local processing may
   *   fail due to timeout or memory limits.
   * @param array $context
   *   Batch context.
   */
  public static function parseProject(Extension $extension, $use_http, &$context) {
    $context['message'] = t('Analysis complete for @project.', ['@project' => $extension->getName()]);

    if (!$use_http) {
      \Drupal::service('upgrade_status.deprecation_analyzer')->analyze($extension);
      return;
    }

    // Do the HTTP request to run processing.
    list($error, $message, $data) = static::doHttpRequest($extension->getName());

    if ($error !== FALSE) {
      /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value */
      $key_value = \Drupal::service('keyvalue')->get('upgrade_status_scan_results');

      $result = [];
      $result['date'] = \Drupal::time()->getRequestTime();
      $result['data'] = [
        'totals' => [
          'errors' => 1,
          'file_errors' => 1,
          'upgrade_status_split' => [
            'warning' => 1,
          ]
        ],
        'files' => [],
      ];
      $result['data']['files'][$error] = [
        'errors' => 1,
        'messages' => [
          [
            'message' => $message,
            'line' => 0,
          ],
        ],
      ];

      $key_value->set($extension->getName(), $result);
    }
  }

  /**
   * Batch callback to finish parsing.
   *
   * @param $success
   *   TRUE if the batch operation was successful; FALSE if there were errors.
   * @param $results
   *   An associative array of results from the batch operation.
   */
  public static function finishedParsing($success, $results) {
    $logger = \Drupal::logger('upgrade_status');
    if ($success) {
      $logger->notice('Finished Upgrade Status processing successfully.');
    }
    else {
      $logger->notice('Finished Upgrade Status processing with errors.');
    }
  }

  /**
   * Do an HTTP request with the type and machine name.
   *
   * @param string $project_machine_name
   *   The machine name of the project.
   *
   * @return array
   *   A three item array with any potential errors, the error message and the
   *   returned data as the third item. Either of them will be FALSE if they are
   *   not applicable. Data may also be NULL if response JSON decoding failed.
   */
  public static function doHttpRequest(string $project_machine_name) {
    $error = $message = $data = FALSE;

    // Prepare for a POST request to scan this project. The separate HTTP
    // request is used to separate any PHP errors found from this batch process.
    // We can store any errors and gracefully continue if there was any PHP
    // errors in parsing.
    $url = Url::fromRoute(
      'upgrade_status.analyze',
      [
        'project_machine_name' => $project_machine_name
      ]
    );

    // Pass over authentication information because access to this functionality
    // requires administrator privileges.
    /** @var \Drupal\Core\Session\SessionConfigurationInterface $session_config */
    $session_config = \Drupal::service('session_configuration');
    $request = \Drupal::request();
    $session_options = $session_config->getOptions($request);
    // Unfortunately DrupalCI testbot does not have a domain that would normally
    // be considered valid for cookie setting, so we need to work around that
    // by manually setting the cookie domain in case there was none. What we
    // care about is we get actual results, and cookie on the host level should
    // suffice for that.
    $cookie_domain = empty($session_options['cookie_domain']) ? '.' . $request->getHost() : $session_options['cookie_domain'];
    $cookie_jar = new CookieJar();
    $cookie = new SetCookie([
      'Name' => $session_options['name'],
      'Value' => $request->cookies->get($session_options['name']),
      'Domain' => $cookie_domain,
      'Secure' => $session_options['cookie_secure'],
    ]);
    $cookie_jar->setCookie($cookie);
    $options = [
      'cookies' => $cookie_jar,
      'timeout' => 0,
    ];

    // Try a POST request with the session cookie included. We expect valid JSON
    // back. In case there was a PHP error before that, we log that.
    try {
      $response = \Drupal::httpClient()->post($url->setAbsolute()->toString(), $options);
      $data = json_decode((string) $response->getBody(), TRUE);
      if (!$data) {
        $error = 'PHP Fatal Error';
        $message = (string) $response->getBody();
      }
    }
    catch (\Exception $e) {
      $error = 'Scanning exception';
      $message = $e->getMessage();
    }

    return [$error, $message, $data];
  }

}
