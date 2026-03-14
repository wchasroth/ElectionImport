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
  fwrite(STDERR, "Usage: phase11dedup.php yyyy-mm-dd\n");
  exit(1);
}

$year = $argv[1];
$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent "
     . "  FROM v4elections WHERE year='$year' "
     . " ORDER BY org, office, district, subdist, incumbent, name";
$result = $pdo->run($sql);
$races  = $result->getRows();

show(0, null);
foreach ($races as $race) {

   $fields = new SqlFields(['org' => $race['org'], 'office' => $race['office'], 'district' => $race['district'], 'subdist' => $race['subdist'],
      'partial' => $race['partial'], 'year' => $year, 'termlen' => $race['termlen'], 'incumbent' => $race['incumbent'],
//    'cycle' => $race['cycle']]);
   ]);
   $result = $pdo->runSF("SELECT * FROM v4elections WHERE ", "ORDER BY name", $fields);
   $rows = $result->getRows();

   $combiner = new CandidateRowCombiner();
   foreach ($rows as $row)  $combiner->addRow($row);

   if (count($combiner->getOtherCounties(-1)) == 1)  continue;  // Skip if all entries are in the same county.

   $combinedRows = $combiner->getResolvedRows();
   $sql = "DELETE FROM v4elections WHERE id IN (" . Str::join($combiner->getIds(), ",") . ")";
   $pdo->run($sql);
   foreach ($combinedRows as $row) {
      unset($row['id']);
      $fields = new SqlFields($row);
      $result = $pdo->runSF("INSERT INTO v4elections", "", $fields, true);
      if ($result->failed()) fwrite (STDERR, $result->getError() . "\n");
   }
}

function show(int $block, $row): void {

   $format = "%4d %5d %-11s %-6s %-11s %-12s %-6s %-6s %-25s %2s %1s %1s %1s %8d\n";
   if ($row == null) {
      fwrite(STDERR, sprintf("       $format", 0, "id", "year", "county", "org", "office", "Dist", "Sub", "name", "V4", "P", "T", "I", "votesC"));
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
      intval($row['votes_C'])
   ));
}
