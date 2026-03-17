#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

if ($argc < 2) {
  fwrite(STDERR, "Usage: phase12countyIncumbents.php county#\n");
  exit(1);
}

$county = $argv[1];
$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT complete FROM v4counties WHERE id = $county";
$result = $pdo->run($sql);
if ($result->failed()  ||  $result->getRowCount() == 0) {
    fwrite(STDERR, "Failed: $sql\n");
    exit(1);
}
$complete = intval($result->getRows()[0]['complete']);
if ($complete === 1)  exit(0);

$sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
     . "  FROM v4elections WHERE org in ('cnty', 'cnty-com') AND district=$county "
     . " ORDER BY year, org, office, district, subdist, incumbent";
$result = $pdo->run($sql);
$races  = $result->getRows();

show(0, null, 0);
foreach ($races as $race) {

   $fields = new SqlFields(['org' => $race['org'], 'office' => $race['office'], 'district' => $county, 'subdist' => $race['subdist'],
      'partial' => $race['partial'], 'year' => $race['year'], 'termlen' => $race['termlen'], 'incumbent' => $race['incumbent'],
      'cycle' => $race['cycle']]);
   $result = $pdo->runSF("SELECT * FROM v4elections WHERE ", "ORDER BY votes_C DESC", $fields);
   $rows = $result->getRows();
   fwrite(STDERR, "\n");
   foreach ($rows as $row)  show(0, $row, 0);

//   $maxVoteFor = 1;  // At least one, no matter what!
//   foreach ($rows as $row)  $maxVoteFor = max($maxVoteFor, intval($row['voteFor']));
//   $maxVoteFor = min ($maxVoteFor, count($rows));  // sometimes voteFor > number of candidates!
//
//   $winnerIds = [];
//   for ($i=0;   $i<$maxVoteFor;   $i++)  {
//      $winnerIds[] = $rows[$i]['id'];
//      show (0, $rows[$i], count($rows));
//   }
//   $sql = "UPDATE v4elections SET winner=1 WHERE id in (" . Str::join($winnerIds, ",") . ")";
//   $pdo->run($sql);
}

function show(int $block, $row, int $candidates): void {

   $format = "%4d %5d %-11s %-6s %-11s %-12s %-6s %-6s %-25s %2s %1s %1s %1s %8d %8d %3d\n";
   if ($row == null) {
      fwrite(STDERR, sprintf("       $format", 0, "id", "year", "county", "org", "office", "Dist", "Sub", "name", "V4", "P", "T", "I", "votesC", "votesT", "#can"));
      return;
   }
   fwrite(STDERR, sprintf("V4Err: $format",
      $block,
      intval($row['id']),
      $row['year'],
      $row['county'],
      $row['org'], $row['office'],
      $row['district'], $row['subdist'],
      $row['name'],
      $row['voteFor'],
      $row['partial'],
      $row['termlen'],
      $row['incumbent'],
      intval($row['votes_C']),
      intval($row['votes_T']),
      $candidates
   ));
}
