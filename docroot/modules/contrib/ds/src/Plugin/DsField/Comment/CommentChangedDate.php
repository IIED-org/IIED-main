<?php

namespace Drupal\ds\Plugin\DsField\Comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Date;

/**
 * Plugin that renders the changed date of a comment.
 */
#[DsField(
  id: 'comment_changed_date',
  title: new TranslatableMarkup('Last modified'),
  entity_type: 'comment',
  provider: 'comment'
)]
class CommentChangedDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function getRenderKey() {
    return 'changed';
  }

}
