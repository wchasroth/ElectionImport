#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ImportTools;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$rows = Csv::loadTrimmed(STDIN, "\t");

//yyyy-mm-dd   county#   title   voteFor#   candidateName   partyLetter   #votes
foreach ($rows as $row) {
   if (isIgnorableRace($row[Column::REGION]))  continue;  // really full title

   if (! array_key_exists(Column::VOTES_C, $row)) {
      $votes = $row[Column::PARTY];
      if (intval($votes) > 0) {
         $row[Column::PARTY] = "";
         $row[Column::VOTES_C] = $votes;
      }
   }
   if (intval($row[Column::VOTES_C]) == 0)         continue;

   $candidate = safeLower($row[Column::NAME]);
   if ($candidate == "yes")                        continue;
   if ($candidate == "no")                         continue;
   if (Str::startsWith($candidate, "rejected "))   continue;
   if (Str::startsWith($candidate, "unassigned"))  continue;
   if (Str::startsWith($candidate, "unqualified")) continue;
   if (Str::contains  ($candidate, "deceased"))    continue;
   if (Str::contains  ($candidate, "times cast"))  continue;
   if (Str::contains  ($candidate, "unresolved ")) continue;

   echo Str::join($row, "\t") . "\n";
}

function safeLower($text): string {
   if ($text == null)  return "";
   return strtolower($text);
}

function isIgnorableRace(string $title): bool {
   static $ignorableRaces = [
      "election summary", "straight party",
      "president/vice", "president / vice", "president and vice",
      "us senator", "united states",
      "representative in", "rep in congress", "us rep", "congress", "rep in cong",
      "governor", "secretary of state", "attorney general",
      "state senator", "state senate",
      "state representative", "rep in state legislature", "rep in state legis", "state rep", "state district representative", "representative",
      "state legislature",
//    "legislative rep", "legislative senator",
      "legislative",
      "deceased", "(deceased",
      "ballots cast", "registered voters", "votes cast", "times cast",
      "member of the state board", "state board of education", "state board of ed", "state bd", "board of education",
      "regent", "trustee of michigan state", "trustees michigan state", "michigan state university", "trustee of mich state", "trustee msu",
      "governor of wayne state", "governors wayne state", "msu trustee", "trustees msu", "wayne state gov",
      "proposal", "proposition", "amendment", "millage", "question", "transportation", "prop",
      "judge", "justice", "court", "judicial",
      "ordinance", "ordianance", "advisory", "suppression", "authority", "city charter", "sinking fund",
      "hudson memorial building", "fire and rescue", "annexation", "tax rate", "road and bridges", "resolution",
      "increase", "fire protection", "city parcel", "override", "fire department", "millage",
      "repair", "renewal", "veterans", "emergency", "fire and ems", "waste removal", "streetlight",
      "county convention",
      "community center",
      "light and power",
      "police protection", "richfield township police", // hack!
      "disincorporation",
      "advisory",            // see City of Gladstone Mayoral Advisory
      "charter commission",  // see Charter Commission for Tecumseh City
      "charter revision", "charter comm", "petition", "infrastructure",
      "agreement", "legalize", "liquor license", "surcharge",
      "change name", "vacated", "vacating",
      "smith-kimball",  // community center, not doing these now.
      "intermediate",  "isd", // intermediate school districts, not doing these now, but will need to eventually.
      "metropolitan district commissioner",  // weird fire district case, don't know how to handle it.
      // TODO: look into intermediate school district stuff like this.  Ignore for now
      "ignore",  // For specific cases where it was easier to hand-edit in "ignore" in a field
      "midland esa", "esa isd", "northwest education services"
   ];
   static $ignorableExactMatch = ["library board director"];  // usually an error in the PDF listing!!

   $title = strtolower(trim($title));
   $title = Str::replaceAll($title, ".", "");
   if (empty($title))  return true;

   foreach ($ignorableRaces as $ignorableRace) {
      if (Str::contains($title, $ignorableRace)) return true;
   }
   $title = trim(Str::substringBefore($title, "("));
   foreach ($ignorableExactMatch as $ignorableExact) {
      if ($title == $ignorableExact) return true;
   }
   return false;
}
