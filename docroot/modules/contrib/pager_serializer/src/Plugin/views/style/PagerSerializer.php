<?php

namespace Drupal\pager_serializer\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "pager_serializer",
 *   title = @Translation("Pager serializer"),
 *   help = @Translation("Serializes views row data and pager using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class PagerSerializer extends Serializer {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pager_serializer.settings';

  /**
   * Pager None class.
   *
   * @var string
   */
  const PAGER_NONE = 'Drupal\views\Plugin\views\pager\None';

  /**
   * Pager Some class.
   *
   * @var string
   */
  const PAGER_SOME = 'Drupal\views\Plugin\views\pager\Some';

  /**
   * {@inheritdoc}
   */
  public function render() {

    $rows = [];
    $config = \Drupal::config(static::SETTINGS);
    $rows_label = $config->get('rows_label');
    $use_pager = $config->get('pager_object_enabled');

    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if (method_exists($this->displayHandler, 'getContentType')) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $pagination = $this->pagination($config, $rows);
    if ($use_pager) {
      $pager_label = $config->get('pager_label');
      $result = [
        $rows_label => $rows,
        $pager_label => $pagination,
      ];
    }
    else {
      $result = $pagination;
      $result[$rows_label] = $rows;
    }

    return $this->serializer->serialize($result, $content_type, ['views_style_plugin' => $this]);
  }

  /**
   * {@inheritdoc}
   */
  protected function pagination($config, $rows) {

    $pagination = [];
    $current_page = 0;
    $items_per_page = 0;
    $total_items = 0;
    $total_pages = 1;
    $class = NULL;

    $pager = $this->view->pager;

    if ($pager) {
      $items_per_page = $pager->getItemsPerPage();
      $total_items = $pager->getTotalItems();
      $class = get_class($pager);
    }

    if ($class === NULL) {
      return NULL;
    } else {
      if (method_exists($pager, 'getPagerTotal')) {
        $total_pages = $pager->getPagerTotal();
      }
      if (method_exists($pager, 'getCurrentPage')) {
        $current_page = $pager->getCurrentPage();
      }
      if ($class == static::PAGER_NONE) {
        $items_per_page = $total_items;
      }
      elseif ($class == static::PAGER_SOME) {
        $total_items = count($rows);
      }
    }

    if ($config->get('current_page_enabled')) {
      $current_page_label = $config->get('current_page_label');
      $pagination[$current_page_label] = $current_page;
    }
    if ($config->get('total_items_enabled')) {
      $total_items_label = $config->get('total_items_label');
      $pagination[$total_items_label] = $total_items;
    }
    if ($config->get('total_pages_enabled')) {
      $total_pages_label = $config->get('total_pages_label');
      $pagination[$total_pages_label] = $total_pages;
    }
    if ($config->get('items_per_page_enabled')) {
      $items_per_page_label = $config->get('items_per_page_label');
      $pagination[$items_per_page_label] = $items_per_page;
    }

    return $pagination;
  }

}
