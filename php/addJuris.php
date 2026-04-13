#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---addJuris.

$csv = new CsvFile();
$argv = $csv->extractFlags($argv);

if (count($argv) < 5) {
   fwrite(STDERR, "Usage: addJuris.php inputFile precinct_full_name contest_name choice_name\n");
   exit(1);
}

if (! $csv->loadfile($argv[1]))  $csv->exitWithError();
$precinct = $argv[2];
$contest  = $argv[3];
$choice   = $argv[4];

echo $csv->makeOutputRow($csv->getKeys()) . "\n";

$ambiguousCases = [];
$rowCount = $csv->getRowCount();
for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   if (isIgnorableCandidate($row[$choice])) continue;

   $contestName = strtolower($row[$contest]);
   if (isAmbiguousOffice($contestName)) {
      $key = $contestName . "_" . $row[$choice];
      if (! isset($ambiguousCases[$key]))  $ambiguousCases[$key] = [];
      $juris = Str::substringBefore($row[$precinct], ",");
      $juris = removePrecinct($juris);
      if (! array_key_exists($juris, $ambiguousCases[$key]))  $ambiguousCases[$key][$juris] = 1;
   }
}

for ($i=1;   $i < $rowCount;   $i++) {
   $row = $csv->getRow($i);
   if (isIgnorableCandidate($row[$choice])) continue;

   $contestName = strtolower($row[$contest]);
   if (mayNeedDisambiguating($contestName)  &&  isAmbiguousOffice($contestName)) {
      $key = $contestName . "_" . $row[$choice];
      $precincts = $ambiguousCases[$key];
//    fwrite(STDERR, "ambiguous: $key " . count($precincts) . "\n");
      if (count($precincts) == 1) {
         $row[$contest] = array_keys($precincts)[0] . " " . $row[$contest];
      }
   }

   echo $csv->makeOutputRow(array_values($row)) . "\n";
}

function isIgnorableCandidate(string $choiceName): bool {
   $choiceName = strtolower($choiceName);
   return $choiceName == 'yes'  ||  $choiceName == 'no'  ||  Str::startsWith ($choiceName, "rejected ", "unresolved ");
}

function isAmbiguousOffice (string $office): bool {
   $minimalOffice = extractMinimalOfficeName($office);
   return in_array($minimalOffice,
      ["clerk", "trustee", "supervisor", "treasurer", "mayor", "council", "commissioner", "city commissioner", "city commission",
       "city councilperson", "councilperson", "city council", "library"]);
}

function mayNeedDisambiguating(string $office): bool {
   return ! Str::contains($office, "school", "county commissioner", "college");
}

function extractMinimalOfficeName(string $office): string {
   $removeWords = ["partial", "term", "ending", "ward", "at", "large", "at-large", "board", "member", "year", "i", "ii", "iii", "iv", "v", "vi"];
   $result = [];
   foreach (Str::splitIntoTokens($office, " ") as $word) {
      if (in_array($word, $removeWords)) continue;
      if (intval($word) > 0)             continue;
      $result[] = $word;
   }
   return Str::join($result, " ");
}

function removePrecinct(string $precinctFullName): string {
   if (Str::contains($precinctFullName, "Precinct"))  return trim(Str::substringBefore ($precinctFullName, "Precinct"));
   if (Str::contains($precinctFullName, "precinct"))  return trim(Str::substringBefore ($precinctFullName, "precinct"));
   return $precinctFullName;
}
