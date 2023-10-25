<?php

/**
 * @file
 * Theme and preprocess functions for comments.
 */

/**
 *
 */
function radix_theme_suggestions_comment_alter(array &$suggestions, array $variables) {
  /** @var \Drupal\comment\CommentInterface $comment */
  $comment = $variables['elements']['#comment'];
  $commented_entity = $comment
    ->getCommentedEntity();
  $variables['comment'] = $comment;
  $variables['commented_entity'] = $commented_entity;

  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
  // Add view mode theme suggestions.
  $suggestions[] = 'comment__' . $sanitized_view_mode;
}
