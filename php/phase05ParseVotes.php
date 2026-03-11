#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\ElectionImport\Column;

require "vendor/autoload.php";

$rows = Csv::loadTrimmed(STDIN, "\t");
echo "#yyyy-mm-dd\tcounty#\ttitle\tvoteFor#\tcandidateName\tpartyLetter\tvotes_C\tvotes_D\tvotes_R\tvotes_O\tvotes_T\n";

$office = new CadmusOffice([]);
foreach ($rows as $row) {
   if (Str::startsWith($row[0], "#"))  continue;
   $title = $row[2];
   if ($title != $office->title) {
      writeCandidates($office);
      $office = new CadmusOffice($row);
   }

   $office->addCandidate($row);
}

writeCandidates($office);

function writeCandidates(CadmusOffice $office): void {
   $candidates = $office->computeResults();

   foreach ($candidates as $candidate)  {
      $name = $candidate[Column::NAME];
      if (Str::contains(strtolower($name), "write-in", "write in")) {
         $candidate[Column::NAME] = preg_replace('/\(*write[ -]in\)*/i', '', $name);
         $candidate[Column::PARTY] = 'W';
      }
      else if (Str::contains($name, "(W")) {
         $candidate[Column::NAME] = trim(Str::replaceFirst($name, "(W)", " "));
         $candidate[Column::PARTY] = 'W';
      }
      else {
         $candidate[Column::PARTY] = computePartyLetter($candidate[Column::PARTY]);
      }
      if (empty(trim($candidate[Column::NAME])))  continue;

      echo Str::join($candidate, "\t") . "\n";

//      $candidate[Column::PARTY] = Str::startsWith(strtolower($candidate[Column::NAME]), "write-in", "write in")
//         ? 'W'
//         : computePartyLetter($candidate[Column::PARTY]);
//      echo Str::join($candidate, "\t") . "\n";
   }
}

function computePartyLetter (string $partyName): string {
   static $partyLetter = [
      'd' => 'D', 'dem' => 'D', 'democ' => 'D',
      'l' => 'L', 'lib' => 'L', 'liber' => 'L',
      'a' => 'A', 'nlp' => 'A', 'natur' => 'A',
      'n' => 'N', 'non' => 'N', 'nonpa' => 'N', 'npa' => 'N', 'nopar' => 'N', 'non-p' => 'N',
      'r' => 'R', 'rep' => 'R', 'repub' => 'R',
      't' => 'T', 'ust' => 'T', 'ustax' => 'T',
      'c' => 'C', 'wcp' => 'C', 'worki' => 'C',
      'g' => 'G', 'grn' => 'G', 'green' => 'G',
      'w' => 'W', 'wri' => 'W', 'write' => 'W',
      '?' => '?'
   ];
   $partyName = strtolower($partyName);
   $partyName = Str::replaceAll($partyName, ' ', '');
   $partyName = Str::replaceAll($partyName, '.', '');
   $partyName = trim(substr($partyName, 0, 5));
   if (empty($partyName))      return '?';
   return $partyLetter[$partyName] ?? 'X';
}
