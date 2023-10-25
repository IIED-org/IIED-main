<?php

namespace Drupal\layout_paragraphs;

use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Layout Paragraphs Layout Tempstore Repository class definition.
 */
class LayoutParagraphsLayoutTempstoreRepository {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * LayoutTempstoreRepository constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The shared tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * Get a layout paragraphs layout from the tempstore.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   A layout paragraphs layout instance.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsLayout
   *   The layout paragraphs layout instance from the tempstore.
   */
  public function get(LayoutParagraphsLayout $layout_paragraphs_layout) {
    $key = $this->getStorageKey($layout_paragraphs_layout);
    $tempstore_layout = $this->getWithStorageKey($key);
    // Editor isn't in tempstore yet, so add it.
    if (empty($tempstore_layout)) {
      $tempstore_layout = $this->set($layout_paragraphs_layout);
    }
    return $tempstore_layout;
  }

  /**
   * Get a layout paragraphs layout frome the tempstore using its storage key.
   *
   * @param string $key
   *   The storage key.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsLayout
   *   The layout.
   */
  public function getWithStorageKey(string $key) {
    return $this->tempStoreFactory->get('layout_paragraphs')->get($key);
  }

  /**
   * Save a layout paragraphs layout to the tempstore.
   */
  public function set(LayoutParagraphsLayout $layout_paragraphs_layout) {
    $key = $this->getStorageKey($layout_paragraphs_layout);
    $this->tempStoreFactory->get('layout_paragraphs')->set($key, $layout_paragraphs_layout);
    return $layout_paragraphs_layout;
  }

  /**
   * Delete a layout from tempstore.
   */
  public function delete(LayoutParagraphsLayout $layout_paragraphs_layout) {
    $key = $this->getStorageKey($layout_paragraphs_layout);
    $this->tempStoreFactory->get('layout_paragraphs')->delete($key);
  }

  /**
   * Returns a unique key for storing the layout.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout object.
   *
   * @return string
   *   The unique key.
   */
  public function getStorageKey(LayoutParagraphsLayout $layout_paragraphs_layout) {
    return $layout_paragraphs_layout->id();
  }

}
