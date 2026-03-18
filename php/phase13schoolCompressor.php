#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\ElectionImport\MultiCountyOfficeCombiner;

require "vendor/autoload.php";

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$ic  = new IncumbentCompressor($pdo);
$moc = new MultiCountyOfficeCombiner($pdo);
$years = $ic->getElectionDates();

$schools = $ic->getUncompletedIdsFor("school");

foreach ($schools as $school) {
    if ($ic->hasCompleteCountiesFor('school', $school)) {
        echo "$school\n";

        //---For school districts that cross counties, combine (add up) the individual county election rows, into one row each.
        foreach ($years as $year) {
           $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
                . " WHERE year='$year' "
                . "   AND org='schl-cou' AND district=$school "
              . " ORDER BY org, office, district, subdist, incumbent, name";
           $moc->combine($sql);
        }

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE org = 'schl-cou' AND district=$school "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $ic->markRaceWinners($sql);

       //---Layer each year's race winners "over top of" the existing incumbents, replacing them
       //   with new incumbents as needed.
       foreach ($years as $year) {
          $sql = "SELECT DISTINCT org, office, district, subdist "
             . "    FROM v4elections WHERE year='$year' "
             . "     AND org IN ('schl-cou') AND district=$school "
             . "   ORDER BY org, office, district, subdist";
          $ic->applyRaceWinnersToIncumbents($sql, $year);
       }

       $ic->setCompleted("school", $school);
    }
}