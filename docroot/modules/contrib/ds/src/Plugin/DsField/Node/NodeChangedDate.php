<?php

namespace Drupal\ds\Plugin\DsField\Node;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Date;

/**
 * Plugin that renders the changed date of a node.
 */
#[DsField(
  id: 'node_changed_date',
  title: new TranslatableMarkup('Last modified'),
  entity_type: 'node',
  provider: 'node'
)]
class NodeChangedDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function getRenderKey() {
    return 'changed';
  }

}
