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

$cc  = new CandidateCompressor($pdo);
$moc = new MultiCountyOfficeCombiner($pdo);
$year = "2026-08-04";

$villages = $cc->getIdsFor("village");

foreach ($villages as $village) {
    if ($cc->hasCompleteCountiesFor('village', $village)) {

        // Qualifier to match offices for this village.
        $orgAndDistrict = " org IN ('vil', 'vil-cou') AND district='$village' ";

        //---For villages that cross counties, combine (add up) the individual county election rows, into one row each.
        $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
             . " WHERE year='$year' AND $orgAndDistrict "
             . " ORDER BY org, office, district, subdist, incumbent, name";
        $moc->combine($sql);

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE $orgAndDistrict "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $cc->markRaceWinners($sql);

       //---Replace/report on adding the winners to the v4candidates table.
       $sql = "SELECT DISTINCT org, office, district, subdist "
          . "    FROM v4elections WHERE year='$year' AND $orgAndDistrict "
          . "   ORDER BY org, office, district, subdist";
       $cc->applyRaceWinnersToCandidates($sql, $year);
    }
}