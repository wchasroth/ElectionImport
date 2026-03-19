#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\Csv;
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

$rows = Csv::loadTrimmed(STDIN, "\t");

// 0   1         2      3       4         5        6     7        8      9      10   11        12    13      14             15                 15              16
//id org  district locale  office   seatnum  subdist  name  elected  party votes_c  pct   termlen  open  region   office_title
//                 16              17              18               19            20
//   official_website  official_email  official_phone official_address  headshot_url

//  org
//  office
//  locale -> district
//  subdist
//  name  (lowercase, space->dash, punctuation removed, same extension as parsed from url

foreach ($rows as $row) {
   if (Str::startsWith($row[0], "#"))  continue;

   $org      = $row[1];
   $office   = $row[4];
   $district = $row[2];
   $subdist  = $row[6];
   $name     = $row[7];

   $fields = ['s.org' => $org, 's.office' => $office, 's.district' => $district, 's.subdist' => $subdist, 'i.name' => $name];
   $sqlFields = new SqlFields($fields);

   $sql = "SELECT i.id "
        . "  FROM      v4incumbents AS i "
        . "  LEFT JOIN v4seats      AS s  ON (s.id = i.seat_id) "
        . " WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   if ($result->succeeded() && $result->getRowCount() == 1) {
      $id = intval($result->getRows()[0]['id']);
      $contactFields = ['web' => $row[16], 'email' => $row[17], 'phone' => $row[18], 'address' => $row[19], 'headshot' => $row[20]];
      foreach ($contactFields as $field => $value) {
         if (!empty ($value)  &&  $value !== "NOT_FOUND") {
            $sql = "UPDATE v4incumbents SET $field = '$value' WHERE id = $id AND $field = ''";
            $pdo->run($sql);
         }
      }
   }
}
