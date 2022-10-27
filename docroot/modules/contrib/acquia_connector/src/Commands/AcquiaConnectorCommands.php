<?php

namespace Drupal\acquia_connector\Commands;

use Drupal\acquia_connector\SiteProfile\SiteProfileReport;
use Drupal\acquia_connector\SiteProfile\TestStatusController;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 *
 * Drush integration for the Acquia Connector module.
 */
class AcquiaConnectorCommands extends DrushCommands {

  /**
   * The SPI Report Service.
   *
   * @var \Drupal\acquia_connector\SiteProfile\SiteProfileReport
   */
  protected $spi;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * AcquiaConnectorCommands constructor.
   *
   * @param \Drupal\acquia_connector\SiteProfile\SiteProfileReport $spi
   *   The SPI controller.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(SiteProfileReport $spi, ModuleHandlerInterface $module_handler) {
    parent::__construct();

    $this->spi = $spi;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Output raw Acquia SPI data.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option outfile
   *   Optional. A file to write data to in the current directory. If omitted
   *   Drush will output to stdout.
   * @option format
   *   Optional. Format may be json, print_r, or var_dump. Defaults to print_r.
   *
   * @command acquia:connector:spi-get
   *
   * @usage acquia:connector:spi-get --format=json --outfile=spi.json
   *   Write JSON encoded SPI data to spi.json in current directory.
   */
  public function spiGet(array $options = ['outfile' => NULL, 'format' => NULL]) {

    $raw_spi = $this->spi->get();

    switch ($options['format']) {
      case 'json':
        $spi = Json::encode($raw_spi);
        break;

      case 'var_dump':
      case 'var_export':
        $spi = var_export($raw_spi, TRUE);
        break;

      case 'print_r':
      default:
        $spi = print_r($raw_spi, TRUE);
        break;
    }

    if (!$options['outfile']) {
      $this->output->writeln($spi);
      return;
    }

    $file = $options['outfile'];
    // Path is relative.
    if (strpos($file, DIRECTORY_SEPARATOR) !== 0) {
      $file = ($_SERVER['PWD'] ?? getcwd()) . DIRECTORY_SEPARATOR . $file;
    }
    if (file_put_contents($file, $spi)) {
      $this->logger->info('SPI Data written to @outfile.', ['@outfile' => realpath($file)]);
    }
    else {
      $this->logger->error('Unable to write SPI Data into @outfile.', ['@outfile' => realpath($file)]);
    }

  }

  /**
   * A command callback and drush wrapper for custom test validation.
   *
   * @command acquia:connector:spi-test-validate
   *
   * @aliases acquia:connector:spi-tv
   *
   * @usage acquia:connector:spi-test-validate
   *   Perform a validation check on any modules with Acquia SPI custom tests.
   *
   * @validate-module-enabled acquia_connector
   */
  public function customTestValidate() {

    $results = [];
    $testStatus = new TestStatusController();

    // Iterate through modules which contain hook_acquia_spi_test().
    // Todo: Remove after Drupal 9.4 is minimum version.
    if (method_exists($this->moduleHandler, 'invokeAllWith')) {
      $this->moduleHandler->invokeAllWith('acquia_connector_spi_test', function (callable $hook, string $module) use ($testStatus, &$results) {
        $results[$module] = $testStatus->testValidate($hook());
      });
    }
    else {
      //@phpstan-ignore-next-line
      foreach ($this->moduleHandler->getImplementations('acquia_connector_spi_test') as $module) {
        $function = $module . '_acquia_connector_spi_test';
        if (function_exists($function)) {
          $results[$module] = $testStatus->testValidate($function());
        }
      }
    }

    if (empty($results)) {
      $this->output->writeln((string) dt('No Acquia SPI custom tests were detected.'));
      return;
    }
    else {
      $pass = [];
      $failure = [];

      $this->output->writeln((string) dt('Acquia SPI custom tests were detected in: @modules ' . PHP_EOL, ['@modules' => implode(', ', array_keys($results))]));
      foreach ($results as $module => $result) {
        if (!$result['result']) {
          $failure[] = $module;
          $this->output->writeln((string) dt("[FAILED]  Validation failed for '@module' and has been logged.", ['@module' => $module]));

          foreach ($result['failure'] as $test_name => $test_failures) {
            foreach ($test_failures as $test_param => $test_value) {
              $variables = [
                '@module_name' => $module,
                '@message' => $test_value['message'],
                '@param_name' => $test_param,
                '@test_name' => $test_name,
                '@value' => $test_value['value'],
              ];
              $this->output->writeln((string) dt("[DETAILS] @message for parameter '@param_name'; current value '@value'. (Test @test_name in module @module_name)", $variables));
              $this->logger->error("<em>Custom test validation failed</em>: @message for parameter '@param_name'; current value '@value'. (<em>Test '@test_name' in module '@module_name'</em>)", $variables);
            }
          }
        }
        else {
          $pass[] = $module;
          $this->output->writeln((string) dt("[PASSED]  Validation passed for '@module.'", ['@module' => $module]));
        }

        $this->output->writeln('');
      }
    }

    $this->output->writeln((string) dt('Validation checks completed.'));
    $variables = [];
    if (count($pass) > 0) {
      $variables['@passes'] = implode(', ', $pass);
      $variables['@pass_count'] = count($pass);
      $this->output->writeln((string) dt('@pass_count module(s) passed validation: @passes.'), $variables);
    }

    if (count($failure) > 0) {
      $variables['@failures'] = implode(', ', $failure);
      $variables['@fail_count'] = count($failure);
      $this->output->writeln((string) dt('@fail_count module(s) failed validation: @failures.'), $variables);
    }
  }

}
