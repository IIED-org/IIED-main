<?php

namespace Drupal\clamav\Form;

use Drupal\clamav\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class ClamAVConfigForm extends ConfigFormBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->streamWrapperManager = $container->get('stream_wrapper_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clamav_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['clamav.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('clamav.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ClamAV integration'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['scan_mechanism_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Scan mechanism'),
      '#open' => TRUE,
    ];
    $form['scan_mechanism_wrapper']['scan_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scan mechanism'),
      '#options' => [
        Config::MODE_EXECUTABLE => $this->t('Executable'),
        Config::MODE_DAEMON => $this->t('Daemon mode (over TCP/IP)'),
        Config::MODE_UNIX_SOCKET => $this->t('Daemon mode (over Unix socket)'),
      ],
      '#default_value' => $config->get('scan_mode'),
      '#description' => $this->t("Control how Drupal connects to ClamAV.<br />Daemon mode is recommended if the ClamAV service is capable of running as a daemon."),
    ];

    // Configuration if ClamAV is set to Executable mode.
    $form['scan_mechanism_wrapper']['mode_executable'] = [
      '#type' => 'details',
      '#title' => $this->t('Executable mode configuration'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="scan_mode"]' => ['value' => Config::MODE_EXECUTABLE],
        ],
      ],
    ];
    $form['scan_mechanism_wrapper']['mode_executable']['executable_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Executable path'),
      '#default_value' => $config->get('mode_executable.executable_path'),
      '#maxlength' => 255,
      // '#description' => t('The path to the ClamAV executable. Defaults to %default_path.', array('%default_path' => CLAMAV_DEFAULT_PATH)),
    ];
    $form['scan_mechanism_wrapper']['mode_executable']['executable_parameters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Executable parameters'),
      '#default_value' => $config->get('mode_executable.executable_parameters') ?: '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional parameters to pass to the clamscan executable, e.g. %example.', ['%example' => '--max-recursion=10']),
    ];

    // Configuration if ClamAV is set to Daemon mode.
    $form['scan_mechanism_wrapper']['mode_daemon_tcpip'] = [
      '#type' => 'details',
      '#title' => $this->t('Daemon mode configuration (over TCP/IP)'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="scan_mode"]' => ['value' => Config::MODE_DAEMON],
        ],
      ],
    ];
    $form['scan_mechanism_wrapper']['mode_daemon_tcpip']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $config->get('mode_daemon_tcpip.hostname'),
      '#maxlength' => 255,
      // '#description' => t('The hostname for the ClamAV daemon. Defaults to %default_host.', array('%default_host' => CLAMAV_DEFAULT_HOST)),
    ];
    $form['scan_mechanism_wrapper']['mode_daemon_tcpip']['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#default_value' => $config->get('mode_daemon_tcpip.port'),
      '#size' => 6,
      '#maxlength' => 8,
      // '#description' => t('The port for the ClamAV daemon.  Defaults to port %default_port.  Must be between 1 and 65535.', array('%default_port' => CLAMAV_DEFAULT_PORT)),
    ];

    // Configuration if ClamAV is set to Daemon mode over Unix socket.
    $form['scan_mechanism_wrapper']['mode_daemon_unixsocket'] = [
      '#type' => 'details',
      '#title' => $this->t('Daemon mode configuration (over Unix socket)'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="scan_mode"]' => ['value' => Config::MODE_UNIX_SOCKET],
        ],
      ],
    ];
    $form['scan_mechanism_wrapper']['mode_daemon_unixsocket']['unixsocket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Socket path'),
      '#default_value' => $config->get('mode_daemon_unixsocket.unixsocket'),
      '#maxlength' => 255,
      // '#description' => t('The unix socket path for the ClamAV daemon. Defaults to %default_socket.', array('%default_socket' => CLAMAV_DEFAULT_UNIX_SOCKET)),
    ];

    $form['outage_actions_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Outage behavior'),
      '#open' => TRUE,
    ];
    $form['outage_actions_wrapper']['outage_action'] = [
      '#type' => 'radios',
      '#title' => $this->t('behavior when ClamAV is unavailable'),
      '#options' => [
        Config::OUTAGE_BLOCK_UNCHECKED => $this->t('Block unchecked files'),
        Config::OUTAGE_ALLOW_UNCHECKED => $this->t('Allow unchecked files'),
      ],
      '#default_value' => $config->get('outage_action'),
    ];

    // Allow scanning according to scheme-wrapper.
    $form['schemes'] = [
      '#type' => 'details',
      '#title' => 'Scannable schemes / stream wrappers',
      '#open' => TRUE,
      '#description' => $this->t('By default only @local schemes are scannable.',
        [
          '@local' => Link::fromTextAndUrl(t('local file-systems'), Url::fromUri('https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21StreamWrapper%21LocalStream.php/class/LocalStream/8.2.x'))
            ->toString(),
        ]),
    ];

    $local_schemes = $this->getAvailableSchemeWrappers('local');
    $remote_schemes = $this->getAvailableSchemeWrappers('remote');

    if (count($local_schemes)) {
      $form['schemes']['clamav_local_schemes'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Local schemes'),
        '#options' => $local_schemes,
        '#default_value' => $this->getSchemeWrappersToScan('local'),
      ];
    }
    if (count($remote_schemes)) {
      $form['schemes']['clamav_remote_schemes'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Remote schemes'),
        '#options' => $remote_schemes,
        '#default_value' => $this->getSchemeWrappersToScan('remote'),
      ];
    }

    $form['verbosity_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Verbosity'),
      '#open' => TRUE,
    ];
    $form['verbosity_wrapper']['verbosity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose'),
      '#description' => $this->t('Verbose mode will log all scanned files, including files which pass the ClamAV scan.'),
      '#default_value' => $config->get('verbosity'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check that:
    // - the executable path exists
    // - the unix socket exists
    // - Drupal can connect to the hostname/port (warn but don't fail)
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Configure the stream-wrapper schemes that are overridden.
    // Local schemes behave differently to remote schemes.
    $local_schemes_to_scan = (is_array($form_state->getValue('clamav_local_schemes')))
      ? array_filter($form_state->getValue('clamav_local_schemes'))
      : [];
    $remote_schemes_to_scan = (is_array($form_state->getValue('clamav_remote_schemes')))
      ? array_filter($form_state->getValue('clamav_remote_schemes'))
      : [];
    $overridden_schemes = array_merge(
      $this->getOverriddenSchemes('local', $local_schemes_to_scan),
      $this->getOverriddenSchemes('remote', $remote_schemes_to_scan)
    );

    $this->config('clamav.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('outage_action', $form_state->getValue('outage_action'))
      ->set('overridden_schemes', $overridden_schemes)
      ->set('scan_mode', $form_state->getValue('scan_mode'))
      ->set('verbosity', $form_state->getValue('verbosity'))
      ->set('mode_executable.executable_path', $form_state->getValue('executable_path'))
      ->set('mode_executable.executable_parameters', $form_state->getValue('executable_parameters'))
      ->set('mode_daemon_tcpip.hostname', $form_state->getValue('hostname'))
      ->set('mode_daemon_tcpip.port', $form_state->getValue('port'))
      ->set('mode_daemon_unixsocket.unixsocket', $form_state->getValue('unixsocket'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * List the available stream-wrappers.
   *
   * Based on whether the stream-wrapper is local or remote.
   *
   * @param string $type
   *   Either 'local' (for local stream-wrappers), or 'remote'.
   *
   * @return array
   *   Array of the names of scheme-wrappers, indexed by the machine-name of
   *   the scheme-wrapper.
   *   For example: ['public' => 'public://'].
   */
  public function getAvailableSchemeWrappers($type) {
    $mgr = $this->streamWrapperManager;

    switch ($type) {
      case 'local':
        $schemes = array_keys($mgr->getWrappers(StreamWrapperInterface::LOCAL));
        break;

      case 'remote':
        $schemes = array_keys(array_diff_key(
          $mgr->getWrappers(StreamWrapperInterface::ALL),
          $mgr->getWrappers(StreamWrapperInterface::LOCAL)
        ));
        break;
    }

    $options = [];
    foreach ($schemes as $scheme) {
      $options[$scheme] = $scheme . '://';
    }
    return $options;
  }

  /**
   * List the stream-wrapper schemes that are configured to be scannable,
   * according to whether the scheme is local or remote.
   *
   * @param string $type
   *   Either 'local' (for local stream-wrappers), or 'remote'.
   *
   * @return array
   *   Unindexed array of the machine-names of stream-wrappers that should be
   *   scanned.
   *   For example: ['public', 'private'].
   */
  public function getSchemeWrappersToScan($type) {
    switch ($type) {
      case 'local':
        $schemes = array_keys($this->getAvailableSchemeWrappers('local'));
        break;

      case 'remote':
        $schemes = array_keys($this->getAvailableSchemeWrappers('remote'));
        break;
    }

    return array_filter($schemes, [
      '\Drupal\clamav\Scanner',
      'isSchemeScannable',
    ]);
  }

  /**
   * List which schemes have been overridden.
   *
   * @param string $type
   *   Type of stream-wrapper: either 'local' or 'remote'.
   * @param array $schemes_to_scan
   *   Unindexed array, listing the schemes that should be scanned.
   *
   * @return array
   *   List of the schemes that have been overridden for this particular
   *   stream-wrapper type.
   */
  public function getOverriddenSchemes($type, $schemes_to_scan): array {
    $available_schemes = $this->getAvailableSchemeWrappers($type);
    $overridden = [];
    switch ($type) {
      case 'local':
        $overridden = array_diff_key($available_schemes, $schemes_to_scan);
        break;

      case 'remote':
        $overridden = array_intersect_key($available_schemes, $schemes_to_scan);
        break;
    }

    return array_keys($overridden);
  }

}
