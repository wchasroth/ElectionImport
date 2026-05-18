#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---fixSubDistricts.php    Migrate missing subdist numbers from pdo1 to pdo2.
//
//   There was a bug in migrateSeats.php that did NOT copy over the subdist numbers to new v4seats rows.
//   So this script goes through all of the rows in pdo1's v4seats (that had subdist > 0), and applies the
//   subdist # to the matching rows in pdo2's v4seats.
//   SIGH.

$env  = new EnvFile("_env");
$pdo1 = PdoHelper::makePdo($env);  // importer
$pdo2 = new AlfredPDO($env->get('dbname2'), $env->get('dbuser'), $env->get('dbpw'));

$sql = "SELECT s.org, s.office, s.district, s.subdist, i.name "
     . "  FROM v4seats           AS s "
     . "  LEFT JOIN v4incumbents AS i  ON (i.seat_id = s.id) "
     . " WHERE s.org in ('city-cou', 'town-cou', 'vil-cou') AND s.subdist > 0"
   ;
$original = $pdo1->run($sql);
if ($original->failed())  fwrite(STDERR, $original->getError() . "\n");
foreach ($original->getRows() as $row) {
   $name = addslashes($row['name']);
   $sql = "SELECT s.id "
        . "  FROM      v4seats      AS s"
        . "  LEFT JOIN v4incumbents AS i  ON (i.seat_id = s.id) "
        . " WHERE s.org     ='{$row['org']}' "
        . "   AND s.office  ='{$row['office']}' "
        . "   AND s.district='{$row['district']}' "
        . "   AND i.name    = '$name' "
        . "   AND s.subdist = 0";
   $result = $pdo2->run($sql);
   if ($result->getRowCount() != 1)  continue;
   $sid = $result->getRows()[0]['id'];
   $sql = "UPDATE v4seats SET subdist={$row['subdist']} WHERE id=$sid";
   echo "$sql    Match: {$row['org']} {$row['office']} {$row['district']} $name new subdist={$row['subdist']}\n";
// $result = $pdo2->run($sql);
// if ($result->failed()) fwrite(STDERR, $result->getError() . "\n");
}
