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

//---migrateVillageCouncil.php:  Migrate new village council seats/incumbents from 'importer' to 'dmeditor'.
//
//   We keep TWO copies of the v4 mivoter database going at any one time:
//      "importer"  (frequently, 'elections')   pdo1
//      "dmeditor"  (frequently, 'elections2')  pdo2
//
//   There were some problems with the original parsing of village council seats.  When we fixed,
//   we may end up with more village council seats.  We need to migrate only the NEW (not already in
//   'dmeditor') village council seats, from 'importer' to 'dmeditor.

$env  = new EnvFile("_env");
$pdo1 = PdoHelper::makePdo($env);  // importer
$pdo2 = new AlfredPDO($env->get('dbname2'), $env->get('dbuser'), $env->get('dbpw'));  // dmeditor

//---Get ALL the village council seats from importer
$sql = "SELECT s.id, s.org, s.district, s.termlen, s.termcycle, "
     . "       i.id AS iid, i.name, i.elected, i.party, i.votes_C, i.votes_D, i.votes_R, i.votes_O, i.votes_T, "
     . "       i.web, i.email, i.phone, i.address, i.num2elect, i.county, i.resigned, i.partial, "
     . "       i.headshot, i.status "
     . "  FROM v4seats           AS s "
     . "  LEFT JOIN v4incumbents AS i  ON (i.seat_id = s.id) "
     . " WHERE s.org='vil-cou' AND i.name != '' ";
echo "$sql\n";

$result1 = $pdo1->run($sql);
foreach ($result1->getRows() as $row) {
    $district = $row['district'];
    $name     = $row['name'];
    $newName  = new MatchableName($name);
    $sql = "SELECT s.id, i.id, i.name "
         . "  FROM v4seats           AS s "
         . "  LEFT JOIN v4incumbents AS i  ON (i.seat_id = s.id) "
         . " WHERE s.org='vil-cou' "
         . "   AND s.district='$district' ";
    $result2 = $pdo2->run($sql);
    $existingNames = [];
    foreach ($result2->getRows() as $existingRow)  $existingNames[] = new MatchableName($existingRow['name']);
    $best = $newName->findBestMatch($existingNames, 2);
    if ($best < 0) fwrite(STDERR, "Should add: $district $name\n");
    else {
        echo "Found dup: $district $name == " . $existingNames[$best]->getSimplifiedName() . "\n";
    }
}