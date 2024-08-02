<?php

namespace Drupal\views_json_source\Event;

use Drupal\views\ViewExecutable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to modify the json content before setting into cache.
 */
class PreCacheEvent extends Event {

  /**
   * Pre cache constant.
   */
  const VIEWS_JSON_SOURCE_PRE_CACHE = 'views_json_source.pre_cache';

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $viewExecutable;

  /**
   * The views json query result body.
   *
   * @var string
   */
  protected $data;

  /**
   * Constructs the object.
   *
   * @param \Drupal\views\ViewExecutable $viewExecutable
   *   The view executable.
   * @param string $data
   *   The views json query result body.
   */
  public function __construct(ViewExecutable $viewExecutable, string $data) {
    $this->viewExecutable = $viewExecutable;
    $this->data = $data;
  }

  /**
   * Get view executable.
   *
   * @return \Drupal\views\ViewExecutable
   *   Return view executable.
   */
  public function getViewExecutable(): ViewExecutable {
    return $this->viewExecutable;
  }

  /**
   * Get view result data.
   *
   * @return string
   *   Return view data.
   */
  public function getViewData(): string {
    return $this->data;
  }

  /**
   * Set the data for this event.
   *
   * @param string $data
   *   The data to override view result data.
   */
  public function setViewData(string $data) {
    $this->data = $data;
  }

}
