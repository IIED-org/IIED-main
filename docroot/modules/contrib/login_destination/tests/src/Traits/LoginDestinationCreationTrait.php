<?php

namespace Drupal\Tests\login_destination\Traits;

use Drupal\login_destination\Entity\LoginDestination;

/**
 * Provides methods to create login destination rules.
 *
 * This trait is meant to be used only by test classes.
 */
trait LoginDestinationCreationTrait {

  /**
   * Creates a login destination rule based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the rule, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array.
   *
   * @return \Drupal\login_destination\Entity\LoginDestination
   *   The created login destination rule.
   */
  protected function createLoginDestinationRule(array $settings = []) {
    $settings += [
      'name' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
    ];

    $rule = LoginDestination::create($settings);
    $rule->save();

    return $rule;
  }

}
