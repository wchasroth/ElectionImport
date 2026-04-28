#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---migrateMultiSeatCases.php:  Migrate additional multi-seat offices that were missed due to a bug.
//
//   Early in the county-election import process, a bug was introduced, that caused some multi-seat
//   offices (specifically village council, town-park, and libry-cou) to only include the first seat.
//   (Specifically, incorrect values of 'seats' in the s4titles table for those offices.)
//
//   Now, we keep TWO copies of the v4 mivoter database going at any one time:
//      "importer"  (frequently, 'elections')   pdo1   Data built from the election county reports
//      "dmeditor"  (frequently, 'elections2')  pdo2   Data edited manually by our volunteers
//
//   So this is a 'fix up' script, that finds the multi-seat offices (that were missing in dmeditor),
//   and migrates them from importer to dmeditor.  It does some clever name-matching of the existing
//   seats in dmeditor, to make sure that we don't introduce duplicates.
//
//   Because this is a one-time fix-up script, we manually modify the queries below to cover
//   the three cases of org='vil-cou', org='libry-cou', and office='town-park'.  Yes, it's
//   a hack.  Yes, if we need to repeat it, I will generalize it.

$env  = new EnvFile("_env");
$pdo1 = PdoHelper::makePdo($env);  // importer
$pdo2 = new AlfredPDO($env->get('dbname2'), $env->get('dbuser'), $env->get('dbpw'));  // dmeditor
$migrator = new Migrator();

//---Get ALL the missing seats from importer
$clause = " s.org = 'vil-cou' ";
//$clause = " s.org = 'libry-cou' ";
//$clause = " s.office = 'town-park' ";

$sql = "SELECT s.id, s.org, s.district, s.termlen, s.termcycle, "
     . "       i.id AS iid, i.name, i.elected, i.party, i.votes_C, i.votes_D, i.votes_R, i.votes_O, i.votes_T, "
     . "       i.web, i.email, i.phone, i.address, i.num2elect, i.county, i.resigned, i.partial, "
     . "       i.headshot, i.status "
     . "  FROM v4seats           AS s "
     . "  LEFT JOIN v4incumbents AS i  ON (i.seat_id = s.id) "
     . " WHERE $clause AND i.name != '' ";
echo "$sql\n";

$result1 = $pdo1->run($sql);
foreach ($result1->getRows() as $row) {
    $district = $row['district'];
    $seatId   = intval($row['id']);
    $incId    = intval($row['iid']);
    $name     = $row['name'];
    $newName  = new MatchableName($name);

    $sql = "SELECT i.id AS iid, i.name "
         . "  FROM v4seats           AS s "
         . "  LEFT JOIN v4incumbents AS i  ON (i.seat_id = s.id) "
         . " WHERE $clause "
         . "   AND s.district='$district' ";
    $result2 = $pdo2->run($sql);
    $existingNames = [];
    foreach ($result2->getRows() as $existingRow) $existingNames[] = new MatchableName($existingRow['name']);

    $best = $newName->findBestMatch($existingNames, 2);
    if ($best >= 0) fwrite(STDERR, "Found dup: $district $name == " . $existingNames[$best]->getSimplifiedName() . "\n");
    else {
       echo "Adding: $district $name\n";
       $seatRow = $migrator->getSeat     ($pdo1, $seatId);
       $incRow  = $migrator->getIncumbent($pdo1, $incId);
       $migrator->insertSeatAndIncumbent($pdo2, $seatRow, $incRow);
    }
}