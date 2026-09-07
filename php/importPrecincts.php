#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\MichiganCounties;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---importPrecincts.php
//
//   Fill in (presumably empty) s4precincts table.
//
//   Reads data from the TSV of precinct codes, supplied by Liz in a folder
//   supplied in turn by Cinnamon Williams of the MDP, in turn from VAN data.
//
//   Used primarily (only?) in parsing other data, like the clerks or polling place data.

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: php importPrecincts.php inputFile.tsv\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$mc = new MichiganCounties();
$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   $precinct_id = intval($row['Precinct ID']);
   $pctCode     = $row['Precinct Code'];
   $pctName     = $row['Precinct Name'];

   $pct      = intval(substr($pctCode,    -3));
   $pctCode  =        substr($pctCode, 0, -3);
   $ward     = intval(substr($pctCode,    -2));
   $juris_id = intval(substr($pctCode, 0, -2));

   $countyName = Str::substringBefore($pctName, " - ");
   $county = $mc->getNumber($countyName);
   if ($county < 1) fwrite (STDERR, "Bad county: $countyName\n");

   $sqlFields = new SqlFields(['precinct_id' => $precinct_id, 'county_id' => $county, 'juris_id' => $juris_id, 'ward' => $ward, 'pct' => $pct]);
   $sql = "INSERT INTO s4precincts " . $sqlFields->getInsertFragment();
   $result = $pdo->run($sql);
   if ($result->failed())  fwrite (STDERR, "Error: $sql\n");

#  echo "$precinct_id: $county  $juris_id  $ward  $pct  $pctName\n";
}