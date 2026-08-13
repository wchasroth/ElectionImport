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

$schools = $cc->getIdsFor("school");

foreach ($schools as $school) {
    if ($cc->hasCompleteCountiesFor('school', $school)) {

        //---For school districts that cross counties, combine (add up) the individual county election rows, into one row each.
        $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
             . " WHERE year='$year' "
             . "   AND org='schl-cou' AND district='$school' "
             . " ORDER BY org, office, district, subdist, incumbent, name";
        $moc->combine($sql);

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE org = 'schl-cou' AND district='$school' "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $cc->markRaceWinners($sql);

       //---Replace/report adding winners to v4candidates table.
       $sql = "SELECT DISTINCT org, office, district, subdist "
            . "    FROM v4elections WHERE year='$year' "
            . "     AND org IN ('schl-cou') AND district='$school' "
            . "   ORDER BY org, office, district, subdist";
       $cc->applyRaceWinnersToCandidates($sql, $year);
    }
}