<?php

namespace Drupal\clamav\Scanner;

use Drupal\clamav\Config;
use Drupal\clamav\Scanner;
use Drupal\clamav\ScannerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\file\FileInterface;

/**
 * Executable class for the ClamAV scanner instance.
 */
class Executable implements ScannerInterface {

  use LoggerChannelTrait;

  private $_executable_path = '';
  private $_executable_parameters = '';

  /**
   * File variable.
   *
   * @var string
   */
  private $_file = '';

  /**
   * Virus name variable.
   *
   * @var string
   */
  protected $_virus_name = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config) {
    $this->_executable_path       = $config->get('mode_executable.executable_path');
    $this->_executable_parameters = $config->get('mode_executable.executable_parameters');
  }

  /**
   * {@inheritdoc}
   */
  public function scan(FileInterface $file) {
    // Verify that the executable exists.
    if (!file_exists($this->_executable_path)) {
      $this->getLogger('clamav')->warning('Unable to find ClamAV executable at @executable_path', ['@executable_path' => $this->_executable_path]);
      return Scanner::FILE_IS_UNCHECKED;
    }

    // Redirect STDERR to STDOUT to capture the full output of the ClamAV script.
    $script = "{$this->_executable_path} {$this->_executable_parameters}";
    $filename = \Drupal::service('file_system')->realpath($file->getFileUri());
    $cmd = escapeshellcmd($script) . ' ' . escapeshellarg($filename) . ' 2>&1';

    // Text output from the executable is assigned to: $output
    // Return code from the executable is assigned to: $return_code.
    // Possible return codes (see `man clamscan`):
    // - 0 = No virus found.
    // - 1 = Virus(es) found.
    // - 2 = Some error(s) occurred.
    // Note that older versions of clamscan (prior to 0.96) may have return
    // values greater than 2. Any value of 2 or greater means that the scan
    // failed, and the file has not been checked.
    exec($cmd, $output, $return_code);
    $output = implode("\n", $output);

    switch ($return_code) {
      case 0:
        return Scanner::FILE_IS_CLEAN;

      case 1:
        return Scanner::FILE_IS_INFECTED;

      default:
        return Scanner::FILE_IS_UNCHECKED;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function virus_name() {
    return $this->_virus_name;
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    if (file_exists($this->_executable_path)) {
      return exec(escapeshellcmd($this->_executable_path) . ' -V');
    }
    else {
      return NULL;
    }
  }

}
