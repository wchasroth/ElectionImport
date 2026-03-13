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
   if ($result->failed()) echo $result->getError() . "\n";
}