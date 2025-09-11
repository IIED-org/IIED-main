<?php

namespace Drupal\Tests\name\Functional\Views;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests individual name subfields in Views.
 *
 * @group name
 */
class IndividualNameFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'views',
    'name',
    'name_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A regular user with 'access content' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->user);

    $this->nodes[] = $this->drupalCreateNode([
      'type' => 'name_test',
      'status' => 1,
      'title' => $this->randomString(),
      'field_name' => [
        'title' => 'Mr.',
        'given' => 'John',
        'middle' => 'Quincy',
        'family' => 'Doe',
        'generational' => 'III',
        'credentials' => 'CISSP',
      ],
    ]);
  }

  /**
   * Test the display of individual Name subfields by Views.
   */
  public function testDisplayNameSubfields() {
    $this->drupalGet('individual-name-field-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Mr.');
    $this->assertSession()->pageTextContains('John');
    $this->assertSession()->pageTextContains('Quincy');
    $this->assertSession()->pageTextContains('Doe');
    $this->assertSession()->pageTextContains('III');
    $this->assertSession()->pageTextContains('CISSP');
  }

}
