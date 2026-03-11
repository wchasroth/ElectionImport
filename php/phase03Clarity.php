#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

if ($argc < 3) {
   fwrite(STDERR, "Usage: php phase3Clarity.php yyyy-mm-dd county#\n");
   exit();
}
$year   = $argv[1];
$county = $argv[2];

// CSV column header line.  All lines that begin with "#" are comments.
echo "#yyyy-mm-dd\tcounty#\ttitle\tvoteFor#\tcandidateName\tpartyName\t#votes\n";

$rows = Csv::loadTrimmed(STDIN, ",");
for ($i=1;   $i < count($rows); $i++) {
   $row = $rows[$i];
   $title   = strtolower($row[1]);
   $voteFor = intval(Str::substringAfter($title,  "(vote for "));
   $title   = trim(  Str::substringBefore($title, "(vote for "));
   echo Str::join([$year, $county, $title, $voteFor, $row[2], $row[3], $row[4]], "\t") . "\n";
}

//"line number","contest name","choice name","party name","total votes","percent of votes","registered voters","ballots cast","num Precinct total","num Precinct rptg","over votes","under votes"
//1,"Straight Party Ticket (Vote For 1)","Democratic Party","DEM",246801,54.89,1035172,775379,506,506,"347","325422"
//
//     0  yyyy-mm-dd   from command line
//     1  county#      from command line
//1 => 2  title        text before "(Vote For..."
//1 => 3  voteFor#     text after  "(Vote For..."
//2 => 4  candidate
//3 => 5  party
//4 => 6  votes
//
//0  1                                    2             3     4
//21,"United States Senator (Vote For 1)","Gary Peters","DEM",418312,54.75,1035172,775379,506,506,"318","11059"
//~
