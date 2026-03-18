#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;

require "vendor/autoload.php";

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$ic = new IncumbentCompressor($pdo);
$years = $ic->getElectionDates();

$schools = $ic->getUncompletedIdsFor("school");

foreach ($schools as $school) {
    if ($ic->hasCompleteCountiesFor($school)) {
        echo "$school uncompleted but ready\n";
        continue;
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