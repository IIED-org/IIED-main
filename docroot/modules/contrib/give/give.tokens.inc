<?php

use Drupal\Core\Render\Markup;

/**
 * Implements hook_tokens().
 *
 * Everything is done automatically, but we need to populate the name, from the
 * donation owner.
 */
function give_tokens($type, $tokens, $data, $options, $bubbleable_metadata) {
  if ($type == 'give_donation') {
    $replacements = [];
    if (isset($tokens['name']) and ($data['give_donation']->getOwnerId())) {
      $replacements['[give_donation:name]'] = Markup::create($data['give_donation']->getOwner()->getDisplayName());
    }
    if (isset($tokens['amount'])) {
      $replacements['[give_donation:amount]'] = format_stripe_currency($data['give_donation']->amount->value);
    }
    return $replacements;
  }
}
