<?php

declare(strict_types=1);

namespace Drupal\Tests\tfa\Functional;

use Drupal\Core\Url;
use Drupal\tfa_test_user\Entity\TfaTestUser;

/**
 * Tests login controller output.
 *
 * @group tfa
 * @coversDefaultClass \Drupal\tfa\Controller\TfaLoginController
 */
final class TfaLoginControllerTest extends TfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tfa_test_user',
  ];

  /**
   * Test the most basic login controller output.
   *
   * Tests with custom user entity bundle to ensure loadable with strict types.
   */
  public function testBasic(): void {
    $this->config('tfa.settings')
      ->set('enabled', TRUE)
      ->set('required_roles', ['authenticated' => 'authenticated'])
      ->save();

    $user = $this->createUser([
      'setup own tfa',
    ]);
    $this->assertInstanceOf(TfaTestUser::class, $user);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('tfa.overview', ['user' => $user->id()]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('<h1>TFA</h1>');
    $this->assertSession()->pageTextContains('Number of times validation skipped: 0 of 3');
  }

}

