<?php

namespace Drupal\isbn\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\isbn\IsbnToolsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'isbn_formatted_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "isbn_formatted_formatter",
 *   label = @Translation("ISBN formatted value"),
 *   field_types = {
 *     "isbn"
 *   }
 * )
 */
class IsbnFormattedFormatter extends FormatterBase {

  /**
   * The ISBN Tools service.
   *
   * @var \Drupal\isbn\IsbnToolsServiceInterface
   */
  protected $isbnTools;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $formatter = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $formatter->setIsbnTools($container->get('isbn.isbn_service'));
    return $formatter;
  }

  /**
   * Sets the ISBN Tools service.
   *
   * @param \Drupal\isbn\IsbnToolsServiceInterface $isbn_tools
   *   The ISBN Tools service.
   *
   * @return $this
   */
  public function setIsbnTools(IsbnToolsServiceInterface $isbn_tools): IsbnFormattedFormatter {
    $this->isbnTools = $isbn_tools;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Displays the ISBN value formatted.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $this->isbnTools->format($item->value),
      ];
    }

    return $element;
  }

}
