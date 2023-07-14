<?php

namespace Drupal\login_destination;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface providing additional methods.
 */
interface LoginDestinationInterface extends ConfigEntityInterface {

  /**
   * Get short description of the login destination rule.
   *
   * @return string
   *   Login destination rule description.
   */
  public function label();

  /**
   * Get machine name for destination rule.
   *
   * @return string
   *   Login destination rule machine name.
   */
  public function getMachineName();

  /**
   * Get login destination.
   *
   * @return string
   *   Destination path.
   */
  public function getDestination();

  /**
   * Get Login Destination Rule triggers.
   *
   * @return array
   *   List of triggers.
   */
  public function getTriggers();

  /**
   * Get Type of pages.
   *
   *  - 0: all pages except listed pages.
   *
   *  - 1: only listed pages.
   *
   * @return int
   *   Type of pages.
   */
  public function getPagesType();

  /**
   * Get pages.
   *
   * @return array
   *   List of pages.
   */
  public function getPages();

  /**
   * Get Login destination roles.
   *
   * @return array
   *   List of roles.
   */
  public function getRoles();

  /**
   * Get Login destination rule weight.
   *
   * @return int
   *   Weight value.
   */
  public function getWeight();

  /**
   * Prepare list of triggers for displaying.
   *
   * @return string
   *   HTML view for list of triggers.
   */
  public function viewTriggers();

  /**
   * Prepare list of roles for displaying.
   *
   * @return mixed
   *   HTML view for list of roles.
   */
  public function viewRoles();

  /**
   * Prepare list of pages for displaying.
   *
   * @return string
   *   HTML view for list of paths.
   */
  public function viewPages();

  /**
   * Prepare redirect destination for displaying.
   *
   * @return string
   *   Destination path.
   */
  public function viewDestination();

  /**
   * Return status.
   *
   * @return bool
   *   TRUE for enabled, FALSE otherwise.
   */
  public function isEnabled();

}
