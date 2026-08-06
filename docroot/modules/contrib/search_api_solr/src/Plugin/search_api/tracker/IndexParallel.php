<?php

namespace Drupal\search_api_solr\Plugin\search_api\tracker;

<<<<<<< HEAD
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiTracker;
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Drupal\search_api\Plugin\search_api\tracker\Basic;

/**
 * Provides a tracker implementation which uses a FIFO-like processing order.
<<<<<<< HEAD
 */
#[SearchApiTracker(
  id: 'index_parallel',
  label: new TranslatableMarkup('Index parallel'),
  description: new TranslatableMarkup('Index parallel tracker which allows to index in parallel.')
)]
=======
 *
 *  @SearchApiTracker(
 *   id = "index_parallel",
 *   label = @Translation("Index parallel"),
 *   description = @Translation("Index parallel tracker which allows to index in parallel.")
 * )
 */
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
class IndexParallel extends Basic {

  const SAFETY_DISTANCE_FACTOR = 3;

  /**
<<<<<<< HEAD
   * The current offset.
   *
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   * @var int
   */
  protected $offset = 0;

  /**
<<<<<<< HEAD
   * The current worker thread.
   *
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   * @var int
   */
  protected $thread = 1;

  /**
<<<<<<< HEAD
   * Sets the current item offset.
   *
   * @param int $offset
   *   The current item offset for the worker thread.
   *
   * @return void
   *   This method does not return a value.
=======
   * @param int $offset
   *
   * @return void
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   */
  public function setOffset(int $offset): void {
    $this->offset = $offset;
  }

  /**
<<<<<<< HEAD
   * Sets the worker thread identifier.
   *
   * @param int $thread
   *   The worker thread identifier.
   *
   * @return void
   *   This method does not return a value.
=======
   * @param int $thread
   *
   * @return void
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   */
  public function setThread(int $thread): void {
    $this->thread = $thread;
  }

<<<<<<< HEAD
  /**
   * Gets the worker thread identifier.
   *
   * @return int
   *   The worker thread identifier.
   */
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
  public function getThread(): int {
    return $this->thread;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItems($limit = -1, $datasource_id = NULL) {
    try {
      $select = $this->createRemainingItemsStatement($datasource_id);
      if ($limit >= 0) {
        $select->range($this->offset, $limit);
      }
      return $select->execute()->fetchCol();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return [];
    }
  }

}
