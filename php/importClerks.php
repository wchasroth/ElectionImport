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

//---importClerks.php
//
//   Fill in (presumably empty) s4clerks table.
//
//   Reads data from the TSV of clerks and polling places, supplied by Liz from
//   a folder supplied by Cinnamon Williams of the MDP, in turn from VAN data.
//   Assumes that importPrecincts.php has already been run, to create table s4precincts
//   to help mapping values from the TSV data.
//
//   Fields used from polling places file:
//      van_precinct_id  (key to s4precincts table)
//      clerk_location
//      clerk_longitude
//      clerk_latitude
//      clerk_address
//      clerk_city
//      clerk_zip
//      clerk_hours (needs processing!!!)

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: php importClerks inputFile.tsv\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$rowCount = $csv->getRowCount();
$prev_location = "";
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   if ($row['clerk_location'] === $prev_location) continue;
   $prev_location = $row['clerk_location'];

   $precinct_id = intval($row['van_precinct_id']);
   $lat = $row['clerk_latitude'];
   $lng = $row['clerk_longitude'];
   $street_address = $row['clerk_address'] . ", " . $row['clerk_city'] . ", Michigan  " . $row['clerk_zip'];

   $sql = "SELECT county_id, juris_id FROM s4precincts WHERE precinct_id = $precinct_id";
   $result = $pdo->run($sql);
   $county_id = $result->getSingleValue('county_id');
   $juris_id  = $result->getSingleValue('juris_id');
#  echo "$county_id $juris_id $prev_location $street_address\n";

   $sql = "SELECT street_address, lat, lng FROM s4clerks WHERE county_code=$county_id AND juris_code=$juris_id";
   $result = $pdo->run($sql);
   if       ($result->failed())           fwrite(STDERR, "Error s4clerks: $sql\n");
   else if ($result->getRowCount() === 0) {
      $fields = new SqlFields(['county_code' => $county_id, 'juris_code' => $juris_id, 'street_address' => $street_address,
         'lat' => $lat, 'lng' => $lng, 'source' => 'van2026-07']);
      $sql = "INSERT INTO s4clerks " . $fields->getInsertFragment();
      $result = $pdo->run($sql);
      if ($result->failed()) fwrite(STDERR, "Insert error: $sql\n");
   }
   else {
      $old = simplifyAddress($result->getSingleValue('street_address'));
      $new = simplifyAddress($street_address);
      if (substr($old, 0, 10) != substr($new, 0, 10)) {
         echo "new: $county_id $juris_id -- $new\n";
         echo "     $county_id $juris_id -- $old\n";
      }
   }

//   $sqlFields = new SqlFields( ['org' => $row['org'], 'office' => $row['office'], 'district' => $row['district'],
//      'subdist' => $row['subdist'], 'termlen' => $row['termlen']]);
//   $result = $pdo->runSF("INSERT INTO v4termlen ", "", $sqlFields);
//   if ($result->failed()) {
//       $error = $result->getError();
//       if (! Str::contains($error, "Duplicate entry"))  echo "$error\n";
//   }
}

function simplifyAddress(string $address): string {
   $address = strtolower($address);
   $address = Str::replaceAll($address, ",", "");
   $address = Str::replaceAll($address, ".", "");
   $words = Str::split($address, " ");
   $address = Str::join($words, " ");
   return trim($address);
}