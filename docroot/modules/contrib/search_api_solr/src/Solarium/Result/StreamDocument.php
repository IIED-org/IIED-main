<?php

namespace Drupal\search_api_solr\Solarium\Result;

use Solarium\Core\Query\AbstractDocument;

/**
 * Stream result Solr document.
 */
class StreamDocument extends AbstractDocument {

  /**
   * Constructor.
   *
<<<<<<< HEAD
   * @param array<string, mixed> $fields
=======
   * @param array $fields
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   *   The array of fields.
   */
  public function __construct(array $fields) {
    $this->fields = $fields;
  }

  /**
   * Sets a field value.
   *
   * @param string $name
   *   The field name.
   * @param mixed $value
   *   The field value.
   */
  public function __set($name, $value): void {
    $this->fields[$name] = $value;
  }

<<<<<<< HEAD
  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
=======
  #[\ReturnTypeWillChange]

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    return $this->getFields();
  }

}
