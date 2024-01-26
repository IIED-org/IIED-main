<?php

namespace Drupal\content_translation_redirect;

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
  protected function getEntityIds(): array {
    $entity_ids = parent::getEntityIds();

    // Always load default redirects.
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
  public function buildHeader(): array {
    $header['label'] = $this->t('Type');
    $header['code'] = $this->t('Redirect status');
    $header['path'] = $this->t('Redirect path');
    $header['mode'] = $this->t('Act on');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $entity */
    $code = $entity->getStatusCode();

    $row['label'] = $entity->label();
    $row['code'] = $this->t('Disabled');
    $row['path'] = '—';
    $row['mode'] = '—';

    if ($code !== NULL) {
      $path = $entity->getPath();
      $mode = $entity->getTranslationMode();

      $row['code'] = ContentTranslationRedirectManager::getStatusCodes()[$code] ?? $code;
      $row['mode'] = ContentTranslationRedirectManager::getTranslationModes()[$mode] ?? $mode;

      if ($path === '') {
        $row['path'] = $this->t('Original content');
      }
      elseif ($path === '/') {
        $row['path'] = $this->t('Front page');
      }
      else {
        $row['path'] = Link::fromTextAndUrl($path, $entity->getUrl());
      }
    }

    return $row + parent::buildRow($entity);
  }

}
