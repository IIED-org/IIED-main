<?php

namespace Drupal\isbn\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines an isbn field mapper.
 *
 * @FeedsTarget(
 *   id = "isbn",
 *   field_types = {"isbn"}
 * )
 */
class Isbn extends FieldTargetBase {}
