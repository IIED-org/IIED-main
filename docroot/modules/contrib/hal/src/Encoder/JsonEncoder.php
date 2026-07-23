<?php

namespace Drupal\hal\Encoder;

use Drupal\serialization\Encoder\JsonEncoder as SerializationJsonEncoder;

/**
 * Encodes HAL data in JSON.
 *
 * Simply respond to hal_json format requests using the JSON encoder.
 *
 * @phpstan-ignore-next-line classExtendsInternalClass.classExtendsInternalClass
 */
class JsonEncoder extends SerializationJsonEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected static $format = ['hal_json'];

}
