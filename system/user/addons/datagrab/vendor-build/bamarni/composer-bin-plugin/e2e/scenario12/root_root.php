<?php

declare (strict_types=1);
namespace BoldMinded\DataGrab\Dependency;

require __DIR__ . '/vendor/autoload.php';
use BoldMinded\DataGrab\Dependency\Composer\InstalledVersions;
echo "Get versions installed in root; executed from root." . \PHP_EOL;
echo InstalledVersions::getPrettyVersion('psr/log') . \PHP_EOL;
