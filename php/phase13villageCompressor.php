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

$villages = $ic->getUncompletedIdsFor("village");

foreach ($villages as $village) {
    if ($ic->hasCompleteCountiesFor('village', $village)) {

        // Qualifier to match offices for this village.
        $orgAndDistrict = " org IN ('vil', 'vil-cou') AND district=$village ";

        //---For villages that cross counties, combine (add up) the individual county election rows, into one row each.
        foreach ($years as $year) {
           $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
                . " WHERE year='$year' AND $orgAndDistrict "
              . " ORDER BY org, office, district, subdist, incumbent, name";
           $moc->combine($sql);
        }

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE $orgAndDistrict "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $ic->markRaceWinners($sql);

       //---Layer each year's race winners "over top of" the existing incumbents, replacing them
       //   with new incumbents as needed.
       foreach ($years as $year) {
          $sql = "SELECT DISTINCT org, office, district, subdist "
             . "    FROM v4elections WHERE year='$year' AND $orgAndDistrict "
             . "   ORDER BY org, office, district, subdist";
          $ic->applyRaceWinnersToIncumbents($sql, $year);
       }

       $ic->setCompleted("village", $village);
    }
}