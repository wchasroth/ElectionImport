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

// Copy incumbent information from 'old' incumbent26/seat26, to v4incumbents.

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

// Iterate over each 'old' incumbent.  (Yes, it's inefficient -- who cares?)
$sql = "SELECT s.org, s.office, s.district, s.subdist, i.name, i.web, i.email, i.phone, i.address "
     . "  FROM      seat26      AS s "
     . "  LEFT JOIN incumbent26 AS i ON (i.seat_id = s.id) "
     . "  WHERE (i.web!='' OR i.email!='' OR i.phone!='' OR i.address!='')" ;
$oldIncumbents = $pdo->run($sql);
if ($oldIncumbents->failed()) echo $oldIncumbents->getError() . "\n";
$counter = 0;
foreach ($oldIncumbents->getRows() as $oldIncumbent) {
   $oldName = new MatchableName($oldIncumbent['name']);
   $web     = $oldIncumbent['web'];
   $email   = $oldIncumbent['email'];
   $phone   = $oldIncumbent['phone'];
   $address = $oldIncumbent['address'];

   // Now find the new incumbents in the same office.
   $fields = ['org' => $oldIncumbent['org'], 'office' => $oldIncumbent['office'], 'district' => $oldIncumbent['district'], 'subdist' => $oldIncumbent['subdist']];
   $sql = "SELECT s.org, s.office, s.district, s.subdist, i.name, i.web, i.email, i.phone, i.address, i.id "
      . "  FROM      v4seats      AS s "
      . "  LEFT JOIN v4incumbents AS i ON (i.seat_id = s.id) ";
   $result = $pdo->runSF("$sql WHERE", "", new SqlFields($fields), true);
   $newIncumbents = $result->getRows();

   // Find (at most!) one that matches by name.
   $bestIndex = getBestMatchingRowIndex($oldName, $newIncumbents);
   if ($bestIndex >= 0) {
      $id        = $newIncumbents[$bestIndex]['id'];
      $contacts  = [];
      if (! empty($web))     $contacts['web']     = $web;
      if (! empty($email))   $contacts['email']   = $email;
      if (! empty($phone))   $contacts['phone']   = $phone;
      if (! empty($address)) $contacts['address'] = $address;
      $contactsFields = new SqlFields($contacts);

      // Fill in the contact info from the old incumbent.
      $sql = "UPDATE v4incumbents SET " . $contactsFields->getSetFragment() . " WHERE id=$id";
      ++$counter;
      echo $counter . " " . $oldIncumbent['name'] . " == " . $newIncumbents[$bestIndex]['name'] . "  $sql\n";
      $update = $pdo->run($sql);
      if ($update->failed())  echo "ERROR: " . $update->getError() . "\n";
   }

}

function getBestMatchingRowIndex(MatchableName $old, array $rows): int {
   $rowCount = count($rows);
   if ($rowCount == 0)  return -1;
   $newNames = [];
   for ($i=0;   $i<$rowCount;   $i++) {
      if (isset($rows[$i]['name']))  $newNames[] = new MatchableName($rows[$i]['name']);
   }

   return $old->findBestMatch($newNames, 2);
}
