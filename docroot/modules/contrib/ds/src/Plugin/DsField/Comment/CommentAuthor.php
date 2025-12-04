<?php

namespace Drupal\ds\Plugin\DsField\Comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the author of a comment.
 */
#[DsField(
  id: 'comment_author',
  title: new TranslatableMarkup('Author'),
  entity_type: 'comment',
  provider: 'comment'
)]
class CommentAuthor extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $field = $this->getFieldConfiguration();

    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity();

    $account = $comment->getOwner();
    if (!empty($field['formatter']) && $field['formatter'] == 'author_linked') {
      $output = [
        '#theme' => 'username',
        '#account' => $account,
      ];
    }
    else {
      $output = [
        '#markup' => $account->getAccountName(),
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {
    return [
      'author' => $this->t('Author'),
      'author_linked' => $this->t('Author linked to profile or homepage'),
    ];
  }

}
