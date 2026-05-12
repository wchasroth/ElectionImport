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

//---migrateSeats.php:  Migrate new data from county election report db, to mivoter editor db.
//
//   We keep TWO copies of the v4 mivoter database going at any one time:
//      "dmeditor"  (frequently, 'elections2')
//      "importer"  (frequently, 'elections')
//
//   As we finish the parsing of the county election reports, we update the v4seats and v4incumbents
//   tables in 'importer'.  Meanwhile, human editors are making changes to v4seats and v4incumbents
//   tables in 'dmeditor'.  So we have to be very careful about merging the two.
//
//   Here's the approach.  As new data is added to 'importer', we add records to v4completed to
//   indicate which org/offices are now "complete" (meaning we finally got all the election reports
//   parsed for a particular county).  By comparing the v4completed tables between 'importer' and
//   'dmeditor', we can tell which v4seats/v4incumbents rows in 'importer' are new (do not exist) in
//   'dmeditor'.  Those are the rows that we need to copy over from 'importer' to 'dmeditor'.
//
//   That's what this script does.

$env  = new EnvFile("_env");
$pdo1 = PdoHelper::makePdo($env);  // importer
$pdo2 = new AlfredPDO($env->get('dbname2'), $env->get('dbuser'), $env->get('dbpw'));

$sql = "SELECT type, id FROM v4completed ORDER BY type, id";
$result1 = $pdo1->run($sql);
$result2 = $pdo2->run($sql);

$typeIds1 = makeCompletedTypeIds($result1->getRows());
$typeIds2 = makeCompletedTypeIds($result2->getRows());
$diffs = array_diff($typeIds1, $typeIds2);
foreach ($diffs as $typeId)  {
    if (Str::startsWith($typeId, "county"))  echo "New $typeId\n";

    //---Mark this typeId as completed.
    $newType = Str::substringBefore($typeId, ':');
    $newId   = Str::substringAfter ($typeId, ':');
    $sql = "INSERT INTO v4completed (type, id) VALUES ('$newType', $newId)";
    $pdo2->run($sql);

    //---Find all the seats for this typeId.
    $sql = "SELECT * FROM v4seats WHERE " . makeQualifier($typeId);
    $result = $pdo2->run($sql);
    if ($result->getRowCount() > 0) {
        echo "Error: got " . $result->getRowCount() . " for $sql\n";
        continue;
    }

    //---Accumulate matching sets of seats and incumbents.
    $result = $pdo1->run($sql);
    $seats = [];
    $incumbents = [];
    foreach ($result->getRows() as $row) $seats[$row['id']] = $row;

    foreach (array_keys($seats) as $seatId) {
        $sql = "SELECT * FROM v4incumbents WHERE seat_id=$seatId";
        $result = $pdo1->run($sql);
        if ($result->getRowCount() > 0)  $incumbents[$seatId] = $result->getRows()[0];
    }

    //---Insert new v4seats entry into pdo2, and remember the NEW v4seats id to be used for the new v4incumbents.seat_id.
    foreach ($seats as $id => $row) {
        $sqlFields = new SqlFields(["org" => $row['org'], "office" => $row['office'], 'district' => $row['district'],
           'subdist' => $row['subdist'],
           'seatnum' => $row['seatnum'], 'seatmax' => $row['seatmax'], 'termlen' => $row['termlen'], 'termcycle' => $row['termcycle']]);
        $sql = "INSERT INTO v4seats " .  $sqlFields->getInsertFragment();
        $result    = $pdo2->run($sql);
        if ($result->failed()) echo $result->getError() . "\n";
        $newSeatid = $result->getInsertId();
        $incumbents[$id]['seat_id'] = $newSeatid;
    }

    //---Insert new v4incumbents entry into pdo2, pointing at the matching NEW v4seats id.
    foreach ($incumbents as $id => $row) {
        $sqlFields = new SqlFields(['seat_id' => $row['seat_id'], 'name' => $row['name'], 'role' => $row['role'], 'elected' => $row['elected'], 'party' => $row['party'],
           'votes_C' => $row['votes_C'], 'votes_D' => $row['votes_D'], 'votes_R' => $row['votes_R'], 'votes_O' => $row['votes_O'], 'votes_T' => $row['votes_T'],
           'web' => $row['web'], 'email' => $row['email'], 'phone' => $row['phone'], 'address' => $row['address'], 'num2elect' => $row['num2elect'],
           'county' => $row['county'], 'resigned' => $row['resigned'], 'partial' => $row['partial'], 'headshot' => $row['headshot'], 'status' => $row['status']]);
        $sql = "INSERT INTO v4incumbents " .  $sqlFields->getInsertFragment();
        $result    = $pdo2->run($sql);
        if ($result->failed()) echo $result->getError() . "\n";
    }
}

function makeQualifier (string $typeId): string {
    $type2Org = ["county" => "org LIKE 'cnty%'", "city" => "org LIKE 'city%'", "village" => "org LIKE 'vil%'",
       "school" => "org = 'schl-cou'", "college" => "org = 'comcol-cou'", 'township' => "org LIKE 'town%'"];
    $type = Str::substringBefore($typeId, ':');
    $id   = Str::substringAfter ($typeId, ':');

    $org = $type2Org[$type];
    return " $org AND district='$id' ";
}
function makeCompletedTypeIds (array $rows): array {
    $typeIds = [];
    foreach ($rows as $row) $typeIds[] = $row['type'] . ":" . $row['id'];
    return $typeIds;
}