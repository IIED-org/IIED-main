<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Verifies Name installs and operates with the test install profile.
 *
 * @group name
 * @group #slow
 */
class NameInstallProfileTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'name_test_profile';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Verifies the profile installs the Name module.
   */
  public function testProfileInstallsNameModule(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('name'));
  }

  /**
   * Verifies fresh install defaults expected by user_load hook logic.
   */
  public function testFreshInstallHasNoPreferredNameField(): void {
    $this->assertSame('', \Drupal::config('name.settings')->get('user_preferred'));
  }

  /**
   * Verifies user 1 can be loaded without install-time hook failures.
   */
  public function testUserOneLoadsWithoutError(): void {
    $account = User::load(1);
    $this->assertNotNull($account);
    $this->assertSame(1, (int) $account->id());
  }

  /**
   * Verifies the Name settings page is reachable post-install.
   */
  public function testNameSettingsPageIsReachable(): void {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/config/regional/name');
    $this->assertSession()->statusCodeEquals(200);
  }

}
