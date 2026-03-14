#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---importTermLen.
//
//   Reads from the 'vishu' extended format county files, and uses the 'termlen' values there
//   to populate the v4termlen table.  This provides the secondary source of termlen by
//   org/office/district/subdist, that is in turn used by phase08TermLenFinder.php.
//
//   It does a lot of duplicate inserts (since we don't care about seatnum), but those
//   fail cheerfully -- which is fine.

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: importTermLen inputFile\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   $termlen = $row['termlen'];
   if (intval($termlen) === 0)  continue;

   $sqlFields = new SqlFields( ['org' => $row['org'], 'office' => $row['office'], 'district' => $row['district'],
      'subdist' => $row['subdist'], 'termlen' => $row['termlen']]);
   $result = $pdo->runSF("INSERT INTO v4termlen ", "", $sqlFields);
   if ($result->failed()) {
       $error = $result->getError();
       if (! Str::contains($error, "Duplicate entry"))  echo "$error\n";
   }
}