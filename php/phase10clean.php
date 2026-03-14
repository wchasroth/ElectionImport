#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

if ($argc < 2) {
  fwrite(STDERR, "Usage: phase10clean.php yyyy-mm-dd\n");
  exit(1);
}

$year = $argv[1];
$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, cycle "
     . "  FROM v4elections WHERE year='$year' "
     . " ORDER BY org, office, district, subdist, incumbent, name";
$result = $pdo->run($sql);
$races  = $result->getRows();

show(0, null);
$block = 0;
foreach ($races as $race) {
// $raceText = Str::join([$race['org'], $race['office'], $race['district'], $race['subdist'], $race['partial']], "\t");

   $fields = new SqlFields(['org' => $race['org'], 'office' => $race['office'], 'district' => $race['district'], 'subdist' => $race['subdist'],
      'partial' => $race['partial'], 'year' => $year, 'termlen' => $race['termlen'], 'incumbent' => $race['incumbent'],
      'cycle' => $race['cycle']]);
   $result = $pdo->runSF("SELECT * FROM v4elections WHERE ", "", $fields);
   $rows = $result->getRows();
   $rowCount = count($rows);
   ++$block;

   // Case 1.  Rows for city-cou or cnty-com, with voteFor = 0 & subdist > 0.  Assume (and fix) voteFor = 1.
   // (This handles many cases where all of the voteFor's are 0, but may get messy when there's a mix.)
   for ($i=0;   $i < $rowCount;   $i++) {
      $row = $rows[$i];
      if (Str::contains($row['org'], "city-cou", "cnty-com")) {
         if (intval($row['voteFor']) == 0 && intval($row['subdist']) > 0) {
            $sql = "UPDATE v4elections SET voteFor=1 WHERE id=" . $row['id'];
            $pdo->run($sql);
            $rows[$i]['voteFor'] = "1";
         }
      }
   }

   // Case 2: only one row, with voteFor=0.  Assume voteFor=1 !
   if ($rowCount == 1  &&  intval($row['voteFor']) == 0) {
      $row = $rows[0];
      if (intval($row['voteFor']) == 0) {
         $sql = "UPDATE v4elections SET voteFor=1 WHERE id=" . $row['id'];
         $pdo->run($sql);
         $rows[0]['voteFor'] = "1";
         continue;
      }
   }

   // Case 2: None had a voteFor value.  Yuck.
   $maxVoteFor = getMaxVoteFor($rows);
   if ($maxVoteFor == 0) {
      foreach ($rows as $row)  show($block, $row);
      continue;
   }

   // Case 3: All had the max value.  Good!
   $goodIds = getIdsWithVoteForCount($rows, $maxVoteFor);
   if (count($goodIds) == $rowCount)  continue;

   // Case 4: some have maxVoteFor, rest have 0. Fix the ones with 0!
   $zeroes = getIdsWithVoteForCount($rows, 0);
   if (count($zeroes) + count($goodIds) == $rowCount)  {
      $sql = "UPDATE v4elections SET voteFor=$maxVoteFor WHERE id IN (" . Str::join($zeroes, ',') . ")";
      $pdo->run($sql);
      continue;
   }

   // Any remaining cases are inconsistent.
   foreach ($rows as $row)  show($block, $row);
}

function getIdsWithVoteForCount(array $rows, int $count): array {
   $ids = [];
   foreach ($rows as $row) {
      if (intval($row['voteFor']) == $count)  $ids[] = $row['id'];
   }
   return $ids;
}

function getMaxVoteFor(array $rows): int {
   $maxVote = -1;
   foreach ($rows as $row) {
      $value = intval($row['voteFor']);
      $maxVote = max($maxVote, $value);
   }
   return $maxVote;
}

function show(int $block, $row): void {

   $format = "%4d %5d %-11s %-6s %-11s %-12s %-6s %-6s %-25s %2s %1s %1s %1s\n";
   if ($row == null) {
      fwrite(STDERR, sprintf("       $format", 0, "id", "year", "county", "org", "office", "Dist", "Sub", "name", "V4", "P", "T", "I"));
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
      $row['incumbent']
   ));
}
