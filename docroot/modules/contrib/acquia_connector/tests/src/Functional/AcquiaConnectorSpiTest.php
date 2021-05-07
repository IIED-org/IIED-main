<?php

namespace Drupal\Tests\acquia_connector\Functional;

use Drupal\acquia_connector\Controller\SpiController;
use Drupal\acquia_connector\Controller\VariablesController;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of the Acquia SPI module.
 *
 * @group Acquia connector
 */
class AcquiaConnectorSpiTest extends BrowserTestBase {

  /**
   * Drupal 8.8 requires default theme to be specified.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Test privileged user.
   *
   * @var object
   */
  protected $privilegedUser;

  /**
   * Module setup path.
   *
   * @var string
   */
  protected $setupPath;

  /**
   * Module credentials path.
   *
   * @var string
   */
  protected $credentialsPath;

  /**
   * Module settings path.
   *
   * @var string
   */
  protected $settingsPath;

  /**
   * Drupal status report path.
   *
   * @var string
   */
  protected $statusReportUrl;

  /**
   * Module environment change path.
   *
   * @var string
   */
  protected $environmentChangePath;

  /**
   * Test user e-mail.
   *
   * @var string
   */
  protected $acqtestEmail = 'TEST_networkuser@example.com';

  /**
   * Test user password.
   *
   * @var string
   */
  protected $acqtestPass = 'TEST_password';

  /**
   * Test user ID.
   *
   * @var string
   */
  protected $acqtestId = 'TEST_AcquiaConnectorTestID';

  /**
   * Test Acquia Connector key.
   *
   * @var string
   */
  protected $acqtestKey = 'TEST_AcquiaConnectorTestKey';

  /**
   * Test Acquia Connector expired ID.
   *
   * @var string
   */
  protected $acqtestExpiredId = 'TEST_AcquiaConnectorTestIDExp';

  /**
   * Test Acquia Connector expired Key.
   *
   * @var string
   */
  protected $acqtestExpiredKey = 'TEST_AcquiaConnectorTestKeyExp';

  /**
   * Test Acquia Connector 503 ID.
   *
   * @var string
   */
  protected $acqtest503Id = 'TEST_AcquiaConnectorTestID503';

  /**
   * Test Acquia Connector 503 ID.
   *
   * @var string
   */
  protected $acqtest503Key = 'TEST_AcquiaConnectorTestKey503';

  /**
   * Test Acquia Connector ID with error.
   *
   * @var string
   */
  protected $acqtestErrorId = 'TEST_AcquiaConnectorTestIDErr';

  /**
   * Test Acquia Connector ID with error.
   *
   * @var string
   */
  protected $acqtestErrorKey = 'TEST_AcquiaConnectorTestKeyErr';

  /**
   * Test site name.
   *
   * @var string
   */
  protected $acqtestName = 'test name';

  /**
   * Test machine name.
   *
   * @var string
   */
  protected $acqtestMachineName = 'test_name';

  /**
   * NSPI data platform keys.
   *
   * @var array
   */
  protected $platformKeys = [
    'php',
    'webserver_type',
    'webserver_version',
    'php_extensions',
    'php_quantum',
    'database_type',
    'database_version',
    'system_type',
    'system_version',
  ];

  /**
   * NSPI data keys.
   *
   * @var array
   */
  protected $spiDataKeys = [
    'spi_data_version',
    'site_key',
    'modules',
    'platform',
    'quantum',
    'system_status',
    'failed_logins',
    '404s',
    'watchdog_size',
    'watchdog_data',
    'last_nodes',
    'last_users',
    'extra_files',
    'ssl_login',
    'distribution',
    'base_version',
    'build_data',
    'roles',
    'uid_0_present',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'acquia_connector',
    'toolbar',
    'acquia_connector_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    if (empty($_SERVER['SERVER_SOFTWARE'])) {
      $_SERVER['SERVER_SOFTWARE'] = $this->randomString();
    }
    parent::setUp();

    // Enable any modules required for the test
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($this->privilegedUser);

    // Setup variables.
    $this->environmentChangePath = '/admin/config/system/acquia-connector/environment-change';
    $this->credentialsPath = 'admin/config/system/acquia-connector/credentials';
    $this->settingsPath = 'admin/config/system/acquia-connector';
    $this->statusReportUrl = 'admin/reports/status';

    // Local env.
    $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');
    $config->set('spi.server', 'http://mock-spi-server');
    $config->set('spi.ssl_verify', FALSE);
    $config->set('spi.ssl_override', TRUE);
    // Set mapping for the test variables.
    $mapping = $config->get('mapping');
    $mapping['test_variable_1'] = ['state', 'test_variable_1'];
    $mapping['test_variable_2'] = ['state', 'test_variable_2'];
    $mapping['test_variable_3'] = ['state', 'test_variable_3'];
    $config->set('mapping', $mapping);
    $config->save(TRUE);

    // Set values for test variables.
    \Drupal::state()->set('test_variable_1', 1);
    \Drupal::state()->set('test_variable_2', 2);
    \Drupal::state()->set('test_variable_3', 3);

  }

  /**
   * Helper function for storing UI strings.
   *
   * @param string $id
   *   String ID.
   *
   * @return string
   *   UI message.
   *
   * @throws \Exception
   */
  private function acquiaSpiStrings($id) {
    switch ($id) {
      case 'spi-status-text':
        return 'SPI data will be sent once every 30 minutes once cron is called';

      case 'spi-not-sent';
        return 'SPI data has not been sent';

      case 'spi-send-text';
        return 'manually send SPI data';

      case 'spi-data-sent':
        return 'SPI data sent';

      case 'spi-data-sent-error':
        return 'Error sending SPI data. Consult the logs for more information.';

      case 'spi-new-def':
        return 'There are new checks that will be performed on your site by the Acquia Connector';

      case 'provide-site-name':
        return 'provide a site name';

      case 'change-env-detected':
        return 'A change in your site\'s environment has been detected. SPI data cannot be submitted until this is resolved.';

      case 'confirm-action':
        return 'confirm the action you wish to take';

      case 'block-site-message':
        return 'This site has been disabled from sending profile data to Acquia.';

      case 'unblock-site':
        return 'Enable this site';

      case 'acquia-hosted':
        return 'Your site is now Acquia hosted.';

      case 'no-acquia-hosted':
        return 'Your site is no longer Acquia hosted.';

      default:
        throw new \Exception("Invalid id $id");
    }
  }

  /**
   * Test Acquia SPI UI.
   *
   * @throws \Exception
   */
  public function testAcquiaSpiUiTests() {
    $this->drupalGet($this->statusReportUrl);
    $this->assertNoText($this->acquiaSPIStrings('spi-status-text'));
    // Connect site on key and id that will error.
    $edit_fields = [
      'acquia_identifier' => $this->acqtestErrorId,
      'acquia_key' => $this->acqtestErrorKey,
    ];
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentialsPath, $edit_fields, $submit_button);
    // Even though the credentials are invalid, they should still be set and the
    // connection successful.
    $this->assertText("Connection successful");

    // If name and machine name are empty.
    $this->drupalGet($this->statusReportUrl);
    $this->assertText($this->acquiaSPIStrings('spi-not-sent'));
    $this->assertText($this->acquiaSPIStrings('provide-site-name'));

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    // Send SPI data.
    $this->drupalGet($this->statusReportUrl);
    $this->assertText($this->acquiaSPIStrings('spi-status-text'));
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertNoText($this->acquiaSPIStrings('spi-data-sent'));
    $this->assertText($this->acquiaSPIStrings('spi-data-sent-error'));

    // Connect site on non-error key and id.
    $this->connectSite();
    $this->drupalGet($this->statusReportUrl);
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertText($this->acquiaSPIStrings('spi-data-sent'));
    $this->assertNoText($this->acquiaSPIStrings('spi-not-sent'));
    $this->assertText('This is the first connection from this site, it may take awhile for it to appear.');

    // Machine name change.
    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName . '_change',
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);
    $this->assertText('A change has been detected in your site environment. Please check the Acquia SPI status on your Status Report page for more information.');
    $this->drupalGet($this->statusReportUrl);
    $this->clickLink($this->acquiaSPIStrings('confirm-action'));
    $this->assertText('Your site machine name changed from ' . $this->acqtestMachineName . ' to ' . $this->acqtestMachineName . '_change' . '.');

    // Block site.
    $edit_fields = [
      'env_change_action' => 'block',
    ];

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environmentChangePath, $edit_fields, $submit_button);
    $this->assertText($this->acquiaSPIStrings('block-site-message'));
    $this->clickLink($this->acquiaSPIStrings('unblock-site'));

    // Unblock site.
    $edit_fields = [
      'env_change_action[unblock]' => TRUE,
    ];

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environmentChangePath, $edit_fields, $submit_button);
    $this->assertText('Your site has been enabled and is sending data to Acquia Cloud');
    $this->assertText($this->acquiaSPIStrings('spi-data-sent'));
    $this->assertNoText($this->acquiaSPIStrings('spi-not-sent'));

    // Update machine name on existing site.
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertText($this->acquiaSPIStrings('change-env-detected'));
    $this->clickLink($this->acquiaSPIStrings('confirm-action'));

    $edit_fields = [
      'env_change_action' => 'update',
    ];

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environmentChangePath, $edit_fields, $submit_button);

    // Name change.
    $edit_fields = [
      'name' => $this->acqtestName . ' change',
      'machine_name' => $this->acqtestMachineName . '_change',
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);
    $this->drupalGet($this->statusReportUrl);
    $this->assertNoText($this->acquiaSPIStrings('spi-not-sent'));
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertText('Site name updated (from ' . $this->acqtestName . ' to ' . $this->acqtestName . ' change).');
  }

  /**
   * Test Acquia SPI get.
   */
  public function testAcquiaSpiGetTests() {
    // Connect site on non-error key and id.
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    // Test spiControllerTest::get.
    $spi = new SpiController(\Drupal::service('acquia_connector.client'), \Drupal::service('config.factory'), \Drupal::service('path_alias.manager'));
    $spi_data = $spi->get();
    $valid = is_array($spi_data);
    $this->assertTrue($valid, 'spiController::get returns an array');
    if ($valid) {
      foreach ($this->spiDataKeys as $key) {
        if (!array_key_exists($key, $spi_data)) {
          $valid = FALSE;
          break;
        }
      }
      $this->assertTrue($valid, 'Array has expected keys');
      $private_key = \Drupal::service('private_key')->get();
      $this->assertEqual(sha1($private_key), $spi_data['site_key'], 'Site key is sha1 of Drupal private key');
      $this->assertNotEmpty($spi_data['spi_data_version'], 'SPI data version is set');
      $vars = Json::decode($spi_data['system_vars']);
      $this->assertIsArray($vars, 'SPI data system_vars is a JSON-encoded array');
      $this->assertArrayHasKey('test_variable_3', $vars, 'test_variable_3 included in SPI data');
      $this->assertNotEmpty($spi_data['modules'], 'Modules is not empty');
      $modules = [
        'status',
        'name',
        'version',
        'package',
        'core',
        'project',
        'filename',
        'module_data',
      ];
      $diff = array_diff(array_keys($spi_data['modules'][0]), $modules);
      $this->assertEmpty($diff, 'Module elements have expected keys');
      $diff = array_diff(array_keys($spi_data['platform']), $this->platformKeys);
      $this->assertEmpty($diff, 'Platform contains expected keys');
      $roles = Json::decode($spi_data['roles']);
      $this->assertIsArray($roles, 'Roles is an array');
      $this->assertArrayHasKey('anonymous', $roles, 'Roles array contains anonymous user');
    }
  }

  /**
   * Validate Acquia SPI data.
   */
  public function testNoObjectInSpiDataTests() {
    // Connect site on non-error key and id.
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    $spi = new SpiController(\Drupal::service('acquia_connector.client'), \Drupal::service('config.factory'), \Drupal::service('path_alias.manager'));
    $spi_data = $spi->get();

    $this->assertFalse($this->isContainObjects($spi_data), 'SPI data does not contain PHP objects.');
  }

  /**
   * Test Acquia SPI send.
   */
  public function testAcquiaSpiSendTests() {
    // Connect site on invalid credentials.
    $edit_fields = [
      'acquia_identifier' => $this->acqtestErrorId,
      'acquia_key' => $this->acqtestErrorKey,
    ];
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentialsPath, $edit_fields, $submit_button);

    // Attempt to send something.
    $client = \Drupal::service('acquia_connector.client');
    // Connect site on valid credentials.
    $this->connectSite();

    // Check that result is an array.
    $spi = new SpiController(\Drupal::service('acquia_connector.client'), \Drupal::service('config.factory'), \Drupal::service('path_alias.manager'));
    $spi_data = $spi->get();
    unset($spi_data['spi_def_update']);
    $result = $client->sendNspi($this->acqtestId, $this->acqtestKey, $spi_data);
    $this->assertIsArray($result, 'SPI update result is an array');

    // Trigger a validation error on response.
    $spi_data['test_validation_error'] = TRUE;
    unset($spi_data['spi_def_update']);
    $result = $client->sendNspi($this->acqtestId, $this->acqtestKey, $spi_data);
    $this->assertFalse($result, 'SPI result is false if validation error.');
    unset($spi_data['test_validation_error']);

    // Trigger a SPI definition update response.
    $spi_data['spi_def_update'] = TRUE;
    $result = $client->sendNspi($this->acqtestId, $this->acqtestKey, $spi_data);
    $this->assertNotEmpty($result['body']['update_spi_definition'], 'SPI result array has expected "update_spi_definition" key.');
  }

  /**
   * Test Acquia SPI update response.
   */
  public function testAcquiaSpiUpdateResponseTests() {
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    $def_timestamp = \Drupal::state()->get('acquia_spi_data.def_timestamp', 0);
    $this->assertNotEqual($def_timestamp, 0, 'SPI definition timestamp set');
    $def_vars = \Drupal::state()->get('acquia_spi_data.def_vars', []);
    $this->assertNotEmpty($def_vars, 'SPI definition variable set');
    \Drupal::state()->set('acquia_spi_data.def_waived_vars', ['test_variable_3']);
    // Test that new variables are in SPI data.
    $spi = new SpiController(\Drupal::service('acquia_connector.client'), \Drupal::service('config.factory'), \Drupal::service('path_alias.manager'));
    $spi_data = $spi->get();
    $vars = Json::decode($spi_data['system_vars']);
    $this->assertNotEmpty($vars['test_variable_1'], 'New variables included in SPI data');
    $this->assertArrayNotHasKey('test_variable_3', $vars, 'test_variable_3 not included in SPI data');
  }

  /**
   * Test Acquia SPI set variables.
   */
  public function testAcquiaSpiSetVariablesTests() {
    // Connect site on non-error key and id.
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    $spi = new SpiController(\Drupal::service('acquia_connector.client'), \Drupal::service('config.factory'), \Drupal::service('path_alias.manager'));
    $spi_data = $spi->get();
    $vars = Json::decode($spi_data['system_vars']);
    $this->assertEmpty($vars['acquia_spi_saved_variables']['variables'], 'Have not saved any variables');
    // Set error reporting so variable is saved.
    $edit = [
      'error_level' => 'verbose',
    ];
    $this->drupalPostForm('admin/config/development/logging', $edit, 'Save configuration');

    // Turn off error reporting.
    $set_variables = ['error_level' => 'hide'];
    $variables = new VariablesController();
    $variables->setVariables($set_variables);

    $new = \Drupal::config('system.logging')->get('error_level');
    $this->assertTrue($new === 'hide', 'Set error reporting to log only');
    $vars = Json::decode($variables->getVariablesData());
    $this->assertContains('error_level', $vars['acquia_spi_saved_variables']['variables'], 'SPI data reports error level was saved');
    $this->assertArrayHasKey('time', $vars['acquia_spi_saved_variables'], 'Set time for saved variables');

    // Attemp to set variable that is not whitelisted.
    $current = \Drupal::config('system.site')->get('name');
    $set_variables = ['site_name' => 0];
    $variables->setVariables($set_variables);
    $after = \Drupal::config('system.site')->get('name');
    $this->assertIdentical($current, $after, 'Non-whitelisted variable cannot be automatically set');
    $vars = Json::decode($variables->getVariablesData());
    $this->assertNotContains('site_name', $vars['acquia_spi_saved_variables']['variables'], 'SPI data does not include anything about trying to save clean url');

    // Test override of approved variable list.
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.set_variables_override', FALSE)->save();
    // Variables controller stores old config.
    $variables = new VariablesController();
    $set_variables = ['acquia_spi_set_variables_automatic' => 'test_variable'];
    $variables->setVariables($set_variables);
    $vars = Json::decode($variables->getVariablesData());
    $this->assertArrayNotHasKey('test_variable', $vars, 'Using default list of approved list of variables');
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.set_variables_override', TRUE)->save();
    // Variables controller stores old config.
    $variables = new VariablesController();
    $set_variables = ['acquia_spi_set_variables_automatic' => 'test_variable'];
    $variables->setVariables($set_variables);
    $vars = Json::decode($variables->getVariablesData());
    $this->assertIdentical($vars['acquia_spi_set_variables_automatic'], 'test_variable', 'Altered approved list of variables that can be set');
  }

  /**
   * Helper function determines whether given array contains PHP object.
   */
  protected function isContainObjects($arr) {
    foreach ($arr as $item) {
      if (is_object($item)) {
        return TRUE;
      }
      if (is_array($item) && $this->isContainObjects($item)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Helper function connects to valid subscription.
   */
  protected function connectSite() {
    $edit_fields = [
      'acquia_identifier' => $this->acqtestId,
      'acquia_key' => $this->acqtestKey,
    ];
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentialsPath, $edit_fields, $submit_button);
  }

}
