#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;

require "vendor/autoload.php";

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$years = ['2018-11-06', '2020-11-03', '2021-11-02', '2022-11-08', '2023-11-07', '2024-11-05', '2025-11-04'];

$ic = new IncumbentCompressor($pdo);

for ($county=81;  $county<=81;  ++$county) {
    if ($ic->isCountyImported($county)  &&  ! $ic->isCompleted("county", $county)) {
       //---Select the winners of all of the county races.
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE org in ('cnty', 'cnty-com', 'town', 'town-cou') AND county=$county "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $ic->markRaceWinners($sql);

       //---Layer each year's race winners "over top of" the existing incumbents, replacing them
       //   with new incumbents as needed.
       foreach ($years as $year) {
          $sql = "SELECT DISTINCT org, office, district, subdist "
             . "    FROM v4elections WHERE year='$year' "
             . "     AND org IN ('cnty', 'cnty-com', 'town', 'town-cou') AND county=$county "
             . "   ORDER BY org, office, district, subdist";
          $ic->applyRaceWinnersToIncumbents($sql, $year);
       }

       $ic->setCompleted("county", $county);
    }
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
