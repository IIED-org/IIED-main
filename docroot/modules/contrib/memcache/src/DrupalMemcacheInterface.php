<?php

namespace Drupal\memcache;

/**
 * Define the interface for drupal memcache.
 */
interface DrupalMemcacheInterface {

  /**
   * Adds an item into memcache.
   *
   * @param string $key
   *   The string with which you will retrieve this item later.
   * @param mixed $value
   *   The item to be stored.
   * @param int $exp
   *   Parameter expire is expiration time in seconds. If it's 0, the item never
   *   expires (but memcached server doesn't guarantee this item to be stored
   *   all the time, it could be deleted from the cache to make place for other
   *   items).
   * @param bool $flag
   *   If using the older memcache PECL extension as opposed to the newer
   *   memcached PECL extension, the MEMCACHE_COMPRESSED flag can be set to use
   *   zlib to store a compressed copy of the item.  This flag option is
   *   completely ignored when using the newer memcached PECL extension.
   *
   * @return bool
   *   Whether or not the add was successful.
   */
  public function set($key, $value, $exp = 0, $flag = FALSE);

  /**
   * Retrieves a value from Memcache.
   *
   * @param string $key
   *   The key with which the item was stored.
   *
   * @return mixed
   *   The item that was originally saved, or FALSE otherwise.
   */
  public function get($key);

  /**
   * Retrieves multiple values from Memcache.
   *
   * @param array $keys
   *   An array of keys for items to retrieve.
   *
   * @return array
   *   An array of stored items, or FALSE otherwise.
   */
  public function getMulti(array $keys);

  /**
   * Deletes an item from Memcache.
   *
   * @param string $key
   *   The key to delete from storage.
   *
   * @return bool
   *   TRUE on success or FALSE on failure.
   */
  public function delete($key);

  /**
   * Add an item to Memcache if it doesn't exist already.
   *
   * @param string $key
   *   The key to add.
   * @param mixed $value
   *   The value to add.
   * @param int $expire
   *   The expiration time in seconds.
   *
   * @return bool
   *   TRUE on success or FALSE on failure.
   */
  public function add($key, $value, $expire = 0);

  /**
   * Prepares the memcache key.
   *
   * @param string $key
   *   The raw cache key.
   *
   * @return string
   *   The prepared cache key.
   */
  public function key($key);

  /**
   * Immediately invalidates all existing items.
   *
   * Flush doesn't actually free any resources, it only marks all the
   * items as expired, so occupied memory will be overwritten by new items.
   *
   * @return bool
   *   TRUE on success or FALSE on failure.
   */
  public function flush();

}
