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

// Some seats were deleted in v4corrections.  This can cause
// "holes" in the seat numbering.  (E.g. given 1,2,3 then delete 2.)
//
// Iterate thru each unique kind of seat.  If there are holes
// (i.e. if the highest seatnum > # of seat rows of that kind of seat),
// renumber the seats to go from 1 to N.

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT DISTINCT org, office, district, subdist FROM v4seats";
$result = $pdo->run($sql);
foreach ($result->getRows() as $row) {
   $fields = ['org' => $row['org'], 'office' => $row['office'], 'district' => $row['district'], 'subdist' => $row['subdist']];
   $seats = $pdo->runSF("SELECT * FROM v4seats WHERE", "ORDER BY seatnum", new SqlFields($fields), true);
   $rowCount = $seats->getRowCount();
   if ($rowCount < 1)    continue;

   $maxSeatnum = getMaxSeatnum($seats->getRows());
   if ($maxSeatnum == 0) continue;

   echo "Renumbering seats for: {$row['org']}, {$row['office']}, {$row['district']}, {$row['subdist']}\n";
   $seatnum = 0;
   foreach ($seats->getRows() as $row) {
      ++$seatnum;
      echo "  {$row['seatnum']} becomes $seatnum\n";
      $id = intval($row['id']);
      $sql = "UPDATE v4seats SET seatnum=$seatnum WHERE id=$id";
      $pdo->run($sql);
   }
   echo "\n";
}

function getMaxSeatnum(array $rows): int {
   $maxSeatnum = 0;
   foreach ($rows as $row) {
      $maxSeatnum = max ($maxSeatnum, intval($row['seatnum']));
   }
   return $maxSeatnum;
}
