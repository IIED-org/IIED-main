<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
      __DIR__ . '/src',
      __DIR__ . '/tests',
    ]);
    $rectorConfig->skip(['*/upgrade_status/tests/modules/*']);
    $rectorConfig->skip(['*/node_modules/*']);
    $rectorConfig->fileExtensions(['php', 'module', 'theme', 'install', 'profile', 'inc', 'engine']);

    // define sets of rules
    $rectorConfig->sets([
      LevelSetList::UP_TO_PHP_74
    ]);
};
