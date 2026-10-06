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

//---importPollingPlaces.php
//
//   Fill in (presumably empty) s4pollingplaces table.
//
//   Reads data from the TSV of clerks and polling places, supplied by Liz from
//   a folder supplied by Cinnamon Williams of the MDP, in turn from VAN data.
//   Assumes that importPrecincts.php has already been run, to create table s4precincts
//   to help mapping values from the TSV data.
//
//   Fields used from polling places file:
//      van_precinct_id  (key to s4precincts table)
//      eday_location
//      eday_longitude, eday_latitude
//      eday_address, eday_city, eday_zip

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: php importPollingPlaces.php inputFile.tsv\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);

   $precinct_id = intval($row['van_precinct_id']);
   $lat = $row['eday_latitude'];
   $lng = $row['eday_longitude'];
   $location = ucwords(strtolower($row['eday_location']));
   $fullAddress = $row['eday_address'] . ", " . $row['eday_city'] . ", MI " . $row['eday_zip'];

   $sqlFields = new SqlFields(['van_precinct_id' => $precinct_id, 'location' => $location,
      'lat' => $lat, 'lng' => $lng, 'address' => $fullAddress]);
   $result = $pdo->runSF("INSERT INTO s4pollingplaces", "", $sqlFields);
   if ($pdo->failed()) fwrite(STDERR, "Error: " . $result->getError() . "\n");
}