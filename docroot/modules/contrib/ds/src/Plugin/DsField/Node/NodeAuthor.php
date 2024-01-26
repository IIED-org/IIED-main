<?php

namespace Drupal\ds\Plugin\DsField\Node;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders the author of a node.
 *
 * @DsField(
 *   id = "node_author",
 *   title = @Translation("Author"),
 *   entity_type = "node",
 *   provider = "node"
 * )
 */
class NodeAuthor extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $settings['display_name'] = [
      '#type' => 'checkbox',
      '#title' => 'Show display name instead of raw username',
      '#default_value' => $config['display_name'],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    if (!empty($config['display_name'])) {
      $summary[] = 'Show display name instead of username';
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_name' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity();
    $user = $node->getOwner();

    // User 0 is anonymous.
    if (empty($user->id())) {
      return [
        '#plain_text' => \Drupal::config('user.settings')->get('anonymous'),
      ];
    }

    $field = $this->getFieldConfiguration();
    $author_name = !empty($this->configuration['display_name']) ? $user->getDisplayName() : $user->getAccountName();
    if ($field['formatter'] == 'author') {
      return [
        '#markup' => $author_name,
        '#cache' => [
          'tags' => $user->getCacheTags(),
        ],
      ];
    }

    if ($field['formatter'] == 'author_linked') {
      return [
        '#theme' => 'username',
        '#account' => $user,
        '#cache' => [
          'tags' => $user->getCacheTags(),
        ],
      ];
    }

    // Otherwise return an empty array.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {

    return [
      'author' => $this->t('Author'),
      'author_linked' => $this->t('Author linked to profile'),
    ];
  }

}
