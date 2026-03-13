#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 3) {
   fwrite(STDERR, "Usage: php fieldFilter [-t | -c] inputFile column_name_1 [column_name_2 ...]\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$rowCount = $csv->getRowCount();
for ($i=0;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   $out = [];
   foreach (array_slice($argv, 2) as $key) {
      if (Str::contains($key, "+")) {
         $left  = Str::substringBefore($key, "+");
         $right = Str::substringAfter ($key, "+");
         $out[] = $row[$left] . "+" . $row[$right];
      }
      else $out[] = $row[$key];
   }
   echo $csv->makeOutputRow($out) . "\n";
}