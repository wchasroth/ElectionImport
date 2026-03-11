#!/usr/bin/env php
<?php
declare(strict_types=1);

require "vendor/autoload.php";

use CharlesRothDotNet\Alfred\Str;

echo stream_resolve_include_path("vendor/autoload.php") . "\n";

echo Str::join(["hello", "world"], ", ") . "\n";
