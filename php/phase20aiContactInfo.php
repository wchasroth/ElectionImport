#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

// phase20aiContactInfo
//
// Import contact info, found by AI agents, from a TSV spreadsheet.
// Add to v4incumbents fields where those fields are EMPTY.
// (I.e. previously-existing values are assumed to take precedence.)

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: phase20aiContactInfo.php inputFile\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();

// 0   1         2      3       4         5        6     7        8      9      10   11        12    13      14             15                 15              16
//id org  district locale  office   seatnum  subdist  name  elected  party votes_c  pct   termlen  open  region   office_title
//                 16              17              18               19            20
//   official_website  official_email  official_phone official_address  headshot_url

//  org
//  office
//  locale -> district
//  subdist
//  name  (lowercase, space->dash, punctuation removed, same extension as parsed from url

$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);

   $org      = $row['org'];
   $office   = $row['office'];
   $district = $row['district'];
   $subdist  = $row['subdist'];
   $name     = $row['name'];

   $fields = ['s.org' => $org, 's.office' => $office, 's.district' => $district, 's.subdist' => $subdist, 'i.name' => $name];
   $sqlFields = new SqlFields($fields);

   $sql = "SELECT i.id "
        . "  FROM      v4incumbents AS i "
        . "  LEFT JOIN v4seats      AS s  ON (s.id = i.seat_id) "
        . " WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   if ($result->succeeded() && $result->getRowCount() == 1) {
      $id = intval($result->getRows()[0]['id']);
      $contactFields = ['web' => $row['web'], 'email' => $row['email'], 'phone' => $row['phone'], 'address' => $row['address'], 'headshot' => $row['headshot']];
      foreach ($contactFields as $field => $value) {
         if (!empty ($value)  &&  $value !== "NOT_FOUND") {
            $sql = "UPDATE v4incumbents SET $field = '$value' WHERE id = $id AND $field = ''";
            echo "$sql\n";
//          $pdo->run($sql);
         }
      }
   }
}
