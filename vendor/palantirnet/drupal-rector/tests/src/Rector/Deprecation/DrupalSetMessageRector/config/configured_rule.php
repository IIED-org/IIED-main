<?php declare(strict_types=1);

use DrupalRector\Rector\Deprecation\DrupalSetMessageRector;
use DrupalRector\Tests\Rector\Deprecation\DeprecationBase;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    DeprecationBase::addClass(DrupalSetMessageRector::class, $rectorConfig);
};
