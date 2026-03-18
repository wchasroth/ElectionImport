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
$years    = $ic->getElectionDates();

$org = " org in "
     . "    ('us', 'us-vp', 'us-sen', 'us-hou', 'mi', 'mi-lt', 'mi-ag', 'mi-sos', 'mi-boe', 'mi-msu', 'mi-um', 'mi-wsu', "
     . "     'crt-sup', 'crt-a', 'crt-c', 'crd-d', 'crt-p') ";

//---Select the winners of all of the state-level races
$sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
     . "  FROM v4elections WHERE $org "
     . "   ORDER BY year, org, office, district, subdist, incumbent";
$ic->markRaceWinners($sql);

//---Layer each year's race winners "over top of" the existing incumbents, replacing them
//   with new incumbents as needed.
foreach ($years as $year) {
   $sql = "SELECT DISTINCT org, office, district, subdist "
        . "  FROM v4elections WHERE year='$year'  AND $org "
        . " ORDER BY org, office, district, subdist";
   $ic->applyRaceWinnersToIncumbents($sql, $year);
}

$ic->setCompleted("state", 0);