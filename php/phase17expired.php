#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

// Create a report of the apparent incumbents whose terms SHOULD be
// over, but have not (according to the election reports we have)
// been replaced.
//
// We "forgive" incumbents in single-seat positions, given the high
// chance that they were re-elected (and better list the wrong person
// than no person at all).
//
// The rest we delete.  They are usually the result of partial term
// elections that did not (sigh!) specify the end of the term, and
// where (in multi-seat offices, like city council) we don't know
// the actual max number of seats.
//
// Note that this needs to get manually updated as new election years
// come along!

$lastYear = 2025;
$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

echo "id\tii\torg\toffice\tdistrict\tsubdist\tseatnum\tname\telected\tcounty\tyear\ttermlen\ttermcycle\n";

$sql = "SELECT s.id, i.id AS ii, s.org, s.office, s.district, s.subdist, s.seatnum, s.seatmax, i.name, i.elected, i.county, e.year, s.termlen, s.termcycle "
     . "  FROM      v4seats         AS s "
     . "  LEFT JOIN v4incumbents    AS i  ON (s.id = i.seat_id) "
     . " WHERE s.termlen > 0 "
     . "   AND SUBSTRING(i.elected, 1, 4) + s.termlen <= $lastYear "
     . "   AND s.seatmax != 1 "
     . " ORDER BY s.org, s.office, s.district, s.subdist, s.seatnum ";
$result  = $pdo->run($sql);
$expires = $result->getRows();

foreach ($expires as $expire) {
   echo Str::join($expire, "\t") . "\n";
   $pdo->run("DELETE FROM v3incumbents WHERE id={$expire['ii']}");
   $pdo->run("DELETE FROM v3seats      WHERE id={$expire['id']}");
}
