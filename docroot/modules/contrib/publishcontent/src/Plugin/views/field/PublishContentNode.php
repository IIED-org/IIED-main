<?php

namespace Drupal\publishcontent\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("publishcontent_node")
 */
class PublishContentNode extends FieldPluginBase {

  /**
   * The publishcontent access service.
   *
   * @var \Drupal\publishcontent\Access\PublishContentAccess
   */
  protected $publishContentAccess;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module configuration for reading.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->publishContentAccess = $container->get('publishcontent.access');
    $instance->currentUser = $container->get('current_user');
    $instance->config = $container->get('config.factory')->get('publishcontent.settings');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $values->_entity;

    // Sanity check.
    if (!$node instanceof NodeInterface) {
      return '';
    }

    // Don't bother adding the link if the access is forbidden.
    if ($this->publishContentAccess->access($this->currentUser, $node)->isForbidden()) {
      return '';
    }

    $langcode = $values->{'node_field_data_langcode'} ?? '';
    $id = $node->id();

    if ($node->isTranslatable() && !empty($langcode) && $node->hasTranslation($langcode)) {
      $url = Url::fromRoute('entity.node.publish_translation',
        ['node' => $id, 'langcode' => $langcode]);
      $text = $node->getTranslation($langcode)->isPublished() ? $this->config->get('unpublish_text_value') : $this->config->get('publish_text_value');
    }
    else {
      $url = Url::fromRoute('entity.node.publish', ['node' => $id]);
      $text = $node->isPublished() ? $this->config->get('unpublish_text_value') : $this->config->get('publish_text_value');
    }

    $link = Link::fromTextAndUrl($text, $url);
    $render_array = $link->toRenderable();
    return $this->getRenderer()->render($render_array);
  }

}
