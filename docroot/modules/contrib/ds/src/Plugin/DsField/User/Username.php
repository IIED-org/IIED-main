<?php

namespace Drupal\ds\Plugin\DsField\User;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the username.
 */
#[DsField(
  id: 'username',
  title: new TranslatableMarkup('Username'),
  entity_type: 'user',
  provider: 'user'
)]
class Username extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'name';
  }

}
