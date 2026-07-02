#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\NameSimplifier;

require "vendor/autoload.php";

$rows = Csv::loadTrimmed(STDIN, "\t");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$districtCache = [];
$voteForCache  = makeVoteForCache($pdo);

$debug = ($argc == 2) && $argv[1] == "-d";

foreach ($rows as $row) {
   #---Comments
   if (Str::startsWith($row[Column::YEAR], "#")) {
      $row[Column::DIST] = "district";
      $row[Column::INCUMBENT] = 'incumbent';
      writeRow($row, "diag", "cache-key");
      continue;
   }

   if (Str::startsWith ($row[Column::ORG], "lib"))  continue;  // skipping library council for now.
   $row[Column::DIST]      = "";   // Because PHP arrays are NOT REALLY FUCKING ARRAYS!   Grrr.
   $row[Column::INCUMBENT] = "";

   $cacheKey = makeCacheKey($row);
   $diagnosticType  = "";

   #---Check cache
   $district = $districtCache[$cacheKey] ?? 0;
   if ($district > 0)  {
      $row[Column::DIST] = strval($district);
      $diagnosticType  = "cache";
//    writeRow($row, "cache", $cacheKey);   continue;
   }

   else if (Str::startsWith ($row[Column::ORG], "cnty"))  $row[Column::DIST] = $row[Column::COUNTY];
   else {
      if ($debug) fwrite(STDERR, "#" . Str::join($row, "\t") . "\n");
      $district = getDistrictFromDb($pdo, $row[Column::ORG], $row[Column::REGION], $row[Column::COUNTY]);
      $row[Column::DIST] = strval($district);
      $districtCache[$cacheKey] = $district;
   }

   //---fix missing voteFor column, if possible.

//   if (Str::contains($row[Column::NAME], "Darling")) {
//      fwrite(STDERR, "votefor=" . $row[Column::VOTEFOR] . "\n");
//      $key = makeVoteForKey($row[Column::ORG], $row[Column::OFFICE]);
//      fwrite(STDERR, "key=$key\n");
//      fwrite(STDERR, "value=" . strval($voteForCache[$key] ?? 0) . "\n");
//   }

   $voteForNumber = intval($row[Column::VOTEFOR]);
   if ($voteForNumber > 20) {
      fwrite(STDERR, "VoteForTooBig: " . Str::join($row, "\t") . "\n");
   }
   if ($voteForNumber == 0) {
//    $voteForKey = $row[Column::ORG] . ":" . $row[Column::OFFICE];
      $voteForKey = makeVoteForKey($row[Column::ORG], $row[Column::OFFICE]);
      if (($voteForCache[$voteForKey] ?? 0) == 1) {
         $row[Column::VOTEFOR] = "1";
      }
   }

   if (empty($diagnosticType)) {
      $diagnosticType = ($district > 0 ? "db" : "no-data");
   }
// writeRow($row, ($district > 0 ? "db" : "no-data"), $cacheKey);
   writeRow($row, $diagnosticType, $cacheKey);
}

function makeVoteForKey(string $org, string $office): string {
   return "$org:$office";
}

function makeVoteForCache(AlfredPDO $pdo): array {
   $result = $pdo->run("SELECT org, office FROM s4titles WHERE seats=1");
   $voteForCache = [];
   foreach ($result->getRows() as $row) {
//    $voteForKey = $row['org'] . ":" . $row['office'];
      $voteForKey = makeVoteForKey($row['org'], $row['office']);
      $voteForCache[$voteForKey] = 1;
   }
   return $voteForCache;
}

function makeCacheKey(array $row): string {
   return Str::join([$row[Column::REGION], $row[Column::ORG]], ":");
}

function writeRow (array $row, string $diag="", $key=''): void {
   $row[Column::DIAG]  = $diag;
   $row[Column::CACHE] = $key;
   echo Str::join($row, "\t") . "\n";
}

function getDistrictFromDb(AlfredPDO $pdo, string $org, string $region, string $countyNum): int {
   $region = trim($region, "-");
   if (Str::startsWith($org, "vil")) {
      $name = NameSimplifier::simplifyVillageName($region);
      $sql = "SELECT DISTINCT id FROM s4villages WHERE simplename='$name' AND county_id=$countyNum";
   }
   else if (Str::startsWith($org, "city")) {
      $name = NameSimplifier::simplifyJurisdictionName($region);
      $sql = "SELECT DISTINCT id FROM s4jurisdictions WHERE simplename='$name' AND type='c' AND county_id=$countyNum";
   }
   else if (Str::startsWith($org, "town")) {
      $name = NameSimplifier::simplifyJurisdictionName($region);
      $sql = "SELECT DISTINCT id FROM s4jurisdictions WHERE simplename IN ('$name', '$name charter') AND type='t' AND county_id=$countyNum";
   }
   else if (Str::startsWith($org, "schl")) {
      $name = NameSimplifier::simplifySchoolName($region);
      $sql = "SELECT DISTINCT id FROM s4schools WHERE simplename='$name' AND county_id=$countyNum";
   }
   else if (Str::startsWith($org, "comcol")) {
      $name = NameSimplifier::simplifyCommCollegeName($region);
      $sql = "SELECT DISTINCT id FROM s4commcolleges WHERE simplename='$name'";
   }
   else {
      fwrite(STDERR, "PHASE 7 BAD ORG: $org, $region, $countyNum\n");
      return 0;
   }

   $result = $pdo->run($sql);
   if ($result->failed()  ||  $result->getRowcount() == 0)  {
      fwrite(STDERR, "PHASE 7 ERROR: sql=$sql, region=$region\n");
      return 0;
   }
   return intval($result->getRows()[0]['id']);
}
