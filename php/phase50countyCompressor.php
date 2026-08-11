#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;

require "vendor/autoload.php";

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$cc = new CandidateCompressor($pdo);
$year = "2026";
$counties = $cc->getUncompletedIdsFor("county");

foreach ($counties as $county) {
    if ($cc->isCountyImported($county)) {
       //---Select the winners of all of the county races.
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE org in ('cnty', 'cnty-com') AND county=$county "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $cc->markRaceWinners($sql);

       //---Insert the new race winners into v4candidates, or update the existing termlen
       //   if they match rows already in v4candidates.
       $sql = "SELECT DISTINCT org, office, district, subdist "
          . "    FROM v4elections "
          . "   WHERE org IN ('cnty', 'cnty-com') AND county=$county "
          . "   ORDER BY org, office, district, subdist";
       $cc->applyRaceWinnersToCandidates($sql, $year);

//     $cc->setCompleted("county", $county);
    }
}