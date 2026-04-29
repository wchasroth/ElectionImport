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

$townships = $ic->getUncompletedIdsFor("township");

foreach ($townships as $township) {
    if ($ic->hasCompleteCountiesFor('township', $township)) {
        if ($township != 3540)  continue;

        //---For cities that cross counties, combine (add up) the individual county election rows, into one row each.
        foreach ($years as $year) {
           $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
                . " WHERE year='$year' "
                . "   AND org IN ('town', 'town-cou') AND district='$township' "
              . " ORDER BY org, office, district, subdist, incumbent, name";
           echo "$sql\n";
           $moc->combine($sql);
        }

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE org IN ('town', 'town-cou') AND district='$township' "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $ic->markRaceWinners($sql);

       //---Layer each year's race winners "over top of" the existing incumbents, replacing them
       //   with new incumbents as needed.
       foreach ($years as $year) {
          $sql = "SELECT DISTINCT org, office, district, subdist "
             . "    FROM v4elections WHERE year='$year' "
             . "     AND org IN ('town', 'town-cou') AND district='$township' "
             . "   ORDER BY org, office, district, subdist";
          $ic->applyRaceWinnersToIncumbents($sql, $year);
       }

       $ic->setCompleted("township", $township);
    }
}