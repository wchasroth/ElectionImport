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

//---importEarlyVoting.php
//
//   Fill in (presumably empty) s4earlyvoting and s4van2early tables.
//
//   Reads data from the TSV of the early voting sites, supplied by Liz from
//   a folder supplied by Cinnamon Williams of the MDP, in turn from VAN data.
//   Assumes that importPrecincts.php has already been run, to create table s4precincts
//   to help mapping values from the TSV data.
//
//   Fields used from early-voting file:
//      location  (e.g. fire station #4)
//      address (street)
//      city_state_zip
//      hours
//      latitude, longitude
//      van_precinct_id (comma-separated list)

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: php importEarlyVoting.php inputFile.tsv\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);

   $address = $row['address'] . ", " . $row['city_state_zip'];
   $address = Str::replaceFirst($address, ' Michigan ', ' MI ');

   $hours = $row['hours'];

   $sqlFields = new SqlFields(['location' => ucwords($row['location']), 'address' => $address, 'hours' => $hours,
      'lat' => $row['latitude'], 'lng' => $row['longitude']]);
   $result = $pdo->runSF("INSERT INTO s4earlyvoting", "", $sqlFields, true);
   if ($result->failed()) {
      fwrite(STDERR, "Error: " . $result->getError() . "\n");
      exit(1);
   }
   $lastId = $result->getInsertId();

   $vanIds = Str::split($row['van_precinct_id'], ",");
   foreach ($vanIds as $vanId) {
      $sql = "INSERT INTO s4van2early (van_precinct_id, early_id) VALUES ($vanId, $lastId)";
      $result = $pdo->run($sql);
      if ($result->failed()) {
         fwrite(STDERR, "s4van2early error: " . $result->getError() . "\n");
      }
   }

}