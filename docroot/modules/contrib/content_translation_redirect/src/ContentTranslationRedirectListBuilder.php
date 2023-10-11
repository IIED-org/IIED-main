<?php

namespace Drupal\content_translation_redirect;

use Drupal\content_translation_redirect\Entity\ContentTranslationRedirect;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;

/**
 * Provides a listing of Content Translation Redirect entities.
 */
class ContentTranslationRedirectListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $entity_ids = parent::getEntityIds();

    if ($entity_ids) {
      $default_id = ContentTranslationRedirectInterface::DEFAULT_ID;
      $entity_ids[$default_id] = $default_id;

      foreach ($entity_ids as $entity_id) {
        $parts = explode('__', $entity_id);

        if (isset($parts[1])) {
          $entity_ids[$parts[0]] = $parts[0];
        }
      }
    }
    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Type');
    $header['code'] = $this->t('Redirect status');
    $header['path'] = $this->t('Redirect path');
    $header['mode'] = $this->t('Act on');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $entity */
    $code = $entity->getStatusCode();
    $path = $entity->getPath();

    $row['label'] = $entity->label();
    $row['code'] = $code ? ContentTranslationRedirect::getStatusCodes()[$code] : $this->t('Not specified');
    $row['path'] = $path ? ($path === '/' ? $this->t('Front page') : Link::fromTextAndUrl($path, $entity->getUrl())) : $this->t('Original content');
    $row['mode'] = ContentTranslationRedirect::getTranslationModes()[$entity->getTranslationMode()];
    return $row + parent::buildRow($entity);
  }

}
