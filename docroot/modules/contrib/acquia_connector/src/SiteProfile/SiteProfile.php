<?php

namespace Drupal\acquia_connector\SiteProfile;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\user\Entity\Role;

/**
 * Site Profile methods.
 *
 * @package Drupal\acquia_contenthub
 */
class SiteProfile {

  /**
   * The Drupal Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Drupal State Service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Module Handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Path Alias Manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * Config Factory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Acquia Connector Site Profile constructor.
   *
   * @param \Drupal\Core\Http\RequestStack $request_stack
   *   The Request Stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module Handler Service.
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   Path Alias Manager.
   */
  public function __construct(RequestStack $request_stack, StateInterface $state, ConfigFactoryInterface $config_factory, ModuleHandler $module_handler, AliasManagerInterface $path_alias_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->state = $state->get('acquia_connector.settings');
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->pathAliasManager = $path_alias_manager;
  }

  /**
   * Attempt to determine if this site is hosted with Acquia.
   *
   * @return bool
   *   TRUE if site is hosted with Acquia, otherwise FALSE.
   */
  public function checkAcquiaHosted() {
    return isset($_SERVER['AH_SITE_ENVIRONMENT'], $_SERVER['AH_SITE_NAME']);
  }

  /**
   * Returns a unique string built on the current domain.
   *
   * @return string
   *   The Site ID when not hosted on Acquia.
   */
  public function getNonAcquiaSiteId() {
    $base_url = $this->request->getHost();
    return $base_url . '_' . substr(md5(uniqid(mt_rand(), TRUE)), 0, 8);
  }

  /**
   * Generate the site name for connector.
   *
   * @return string
   *   The Acquia Hosted name.
   */
  public function getSiteName($subscription_name) {
    // Acquia Hosted.
    if ($this->checkAcquiaHosted() && $subscription_name) {
      return $subscription_name . ': ' . $_SERVER['AH_SITE_ENVIRONMENT'];
    }
    // Locally Hosted.
    return $subscription_name . ': ' . $this->getNonAcquiaSiteId();

  }

  /**
   * Generate the machine name for connector.
   *
   * @return string
   *   The suggested Acquia Hosted machine name.
   */
  public function getMachineName($subscription_data): string {
    if (is_array($subscription_data)) {
      $sub_uuid = str_replace('-', '_', $this->getIdFromSub($subscription_data));

      if ($this->checkAcquiaHosted()) {
        return $sub_uuid . '__' . $_SERVER['AH_SITE_NAME'] . '__' . uniqid();
      }
      return $sub_uuid . '__' . str_replace(['.', '-'], '_', $this->getNonAcquiaSiteId());
    }
    return '';
  }

  /**
   * Gets the subscription UUID from subscription data.
   *
   * @param array $sub_data
   *   An array of subscription data.
   *
   * @see acquia_agent_settings('acquia_subscription_data')
   *
   * @return string
   *   The UUID taken from the subscription data.
   */
  public function getIdFromSub(array $sub_data) {
    if (!empty($sub_data['uuid'])) {
      return $sub_data['uuid'];
    }

    // Otherwise, get this form the sub url.
    $url = UrlHelper::parse($sub_data['href']);
    $parts = explode('/', $url['path']);
    // Remove '/dashboard'.
    array_pop($parts);

    return end($parts);
  }

  /**
   * Check if a site environment change has been detected.
   *
   * @return bool
   *   TRUE if change detected that needs to be addressed, otherwise FALSE.
   */
  public function checkEnvironmentChange() {
    $changes = $this->state->get('spi.environment_changes');
    $change_action = $this->state->get('spi.environment_changed_action');

    return !empty($changes) && empty($change_action);
  }

  /**
   * Attempt to determine the version of Drupal being used.
   *
   * Note, there is better information on this in the common.inc file.
   *
   * @return array
   *   An array containing some detail about the version
   */
  public function getVersionInfo() {
    $server = $this->request->server->all();
    $ver = [];

    $ver['base_version'] = \Drupal::VERSION;
    $install_root = DRUPAL_ROOT;
    $ver['distribution'] = '';

    return $ver;
  }

  /**
   * Gather platform specific information.
   *
   * @return array
   *   An associative array keyed by a platform information type.
   */
  public function getPlatform() {
    $server = $this->request->server;
    // Database detection depends on the structure starting with the database.
    $db_class = '\Drupal\Core\Database\Driver\\' . Database::getConnection()->driver() . '\Install\Tasks';
    $db_tasks = new $db_class();
    // Webserver detection is based on name being before the slash, and
    // version being after the slash.
    preg_match('!^([^/]+)(/.+)?$!', $server->get('SERVER_SOFTWARE'), $webserver);

    if (isset($webserver[1]) && stristr($webserver[1], 'Apache') && function_exists('apache_get_version')) {
      $webserver[2] = apache_get_version();
    }

    // Get some basic PHP vars.
    $php_quantum = [
      'memory_limit' => ini_get('memory_limit'),
      'register_globals' => 'Off',
      'post_max_size' => ini_get('post_max_size'),
      'max_execution_time' => ini_get('max_execution_time'),
      'upload_max_filesize' => ini_get('upload_max_filesize'),
      'error_log' => ini_get('error_log'),
      'error_reporting' => ini_get('error_reporting'),
      'display_errors' => ini_get('display_errors'),
      'log_errors' => ini_get('log_errors'),
      'session.cookie_domain' => ini_get('session.cookie_domain'),
      'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
      'newrelic.appname' => ini_get('newrelic.appname'),
      'sapi' => php_sapi_name(),
    ];

    $platform = [
      'php'               => PHP_VERSION,
      'webserver_type'    => $webserver[1] ?? '',
      'webserver_version' => $webserver[2] ?? '',
      'php_extensions'    => get_loaded_extensions(),
      'php_quantum'       => $php_quantum,
      'database_type'     => (string) $db_tasks->name(),
      'database_version'  => Database::getConnection()->version(),
      'system_type'       => php_uname('s'),
      // php_uname() only accepts one character, so we need to concatenate
      // ourselves.
      'system_version'    => php_uname('r') . ' ' . php_uname('v') . ' ' . php_uname('m') . ' ' . php_uname('n'),
    ];

    return $platform;
  }

  /**
   * Gather information about modules on the site.
   *
   * @return array
   *   An associative array keyed by filename of associative arrays with
   *   information on the modules.
   */
  public function getModules() {
    $modules = \Drupal::service('extension.list.module')->reset()->getList();
    uasort($modules, 'system_sort_modules_by_info_name');

    $result = [];
    $keys_to_send = ['name', 'version', 'package', 'core', 'project'];
    foreach ($modules as $module) {
      $info = [];
      $info['status'] = $module->status;
      foreach ($keys_to_send as $key) {
        $info[$key] = $module->info[$key] ?? '';
      }
      $info['filename'] = $module->getPathname();
      if (empty($info['project']) && $module->origin == 'core') {
        $info['project'] = 'drupal';
      }

      $result[] = $info;
    }
    return $result;
  }

  /**
   * Gather information about nodes, users and comments.
   *
   * @return array
   *   An associative array.
   */
  public function getQuantum() {
    $quantum = [];

    if ($this->moduleHandler->moduleExists('node')) {
      // Get only published nodes.
      $quantum['nodes'] = Database::getConnection()->select('node_field_data', 'n')
        ->fields('n', ['nid'])
        ->condition('n.status', NodeInterface::PUBLISHED)
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    // Get only active users.
    $quantum['users'] = Database::getConnection()->select('users_field_data', 'u')
      ->fields('u', ['uid'])
      ->condition('u.status', 1)
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($this->moduleHandler->moduleExists('comment')) {
      // Get only active comments.
      $quantum['comments'] = Database::getConnection()->select('comment_field_data', 'c')
        ->fields('c', ['cid'])
        ->condition('c.status', 1)
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    return $quantum;
  }

  /**
   * This function is a trimmed version of Drupal's system_status function.
   *
   * @return array
   *   System status array.
   */
  public function getSystemStatus() {
    $data = [];

    if (\Drupal::hasContainer()) {
      $profile = \Drupal::installProfile();
    }
    else {
      $profile = BootstrapConfigStorageFactory::getDatabaseStorage()->read('core.extension')['profile'];
    }
    if ($profile != 'standard') {
      $extension_list = \Drupal::service('extension.list.module');
      $info = $extension_list->getExtensionInfo($profile);
      $data['install_profile'] = [
        'title' => 'Install profile',
        'value' => sprintf('%s (%s-%s)', $info['name'], $profile, $info['version']),
      ];
    }
    $data['php'] = [
      'title' => 'PHP',
      'value' => phpversion(),
    ];
    $conf_dir = TRUE;
    $settings = TRUE;
    $dir = DrupalKernel::findSitePath(\Drupal::request(), TRUE);
    if (is_writable($dir) || is_writable($dir . '/settings.php')) {
      $value = 'Not protected';
      if (is_writable($dir)) {
        $conf_dir = FALSE;
      }
      elseif (is_writable($dir . '/settings.php')) {
        $settings = FALSE;
      }
    }
    else {
      $value = 'Protected';
    }
    $data['settings.php'] = [
      'title' => 'Configuration file',
      'value' => $value,
      'conf_dir' => $conf_dir,
      'settings' => $settings,
    ];
    $cron_last = \Drupal::state()->get('system.cron_last');
    if (!is_numeric($cron_last)) {
      $cron_last = \Drupal::state()->get('install_time', 0);
    }
    $data['cron'] = [
      'title' => 'Cron maintenance tasks',
      'value' => sprintf('Last run %s ago', \Drupal::service('date.formatter')->formatInterval(\Drupal::time()->getRequestTime() - $cron_last)),
      'cron_last' => $cron_last,
    ];
    if (!empty(Settings::get('update_free_access'))) {
      $data['update access'] = [
        'value' => 'Not protected',
        'protected' => FALSE,
      ];
    }
    else {
      $data['update access'] = [
        'value' => 'Protected',
        'protected' => TRUE,
      ];
    }
    $data['update access']['title'] = 'Access to update.php';
    if (!$this->moduleHandler->moduleExists('update')) {
      $data['update status'] = [
        'value' => 'Not enabled',
      ];
    }
    else {
      $data['update status'] = [
        'value' => 'Enabled',
      ];
    }
    $data['update status']['title'] = 'Update notifications';
    return $data;
  }

  /**
   * Get the information on failed logins in the last cron interval.
   *
   * @return array
   *   Array of last 10 failed logins.
   */
  public function getFailedLogins() {
    $last_logins = [];

    if ($this->moduleHandler->moduleExists('dblog')) {
      $result = Database::getConnection()->select('watchdog', 'w')
        ->fields('w', ['message', 'variables', 'timestamp'])
        ->condition('w.message', 'login attempt failed%', 'LIKE')
        ->condition('w.message', [
          "UPGRADE.txt",
          "MAINTAINERS.txt",
          "README.txt",
          "INSTALL.pgsql.txt",
          "INSTALL.txt",
          "LICENSE.txt",
          "INSTALL.mysql.txt",
          "COPYRIGHT.txt",
          "CHANGELOG.txt",
        ], 'NOT IN')
        ->orderBy('w.timestamp', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($result as $record) {
        $variables = unserialize($record->variables);
        if (!empty($variables['%user'])) {
          $last_logins['failed'][$record->timestamp] = Html::escape($variables['%user']);
        }
      }
    }
    return $last_logins;
  }

  /**
   * Grabs the last 404 errors in logs.
   *
   * Grabs the last 404 errors in logs, excluding the checks we run for drupal
   * files like README.
   *
   * @return array
   *   An array of the pages not found and some associated data.
   */
  public function get404s() {
    $data = [];
    $row = 0;

    if ($this->moduleHandler->moduleExists('dblog')) {
      $result = Database::getConnection()->select('watchdog', 'w')
        ->fields('w', ['message', 'hostname', 'referer', 'timestamp'])
        ->condition('w.type', 'page not found', '=')
        ->condition('w.timestamp', \Drupal::time()->getRequestTime() - 3600, '>')
        ->condition('w.message', [
          "UPGRADE.txt",
          "MAINTAINERS.txt",
          "README.txt",
          "INSTALL.pgsql.txt",
          "INSTALL.txt",
          "LICENSE.txt",
          "INSTALL.mysql.txt",
          "COPYRIGHT.txt",
          "CHANGELOG.txt",
        ], 'NOT IN')
        ->orderBy('w.timestamp', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($result as $record) {
        $data[$row]['message'] = $record->message;
        $data[$row]['hostname'] = $record->hostname;
        $data[$row]['referer'] = $record->referer;
        $data[$row]['timestamp'] = $record->timestamp;
        $row++;
      }
    }

    return $data;
  }

  /**
   * Get the number of rows in watchdog.
   *
   * @return int
   *   Number of watchdog records.
   */
  public function getWatchdogSize() {
    if ($this->moduleHandler->moduleExists('dblog')) {
      return Database::getConnection()->select('watchdog', 'w')->fields('w', ['wid'])->countQuery()->execute()->fetchField();
    }
    return 0;
  }

  /**
   * Get the latest (last hour) critical and emergency warnings from watchdog.
   *
   * These errors are 'severity' 0 and 2.
   *
   * @return array
   *   EMERGENCY and CRITICAL watchdog records for last hour.
   */
  public function getWatchdogData() {
    $wd = [];
    if ($this->moduleHandler->moduleExists('dblog')) {
      // phpcs:disable
      $result = Database::getConnection()->select('watchdog', 'w')
        ->fields('w', ['wid', 'severity', 'type', 'message', 'timestamp'])
        ->condition('w.severity', [RfcLogLevel::EMERGENCY, RfcLogLevel::CRITICAL], 'IN')
        ->condition('w.timestamp', \Drupal::time()->getRequestTime() - 3600, '>')
        ->execute();
      // phpcs:enable

      while ($record = $result->fetchAssoc()) {
        $wd[$record['severity']] = $record;
      }
    }

    return $wd;
  }

  /**
   * Get last 15 nodes created.
   *
   * This can be useful to determine if you have some sort of spam on your site.
   *
   * @return array
   *   Array of the details of last 15 nodes created.
   */
  public function getLastNodes() {
    $last_five_nodes = [];
    if ($this->moduleHandler->moduleExists('node')) {
      $result = Database::getConnection()->select('node_field_data', 'n')
        ->fields('n', ['title', 'type', 'nid', 'created', 'langcode'])
        ->condition('n.created', \Drupal::time()->getRequestTime() - 3600, '>')
        ->orderBy('n.created', 'DESC')
        ->range(0, 15)
        ->execute();

      $count = 0;
      foreach ($result as $record) {
        $last_five_nodes[$count]['url'] = $this->pathAliasManager
          ->getAliasByPath('/node/' . $record->nid, $record->langcode);
        $last_five_nodes[$count]['title'] = $record->title;
        $last_five_nodes[$count]['type'] = $record->type;
        $last_five_nodes[$count]['created'] = $record->created;
        $count++;
      }
    }

    return $last_five_nodes;
  }

  /**
   * Get last 15 users created.
   *
   * Useful for determining if your site is compromised.
   *
   * @return array
   *   The details of last 15 users created.
   */
  public function getLastUsers() {
    $last_five_users = [];
    $result = Database::getConnection()->select('users_field_data', 'u')
      ->fields('u', ['uid', 'name', 'mail', 'created'])
      ->condition('u.created', \Drupal::time()->getRequestTime() - 3600, '>')
      ->orderBy('created', 'DESC')
      ->range(0, 15)
      ->execute();

    $count = 0;
    foreach ($result as $record) {
      $last_five_users[$count]['uid'] = $record->uid;
      $last_five_users[$count]['name'] = $record->name;
      $last_five_users[$count]['email'] = $record->mail;
      $last_five_users[$count]['created'] = $record->created;
      $count++;
    }

    return $last_five_users;
  }

  /**
   * Check to see if the unneeded release files with Drupal are removed.
   *
   * @return int
   *   1 if they are removed, 0 if they aren't.
   */
  public function checkFilesPresent() {

    $files_exist = FALSE;
    $files_to_remove = [
      'CHANGELOG.txt',
      'COPYRIGHT.txt',
      'INSTALL.mysql.txt',
      'INSTALL.pgsql.txt',
      'INSTALL.txt',
      'LICENSE.txt',
      'MAINTAINERS.txt',
      'README.txt',
      'UPGRADE.txt',
      'PRESSFLOW.txt',
      'install.php',
    ];

    foreach ($files_to_remove as $file) {

      $path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $file;
      if (file_exists($path)) {
        $files_exist = TRUE;
      }
    }

    return $files_exist ? 1 : 0;
  }

  /**
   * Checks to see if SSL login is required.
   *
   * @return int
   *   1 if SSL login is required.
   */
  public function checkLogin() {
    $login_safe = 0;

    if ($this->moduleHandler->moduleExists('securelogin')) {
      $secureLoginConfig = $this->configFactory->get('securelogin.settings')->get();
      if ($secureLoginConfig['all_forms']) {
        $forms_safe = TRUE;
      }
      else {
        // All the required forms should be enabled.
        $required_forms = [
          'form_user_login_form',
          'form_user_form',
          'form_user_register_form',
          'form_user_pass_reset',
          'form_user_pass',
        ];
        $forms_safe = TRUE;
        foreach ($required_forms as $form_variable) {
          if (!$secureLoginConfig[$form_variable]) {
            $forms_safe = FALSE;
            break;
          }
        }
      }
      // \Drupal::request()->isSecure() ($conf['https'] in D7) should be false
      // for expected behavior.
      if ($forms_safe && !\Drupal::request()->isSecure()) {
        $login_safe = 1;
      }
    }

    return $login_safe;
  }

  /**
   * Check the presence of UID 0 in the users table.
   *
   * @return bool
   *   Whether UID 0 is present.
   */
  public function getUidZeroIsPresent() {
    $count = Database::getConnection()->query('SELECT uid FROM {users} WHERE uid = 0')->fetchAll();
    return (boolean) $count;
  }

  /**
   * Determines if settings.php is read-only.
   *
   * @return bool
   *   TRUE if settings.php is read-only, FALSE otherwise.
   */
  public function getSettingsPermissions() {
    $settings_permissions_read_only = TRUE;
    // http://en.wikipedia.org/wiki/File_system_permissions.
    $writes = ['2', '3', '6', '7'];
    $settings_file = './' . DrupalKernel::findSitePath(\Drupal::request(), TRUE) . '/settings.php';
    $permissions = mb_substr(sprintf('%o', fileperms($settings_file)), -4);

    foreach ($writes as $bit) {
      if (strpos($permissions, $bit)) {
        $settings_permissions_read_only = FALSE;
        break;
      }
    }

    return $settings_permissions_read_only;
  }

  /**
   * The number of users who have admin-level user roles.
   *
   * @return int
   *   Count of admin users.
   */
  public function getAdminCount() {
    $roles_name = [];
    $get_roles = Role::loadMultiple();
    unset($get_roles[AccountInterface::ANONYMOUS_ROLE]);
    $permission = ['administer permissions', 'administer users'];
    foreach ($permission as $value) {
      $filtered_roles = array_filter($get_roles, function ($role) use ($value) {
        return $role->hasPermission($value);
      });
      foreach ($filtered_roles as $role_name => $data) {
        $roles_name[] = $role_name;
      }
    }

    if (!empty($roles_name)) {
      $roles_name_unique = array_unique($roles_name);
      $query = Database::getConnection()->select('user__roles', 'ur');
      $query->fields('ur', ['entity_id']);
      $query->condition('ur.bundle', 'user', '=');
      $query->condition('ur.deleted', '0', '=');
      $query->condition('ur.roles_target_id', $roles_name_unique, 'IN');
      $count = $query->countQuery()->execute()->fetchField();
    }

    return (isset($count) && is_numeric($count)) ? $count : NULL;
  }

  /**
   * Determine if the super user has a weak name.
   *
   * @return int
   *   1 if the super user has a weak name, 0 otherwise.
   */
  public function getSuperName() {
    $result = Database::getConnection()->query("SELECT name FROM {users_field_data} WHERE uid = 1 AND (name LIKE '%admin%' OR name LIKE '%root%') AND LENGTH(name) < 15")->fetchAll();
    return (int) $result;
  }

}
