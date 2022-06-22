<?php

namespace Drupal\default_content_deploy\Event;

/**
 * Defines events for the Default Content Deploy module.
 */
final class DefaultContentDeployEvents {

  /**
   * Alter the entity before it will be serialized.
   *
   * @Event
   */
  const PRE_SERIALIZE = PreSerializeEvent::class;

}
