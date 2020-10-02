<?php

namespace Drupal\publishcontent\Plugin\views\field;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->publishContentAccess = $container->get('publishcontent.access');
    $instance->currentUser = $container->get('current_user');
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
    /* @var \Drupal\Core\Entity\EntityPublishedInterface */
    $entity = $values->_entity;
    // Don't bother adding the link if the access is forbidden.
    if ($this->publishContentAccess->access($this->currentUser, $entity)->isForbidden()) {
      return [];
    }

    if (!$entity instanceof EntityPublishedInterface) {
      return [];
    }

    $langcode = $values->{'node_field_data_langcode'};
    $id = $entity->id();

    if ($entity->isTranslatable() && isset($langcode) && $entity->hasTranslation($langcode)) {
      $url = Url::fromRoute('entity.node.publish_translation', ['node' => $id, 'langcode' => $langcode]);
      $text = $entity->getTranslation($langcode)->isPublished() ? $this->t('Unpublish (this translation)') : $this->t('Publish (this translation)');
    }
    else {
      $url = Url::fromRoute('entity.node.publish', ['node' => $id]);
      $text = $entity->isPublished() ? $this->t('Unpublish') : $this->t('Publish');
    }

    $link = Link::fromTextAndUrl($text, $url);
    $render_array = $link->toRenderable();
    return $this->getRenderer()->render($render_array);
  }

}
