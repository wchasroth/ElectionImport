#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---fieldSum.   Add up the total number of votes for each candidate.
//
//   Used to transform a county elections report CSV file, with multiple rows
//   for EACH contest and candidate (i.e. distinct rows for the # of votes in
//   individual precincts), into the same format CSV file, but with the number
//   of votes for each contest/candidate added together.  Thus the output has
//   only ONE row per contest/candidate.
//
//   Assumes the first row contains the column names.
//
//   We need a set of "key" columns that uniquely identify a person and contest,
//   so that it knows WHO to sum up the votes for.  In most cases, contest_name
//   and choice_name together should suffice.  So typical usage would look like
//      fieldSum.php inputFile contest_name+choice_name  total_votes

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 4) {
   fwrite(STDERR, "Usage: fieldSum.php [-t | -c] inputFile keycol1+keycol2+... column_name_to_sum\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

echo $csv->makeOutputRow($csv->getKeys()) . "\n";

$keyCols = Str::split($argv[2], "+");
$addCol  = $argv[3];

$summedRows = [];
$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   $key = makeKey($keyCols, $row);
   if (! isset($summedRows[$key]))   $summedRows[$key] = $row;
   else {
       $oldRow = $summedRows[$key];
       $votes = intval($oldRow[$addCol]) + intval($row[$addCol]);
       $summedRows[$key][$addCol] = strval($votes);
   }
}

foreach ($summedRows as $row) {
    echo $csv->makeOutputRow(array_values($row)) . "\n";
}

function makeKey (array $keyCols, array $row) {
   $result = [];
   foreach ($keyCols as $col) $result[] = $row[$col];
   return Str::join($result, "+");
}