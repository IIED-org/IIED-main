<?php

namespace Drupal\Driver\Database\mysql\Install;

use Drupal\Core\Database\Driver\mysql\Install\Tasks as CoreMysqlTasks;

/**
 * Specifies installation tasks for MySQL 5.6 and equivalent databases.
 */
class Tasks extends CoreMysqlTasks {

  const MYSQL_MINIMUM_VERSION = '5.6';
  const MARIADB_MINIMUM_VERSION = '10.1';

}
