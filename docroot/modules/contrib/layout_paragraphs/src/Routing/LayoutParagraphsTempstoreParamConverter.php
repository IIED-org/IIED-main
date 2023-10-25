<?php

namespace Drupal\layout_paragraphs\Routing;

use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository;

/**
 * Layout paragraphs tempstore param converter service.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutParagraphsTempstoreParamConverter implements ParamConverterInterface {

  /**
   * The layout paragraphs layout tempstore.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository
   */
  protected $layoutParagraphsLayoutTempstore;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LayoutParagraphsEditorTempstoreParamConverter.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayoutTempstoreRepository $layout_paragraphs_layout_tempstore
   *   The layout tempstore repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(LayoutParagraphsLayoutTempstoreRepository $layout_paragraphs_layout_tempstore, EntityTypeManagerInterface $entity_type_manager) {
    $this->layoutParagraphsLayoutTempstore = $layout_paragraphs_layout_tempstore;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (empty($defaults['layout_paragraphs_layout'])) {
      return NULL;
    }
    $key = $defaults['layout_paragraphs_layout'];
    return $this->layoutParagraphsLayoutTempstore->getWithStorageKey($key);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['layout_paragraphs_layout_tempstore']);
  }

}
