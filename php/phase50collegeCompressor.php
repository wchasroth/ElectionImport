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

//---Unlike the other kinds of orgs, s4commcolleges does NOT denormalize/duplicate rows in order to include the county!
//   Instead, we have a link table,  v4commcolleges_county that links each college to one or more counties.
//   But we have to BUILD that table from the v4elections data, which is a nuisance.  Most of the time, we'll end
//   up with a ton of duplicate INSERTS -- that are thrown away by the primary key rule.  That's fine.
//   It also means we're not 100% sure that the info is complete UNTIL we have imported ALL counties.  (Yuck.)
$sql = "SELECT DISTINCT district, county FROM v4elections WHERE org='comcol-cou'";
$result = $pdo->run($sql);
foreach ($result->getRows() as $row) {
    $sql = "INSERT INTO v4commcolleges_county (id, county_id) VALUES ({$row['district']}, {$row['county']})";
    $pdo->run($sql);
}

$colleges = $cc->getIdsFor("college");

foreach ($colleges as $college) {
    if ($cc->hasCompleteCountiesFor('college', $college)) {

        // Qualifier to match offices for this college.
        $orgAndDistrict = " org IN ('comcol-cou') AND district='$college' ";

        //---For colleges that cross counties, combine (add up) the individual county election rows, into one row each.
        $sql = "SELECT DISTINCT org, office, district, subdist, partial, termlen, incumbent, year FROM v4elections "
             . " WHERE year='$year' AND $orgAndDistrict "
             . " ORDER BY org, office, district, subdist, incumbent, name";
        $moc->combine($sql);

       //---Select the winners
       $sql = "SELECT DISTINCT org, office, subdist, district, partial, termlen, incumbent, cycle, year "
          . "    FROM v4elections WHERE $orgAndDistrict "
          . "   ORDER BY year, org, office, district, subdist, incumbent";
       $cc->markRaceWinners($sql);

       //---Replace/report on adding winners to v4candidates table.
       $sql = "SELECT DISTINCT org, office, district, subdist "
            . "    FROM v4elections WHERE year='$year' AND $orgAndDistrict "
            . "   ORDER BY org, office, district, subdist";
       $cc->applyRaceWinnersToCandidates($sql, $year);
    }
}