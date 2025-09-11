<?php

namespace Drupal\ds\Plugin\DsField\Node;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the links of the node entity.
 */
#[DsField(
  id: 'node_links',
  title: new TranslatableMarkup('Links'),
  entity_type: 'node',
  provider: 'node'
)]
class NodeLinks extends DsFieldBase {

}
