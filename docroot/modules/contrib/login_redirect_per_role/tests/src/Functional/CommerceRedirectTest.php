<?php

namespace Drupal\Tests\login_redirect_per_role\Functional;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\commerce\Traits\CommerceBrowserTestTrait;
use Drupal\user\UserInterface;

/**
 * Tests redirects after login in a commerce checkout.
 *
 * @group login_redirect_per_role
 */
class CommerceRedirectTest extends BrowserTestBase {

  use CommerceBrowserTestTrait;
  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'commerce_cart',
    'commerce_checkout',
    'commerce_product',
    'commerce_store',
    'login_redirect_per_role_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The commerce product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected ProductInterface $product;

  /**
   * A user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->createStore()],
    ]);

    $this->account = $this->createUser();

    $this->config('login_redirect_per_role.settings')
      ->set('login.authenticated', [
        'allow_destination' => TRUE,
        'redirect_url' => '<front>',
        'weight' => 0,
      ])
      ->save();
  }

  /**
   * Tests the login redirect in a commerce checkout.
   */
  public function testCommerceCheckout(): void {
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    $this->submitForm([
      'login[returning_customer][name]' => $this->account->getAccountName(),
      'login[returning_customer][password]' => $this->account->passRaw,
    ], 'Log in');

    $this->assertSession()->addressMatches('/\/checkout\/\d+\/order_information/');
  }

}
