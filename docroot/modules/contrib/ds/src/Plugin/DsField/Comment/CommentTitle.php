<?php

namespace Drupal\ds\Plugin\DsField\Comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Field;

/**
 * Plugin that renders the title of a comment.
 */
#[DsField(
  id: 'comment_title',
  title: new TranslatableMarkup('Title'),
  entity_type: 'comment',
  provider: 'comment'
)]
class CommentTitle extends Field {

  /**
   * {@inheritdoc}
   */
  protected function entityRenderKey() {
    return 'subject';
  }

}
