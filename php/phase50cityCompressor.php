#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;

require "vendor/autoload.php";

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$cc  = new CandidateCompressor($pdo);
$moc = new MultiCountyOfficeCombiner($pdo);
$year = "2026-08-04";

$cities = $cc->getIdsFor("city");

foreach ($cities as $city) {
    if ($cc->hasCompleteCountiesFor('city', $city)) {

        //---For cities that cross counties, combine (add up) the individual county election rows, into one row each.
        $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
             . " WHERE year='$year' "
             . "   AND org IN ('city', 'city-cou') AND district='$city' "
           . " ORDER BY org, office, district, subdist, incumbent, name";
        $moc->combine($sql);

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE org IN ('city', 'city-cou') AND district='$city' "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $cc->markRaceWinners($sql);

       //---Layer each year's race winners "over top of" the existing incumbents, replacing them
       //   with new incumbents as needed.
       $sql = "SELECT DISTINCT org, office, district, subdist "
          . "    FROM v4elections WHERE year='$year' "
          . "     AND org IN ('city', 'city-cou') AND district='$city' "
          . "   ORDER BY org, office, district, subdist";
       $cc->applyRaceWinnersToCandidates($sql, $year);
    }
}