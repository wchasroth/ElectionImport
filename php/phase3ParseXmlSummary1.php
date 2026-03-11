#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use DOMDocument;
use CharlesRothDotNet\Alfred\FileUtils;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

// Translates XML election report to CSV.
//
// Input:
//    PDF election report, layout style "summary1", run through UPDF to convert to XML,
//    then run thru local script 'xmlClean" to format and transform "<ss:Data>" to "<ssData">.
//
// Output: our standard "phase3" CSV.  See https://volunteers.mivoter.org/building-db.html for details.
//
// (Note that winners are not selected, that is done in a later phase.)

// The translation is done by running all of the rows thru a finite state machine, represented by this enum.
// This logic is HEAVILY dependent on the specific layout of the data; different PDF formats will probably require
// different logic.
enum State {
   case findBold;              // Looking for a bolded one-cell title, that indicates the start of a particular race.
   case findCandidate;         // From there, looking for the 1st actual candidate row.
   case addCandidate;          // We're at a real candidate, keep slurping up candidates until we hit a not-candidate.
                               //   (in which case we jump back to state findBold.
}

if ($argc < 3) {
   fwrite (STDERR, "Usage: php phase3ParsePdfSummary1.php yyyy-mm-dd county#\n");
   exit(1);
}
$text = file_get_contents("php://stdin");
$electionDate = $argv[1];
$countyCode   = $argv[2];

// CSV column header line.  All lines that begin with "#" are comments.
echo "#yyyy-mm-dd\tcounty#\ttitle\tvoteFor#\tcandidateName\tpartyName\t#votes\n";

// 1. Find row with single cell with bold text: that's title
//    a.  If ignorableRace, reset to state 1
// 2. Skip rows until "Candidate" or "Choice" found.
// 3. Accumulate following rows, until "Total Votes" found.  Reset to state 1
$doc = simplexml_load_string($text);
$allRows = extractAllRowsFrom($doc);

$votesColumn = 4;
$voteFor = 0;
$state = State::findBold;
foreach ($allRows as $row) {
   $cells = getChildren($row, "Cell");
   $cell0Text = getCellText($cells[0]);
// fwrite(STDERR, $state->name . "  " . cellsText($cells) . "\n");

   if ($state == State::findBold) {
      $text = getSingleCellWithBold($cells);
//    fwrite(STDERR, $state->name . "  $text\n");
      if (Str::startsWith($text, "Choice ")) {  // Horribly mangled line!
         fwrite(STDERR, "ERROR: (a) mangled line: $text\n");
      }
      else {
         $title = extractOfficeName($text);   // Pluck apart title from "vote for #".
         $voteFor = extractVoteFor($text);
         if (!empty($title)) $state = State::findCandidate;  // we ignore/skip-over top-level races, proposals, etc.
      }
   }
   else if ($state == State::findCandidate) {
      if (count($cells) == 1)  continue;
//    fwrite(STDERR, $state->name . "  $cell0Text\n");
      if (count($cells) < 3) {
         if (Str::startsWith($cell0Text, "Choice"))  $votesColumn = 6;
         else {
            fwrite(STDERR, "ERROR: (b) $title: " . cellsText($cells) . "\n");
            $state = State::findBold;
         }
      }
      else if ($cell0Text == "Candidate"  ||  $cell0Text == "Choice") {
         $votesColumn = getVotesColumn($cells);
         $state = State::addCandidate;  // last thing before actual candidates is the row "Candidate".
      }
   }
   else if ($state == State::addCandidate) {
//    fwrite(STDERR, $state->name . "  $cell0Text\n");
      if (isEndOfCandidates($cell0Text))  $state = State::findBold;
      else {
         if (count($cells) < $votesColumn) {
//          fwrite(STDERR, "PROBLEM: (c) " . $cell0Text . "\n");
            continue;
         }
         $name  = getCellText($cells[0]);
         $party = getCellText($cells[1]);
         if (Str::contains($name, "(W)")) {
            $name  = trim(Str::replaceFirst($name, "(W)", ""));
            $party = "write-in";
         }
         $votes = getCellText($cells[$votesColumn]);
         echo "$electionDate\t$countyCode\t$title\t$voteFor\t$name\t$party\t$votes\n";
      }
   }
}

function isEndOfCandidates(string $cell0Text): bool {
   if ($cell0Text == "Total Votes")                return true;
   if (Str::startsWith($cell0Text, "Cast Vote"))   return true;
   if (empty($cell0Text))                          return true;
   return false;
}

function getVotesColumn(array $cells): int {
   for ($i=1;   $i<count($cells);   ++$i) {
      if (getCellText($cells[$i]) == "Total") return $i;
   }
   fwrite (STDERR, "could not find votes column, defaulting to 4\n");
   return 4;  // should never happen, but need a reasonable default just in case!
}

function extractAllRowsFrom(\SimpleXMLELement $document): array {
   $allRows = [];
   $worksheets = getChildren($document, "Worksheet");
   foreach ($worksheets as $worksheet) {
      $tables = getChildren($worksheet, "Table");
      foreach ($tables as $table) {
         $rows = getChildren($table, "Row");
         foreach ($rows as $row) $allRows[] = $row;
      }
   }
   return $allRows;
}

function getSingleCellWithBold(array $cells): string {
   if (count($cells) != 1)  return "";
   foreach (getChildren($cells[0], "ssData") as $ssData) {
      foreach (getChildren($ssData, "Font") as $font) {
         foreach (getChildren($font, "B") as $b => $value) {
            return cleanText($value);
         }
      }
   }
   return "";
}

//--- Some of these functions will eventually be extracted into a utility class, since they will be the
//    same in most PDF/XML layouts.
function getCellText(\SimpleXMLELement $cell): string {
   foreach (getChildren($cell, "ssData") as $ssData) {
      foreach (getChildren($ssData, "Font") as $font) {
         foreach (getChildren($font, "B") as $bold => $value)   return cleanText($value);
         return cleanText($font);
      }
      return cleanText($ssData);
   }
   return "";
}

function cleanText(\SimpleXMLElement $node) {
   return trim((string) $node, " \n\t");
}

function getChildren(\SimpleXMLELement $node, string $name): array {
   $result = [];
   foreach ($node->children() as $child) {
      if ($child->getName() == $name)  $result[] = $child;
   }
   return $result;
}

function getVoteForClauses(): array {
   return [" (Vote for ", " - Vote for not more than "];
}

function extractOfficeName (string $title): string {
   foreach (getVoteForClauses() as $clause) {
      if (Str::contains($title, $clause))     return trim(Str::substringBefore($title, $clause));
   }
   return $title;
}

function cellsText(array $cells): string {
   $texts = [];
   for ($i=0;   $i<count($cells); ++$i) {
      $texts[] = trim(Str::replaceAll(getCellText($cells[$i]), "\n", " "));
   }
   $result = join(" | ", $texts);
   $result = Str::replaceAll($result, "  ", " ");
   return $result;
}

function extractVoteFor (string $title): string {
   foreach (getVoteForClauses() as $clause) {
      if (Str::contains($title, $clause)) {
         return strval(intval(Str::substringAfter($title, $clause)));
      }
   }
   return "1";
}
