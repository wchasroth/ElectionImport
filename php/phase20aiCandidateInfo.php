#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

// phase20aiCandiateInfo
//
// Import contact info, found by AI agents, from a TSV spreadsheet.
// Add to v4candidates fields where those fields are EMPTY.
// (I.e. previously-existing values are assumed to take precedence.)
//
// I had problems counting up the number of rows actually affected, so
// in the interests of time, I just write the SQL itself out to a file,
// and then run it manually.  Yeah, it sucks, Harry.

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 2) {
   fwrite(STDERR, "Usage: phase20aiCandidateInfo.php inputFile\n");
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
$rowsUpdated = 0;
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);

   $org      = $row['org'];
   $office   = $row['office'];
   $district = $row['district'];
   $subdist  = $row['subdist'];
   $name     = $row['name'];

   $fields = ['s.org' => $org, 's.office' => $office, 's.district' => $district, 's.subdist' => $subdist, 'i.name' => $name];
   $sqlFields = new SqlFields($fields);
   $rowsUpdated += updateFields($pdo, $sqlFields,
      ['web' => $row['web'], 'email' => $row['email'], 'phone' => $row['phone'], 'headshot' => $row['headshot']]);
   updateFields($pdo, $sqlFields, ['tempdesc' => $row['description']]);
}

$sql =  "UPDATE v4candidates SET description = tempdesc "
     .  " WHERE reviewed=0  AND  (description='' OR (LENGTH(tempdesc) > LENGTH(description) + 100)) ";
echo "$sql;\n";
#$result = $pdo->run($sql);
#$rowsUpdated = $result->getRowCount();

$sql = "UPDATE v4candidates SET tempdesc=''";
echo "$sql;\n";
#$pdo->run($sql);

#echo "Rows updated: $rowsUpdated\n";


function updateFields(AlfredPDO $pdo, SqlFields $sqlFields, array $contactFields): int {
   $updatedFieldCount = 0;
   $sql = "SELECT i.id "
      . "  FROM      v4candidates AS i "
      . "  LEFT JOIN v4seats      AS s  ON (s.id = i.seat_id) "
      . " WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   if ($result->succeeded() && $result->getRowCount() == 1) {
      $id = intval($result->getRows()[0]['id']);
      foreach ($contactFields as $field => $value) {
         if (!empty ($value)  &&  $value !== "NOT_FOUND") {
            $value = Str::replaceAll($value, "'", ";");
            $value = Str::replaceAll($value, ";", "''");
            $sql = "UPDATE v4candidates SET $field = '$value' WHERE id = $id AND $field = ''";
            echo "$sql;\n";
#           $result = $pdo->run($sql);
#           if ($result->failed())  echo "phase20aiCandidateInfo failed: $sql " . $result->getError() . "\n";
#           else $updatedFieldCount += $result->getRowCount();
         }
      }
   }
   return $updatedFieldCount;
}


//echo "Total # of rows updated: $rowsUpdated\n";
