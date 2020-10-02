<?php

namespace Drupal\publishcontent\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local task plugin with a dynamic title.
 */
class PublishContentLocalTask extends LocalTaskDefault {
  use StringTranslationTrait;

  /**
   * Current node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $this->node = Node::load($route_match->getRawParameter('node'));

    return parent::getRouteParameters($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($this->node->isTranslatable() && $this->node->hasTranslation($langcode) && $translatedNode = $this->node->getTranslation($langcode)) {
      $translatedNode->setPublished($translatedNode->isPublished());
      return $translatedNode->isPublished() ? $this->t('Unpublish (this translation)') : $this->t('Publish (this translation)');
    }
    return $this->node->isPublished() ? $this->t('Unpublish') : $this->t('Publish');
  }

}
