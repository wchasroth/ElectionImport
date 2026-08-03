#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;

require "vendor/autoload.php";

//---phase80MatchCandidates.php
//
//   Scan thru stdin (output from phase07Districts.php).  Attempt to match each office and candidate
//   name with the entries in BOTH v4candidates and v4filings.
//
//   Report cases that match, and the respective table and id.
//   Report cases that DO NOT MATCH.  (We may want to eventually import them somewhere.)

$rows = Csv::loadTrimmed(STDIN, "\t");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

foreach ($rows as $row) {
   if (Str::startsWith($row[Column::YEAR], "#")) continue;

   $name = new MatchableName($row[Column::NAME]);

   $candidates = getCandidates($pdo, $row);
   $possibles  = prepareMatchables($candidates);
   $bestIndex  = $name->findBestMatch($possibles, 2);
   if ($bestIndex >= 0) {
      echo "Match-v4candidates:" . $candidates[$bestIndex]['id'] . " " . $candidates[$bestIndex]['name']
         . " for " . $row[Column::NAME] . "\n";
      continue;
   }

   $filings = getFilings($pdo, $row);
   $possibles  = prepareMatchables($filings);
   $bestIndex  = $name->findBestMatch($possibles, 2);
   if ($bestIndex >= 0) {
      echo "Match-v4filings:" . $filings[$bestIndex]['id'] . " " . $filings[$bestIndex]['name']
         . " for " . $row[Column::NAME] . "\n";
      continue;
   }

   echo "No-match:" . $row[Column::NAME] . "\n";

   // year  county  region  voteFor  name  party   votes_C votes_D votes_R votes_O votes_T
   // org office termlen cycle partial subdist district

   // org office district subdist (special case for subdist 0??)
}

function prepareMatchables(array $possibles): array {
   $matches = [];
   for ($i=0;   $i<count($possibles);   $i++) $matches[$i] = new MatchableName($possibles[$i]['name']);
   return $matches;
}

function getCandidates(AlfredPDO $pdo, array $row): array {
   $sqlFields = makeSqlFields($row);
   $sql = "SELECT * FROM v4candidates WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   return $result->getRows();
}

function getFilings(AlfredPDO $pdo, array $row): array {
   $sqlFields = makeSqlFields($row);
   $sql = "SELECT * FROM v4filings WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   return $result->getRows();
}

function makeSqlFields(array $row): SqlFields {
   return new SqlFields(['org' => $row[Column::ORG], 'office' => $row[Column::OFFICE],
      'district' => $row[Column::DIST], 'subdist' => $row[Column::SUBDIST]]);
}