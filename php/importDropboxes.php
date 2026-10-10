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

//---importDropboxes.php
//
//   Fill in s4dropboxes and s4van2drop tables.
//
//   (s4dropboxes must have completely recreated as empty)
//
//   Reads data from the TSV of the drop-boxes, supplied by Liz from
//   a folder supplied by Cinnamon Williams of the MDP, in turn from VAN data.
//   Assumes that importPrecincts.php has already been run, to create table s4precincts
//   to help mapping values from the TSV data.
//
//   Fields used from drop-box file:
//      location  (fire station #4)
//      address (street)
//      city
//      zip
//      hours
//      directions
//      van_precinct_id (comma-separated list)

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: php importDropboxes.php inputFile.tsv\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

$pdo->run("DELETE FROM s4van2drop");

$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   if ($row['address'] == '')  continue;

   $fullAddress = $row['location'];
   if (!empty($fullAddress))  $fullAddress .= ", ";
   $fullAddress .= $row['address'] . ", " . $row['city'] . ", MI " . $row['zip'];

   $hours = $row['hours'];
   $directions = $row['directions'];

   $sqlFields = new SqlFields(['address' => $fullAddress, 'hours' => $hours, 'directions' => $directions]);
   $result = $pdo->runSF("INSERT INTO s4dropboxes", "", $sqlFields, true);
   if ($result->failed()) {
      fwrite(STDERR, "Error: " . $result->getError() . "\n");
      exit(1);
   }
   $lastId = $result->getInsertId();

   $vanIds = Str::split($row['van_precinct_id'], ",");
   foreach ($vanIds as $vanId) {
      $sql = "INSERT INTO s4van2drop (van_precinct_id, drop_id) VALUES ($vanId, $lastId)";
      $result = $pdo->run($sql);
      if ($result->failed()) {
         fwrite(STDERR, "s4van2drop error: " . $result->getError() . "\n");
      }
   }

}
