#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\SafeMap;

require "vendor/autoload.php";

// Parses Michigan state-level (and court) vote counts from phase 1 CSV file from November election.
// e.g. 00-aa-state_2018-11-06.state.ph1.csv
// See for example https://mvic.sos.state.mi.us/votehistory/Index?type=C&electionDate=11-5-2024,
// click on "Data" tab to get downloadable CSV (actually TSV).
//
// Outputs results as a phase 7 TSV:
// yyyy-mm-dd  county#   region  voteFor#   candidateName   partyLetter  votes_C  votes_D  votes_R  votes_O  votes_T  org  office  termlen  termcycle  partial  subdist  district
//
// Note that, for multi-position races (e.g. State BOE), votes_T is the total of ALL
// votes across all positions; whereas the rest of the votes_X numbers is for that
// particular candidate.

// Input spreadsheet column # layout
define("COUNTY_CODE", 4);
define("OFFICE",      6);
define("PARTY",       8);
define("LASTNAME",   10);
define("FIRSTNAME",  11);
define("MIDNAME",    12);
define("VOTES",      14);

// officeMap (map for a particular office name)
//    'positions'       int
//    'votes_D'         int
//    'votes_R'         int
//    'votes_O'         int
//    'votes_T'         int
//    'votes_U'         int   (but largely ignored)i
//    'people'          map
//       $name          (key)
//          'votes_C'   int
//          'party'     str

//  Some day may want to consider parsing the office name to get term length; not needed at the moment (7/22/2025).

$csv = Csv::loadTrimmed(STDIN, "\t");

$map = new SafeMap();
foreach ($csv as $row) {
   if ($row[0][0] != '2')  continue;   // skip header/footer

   $electionDate = $row[0];

   $fullOfficeName = strtolower($row[OFFICE]);
   if (Str::contains($fullOfficeName, " proposal "))  continue;

   $partial = Str::contains($fullOfficeName, " partial ") ? 1 : 0;
   $incumbent = "";
   if (Str::contains($fullOfficeName, " incumbent ")    )  $incumbent = "I";
   if (Str::contains($fullOfficeName, " non-incumbent "))  $incumbent = "N";

   $officeName  = preg_replace('/ [0-9] YEAR TERM.*$/', '', $row[OFFICE]);

   $personName  = preg_replace('/\s+/', ' ', "{$row[FIRSTNAME]} {$row[MIDNAME]} {$row[LASTNAME]}");
   $personName  = Str::replaceAll($personName, ',', '');
   $partyName   = $row[PARTY];
   $voteCount   = intval($row[VOTES]);

   $officeMap = $map->getMap($officeName);
   $officeMap->putInt('partial', $partial);
   $officeMap->putStr('incumbent', $incumbent);
   $officeMap->putInt('positions',  extractPositions($row[OFFICE]));
   $voteKey = calculateVoteKeyFromPartyName($partyName, $officeName);
   $officeMap->addInt('votes_T', $voteCount);
   $officeMap->addInt($voteKey,  $voteCount);
   $termlen = ImportHelper::calculateTermLength($row[OFFICE]);
   $officeMap->putInt('termlen', $termlen);
   $termcycle = ImportHelper::calculateTermCycle($row[OFFICE], intval($electionDate));
   $officeMap->putInt('termcycle', $termcycle);
   $officeMap->putStr('county', $row[COUNTY_CODE]);

   $peopleMap = $officeMap->getMap('people');
   $personMap = $peopleMap->getMap($personName);
   $personMap->putStr('party', $partyName);
   $personMap->addInt('votes_C',  $voteCount);
}

//printf("%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n", "year", "org", "office", "district", "subdist", "termlen", "termcycle", "name", "party",
//   "num2elect", "votes_C", "votes_D", "votes_R", "votes_O", "votes_T");
echo	"#yyyy-mm-dd	county#	region	voteFor#	candidateName	partyLetter	votes_C	votes_D	votes_R	votes_O 	votes_T org	office  	termlen 	termcycle	partial  subdist	district\n";
foreach ($map->getKeys() as $officeName) {
   $officeMap = $map->getMap($officeName);
   $positions = $officeMap->getInt('positions');
   $so        = StateOffice::make($officeName, $officeMap->getStr('county'));

   $peopleMap = $officeMap->getMap('people');
// $winners   = getWinners($peopleMap, $positions);
   $winners   = getWinners($peopleMap, 20);  // Hack to include all candidates
   foreach ($winners as $personName => $votes) {
      $personMap = $peopleMap->getMap($personName);
      $partyChar = PartyFinder::getPartyCode($personMap->getStr('party'));

      $region  = "";  // Probably irrelevant
      $office  = "";  // Probably irrelevant
      $subdist =  0;  // Irrelevant
      printf("%s\t0\t%s\t%d\t%s\t%s\t%d\t%d\t%d\t%d\t%d\t%s\t%s\t%d\t%d\t%d\t%d\t%s\t%s\n",
         $electionDate, $region, $positions, $personName, $partyChar,
         $personMap->getInt("votes_C"), $officeMap->getInt("votes_D"), $officeMap->getInt("votes_R"),
         $officeMap->getInt("votes_O"), $officeMap->getInt("votes_T"),
         $so->org, $office, $officeMap->getInt('termlen'), $officeMap->getInt('termcycle'),
         $officeMap->getInt('partial'), $subdist, $so->district, $officeMap->getStr('incumbent')
      );
//      printf("%s\t%s\t\t%s\t%s\t%d\t%s\t%s\t%s\t%d\t%d\t%d\t%d\t%d\t%d\n",
//         $electionDate, $so->org, $so->district, '',
//         $officeMap->getInt('termlen'), $officeMap->getInt('termcycle'),
//         $personName, $partyChar,
//         $positions,
//         $personMap->getInt("votes_C"),
//         $officeMap->getInt("votes_D"), $officeMap->getInt("votes_R"), $officeMap->getInt("votes_O"), $officeMap->getInt("votes_T"));
   }
}

function getWinners(SafeMap $peopleMap, int $positions): array {
   $votesByPerson = [];
   foreach ($peopleMap->getKeys() as $personName) {
      $person = $peopleMap->getStr($personName);
      $votesByPerson[$personName] = $peopleMap->getMap($personName)->getInt('votes_C');
   }
   uasort($votesByPerson, "CharlesRothDotNet\ElectionImport\compareInts");
   return array_slice($votesByPerson, 0, $positions);
}

function compareInts(int $a, int $b): int {
   if ($a == $b)  return 0;
   return ($a < $b) ? 1 : -1;
}

function calculateVoteKeyFromPartyName(string $partyName, string $officeName): string {
   if (Str::contains($officeName, "COURT"))  return "votes_U";
   switch (strtoupper($partyName)) {
      case "DEMOCRATIC":           return "votes_D";
      case "REPUBLICAN":           return "votes_R";
      case "NO PARTY AFFILIATION":
      case "NON PARTISAN":         return "votes_U";
      default:                     return "votes_O";
   }
}

function extractPositions(string $text): int {
   return intval(Str::substringAfter($text, '('));
}
