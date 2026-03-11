<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class ParsedTitle {
   private string $org              = "";    // same as seat26.city
   private string $office           = "";    // same as seat26.office (if we can tell!)
   private string $genericOffice    = "";    // generic name for office if we can't figure out previous field definitively
   private string $jurisdictionType = "";    // t (township), c (city), v (village).
                                             //     Also y (county), l (library), but less important.  Or empty in all other cases.
   private string $jurisdictionName = "";    // without the city/township/county etc. phrase
   private int    $district         = 0;     // district number (ward, etc.) of any sort, if found (else 0)

   private OfficePhrase $officePhrase;

   private static array $countyTitle2office = [
      "clerk and register"  => "clerkreg",
      "clerk & register"    => "clerkreg",
      "clerk/ reg"          => "clerkreg",
      "pros atty"       => "atty",
      "executive"       => "executive",
      "attorney"        => "atty",
      "sheriff"         => "sheriff",
      "register"        => "reg",
      "reg of deeds"    => "reg",
      "treasurer"       => "treas",
      "treasure"        => "treas",
      "treas"           => "treas",
      "auditor"         => "auditor",
      "mine"            => "mine",
      "road"            => "road",
      "rd comm"         => "road",
      "drain comm"      => "drain",
      "drain"           => "drain",
      "water resources commissioner" => "drain",
      "works"           => "works",
      "coroner"         => "coroner",
      "clerk"           => "clerk",
      "surveyor"        => "surv",
      "prosecuting"     => "atty",
      "clerk/treasurer" => "clerktreas",
      "clerk/register"  => "clerkreg",
      "clerk/reg"       => "clerkreg"
   ];

   private static array $cityTitle2office = [
      "mayor"          => "mayor",
      "president"      => "pres",
      "treasurer"      => "treas",
      "treasure"       => "treas",
      "treas"          => "treas",
      "assessor"       => "assess",
      "elections"      => "elect",
      "clerk"          => "clerk",
      "clerk/treasurer" => "clerktreas",
      "supervisor"     => "super",
      "constable"      => "cons",
      "police"         => "police",
      "comptroller"    => "comp",
      "review"         => "review"
   ];

   private static array $villageTitle2office = [
      "mayor"          => "mayor",
      "president"      => "pres",
      "treasurer"      => "treas",
      "treasure"       => "treas",
      "treas"          => "treas",
      "constable"      => "cons",
      "clerk"          => "clerk",
   ];

   private static array $townshipTitle2office = [
      "supervisor"     => "town-super",
      "super"          => "town-super",
      "clerk"          => "town-clerk",
      "treasurer"      => "town-treas",
      "treasure"       => "town-treas",
      "treas"          => "town-treas",
      "secretary"      => "town-secty",
      "constable"      => "town-cons",
      "park comm"      => "town-park",
      "park"           => "town-park",
      "parks"          => "town-park",
      "community center"  => "town-comm"
   ];

   private static array $noiseWords = [
      "for", "clerk", "commission", "commissioner", "charter", "of", "public", "prosecuting", "member",
      "board", "local", "the",
//    "trustee", "trustees",
      "community", "county", "deeds", "vill",
      "and", "nonpartisan",
      "ward"
//    "mayor", "treasurer", "assessor", "constable"
   ];

   private static array $horribleSpellingCorrections = [
      "grcc"  => "grand rapids cc",
      "grps"  => "grand rapids public schools",
      "egr"   => "east grand rapids",
      "hgts"  => "heights",
      "gr"    => "grand rapids",
      "gocc"  => "glen oaks community college",
      "kvcc"  => "kalamazoo valley community college",
      "lmc"   => "lake michigan community college",
      "smc"   => "southwestern michigan community college",
      "paw paw" => "paw-paw",
      "plymouth canton" => "plymouth-canton",
      "aaps"    => "ann arbor public schools",
      "mayor-"    => "mayor ",
      "assessor-" => "assessort ",
      "clerk-"    => "clerk ",
      "commission-" => "commission ",
      "council-at-large-" => "council-at-large ",
      "councilman"  => "council",
      "council-"    => "council ",
      "treasurer-"  => "treasurer ",
      "assessort"   => "assessor",
      "lathrup village" => "lathrup city" // It's a city with 'village' in its name.  Good grief!
   ];

   function __construct(string $title, string $pctInfo) {
      $title   = strtolower($title);
      $pctInfo = strtolower($pctInfo);
      $title  = Str::replaceFirst($title, ", ", " ");
      $title  = Str::replaceAll  ($title, ". ", " ");
      $title  = trim($title, " .");
      $title  = $this->removeParentheticalPhrase($title);
      $title  = trim($this->removeParentheticalPhrase($title), " -");
      $title    = $this->fixHorribleSpellingCases($title);
      $pctInfo  = $this->fixHorribleSpellingCases($pctInfo);
      $phrase = new OfficePhrase($title);

      $this->district = $this->extractAndRemoveDistrict($phrase);

      // If we can't extract the office & jurisdiction, try again by adding the detailed precinct info.
      $retryText = $phrase->getTop();
      if (! $this->extractAndRemoveOffice($phrase, false)) {
         $phrase->push ("$retryText $pctInfo");
//       echo "IN RETRY\n";
         if (! $this->extractAndRemoveOffice($phrase, true)) {
            fwrite (STDERR, "PHASE 6 may be error: $retryText $pctInfo\n");
         }
      }

//    $this->jurisdictionName = $phrase->getTop();  // with all offices and noise removed, should be literally just the name!
      $this->officePhrase = $phrase;
   }

   function getOrg():           string  { return $this->org; }
   function getOffice():        string  { return $this->office; }
   function getGenericOffice(): string  { return $this->genericOffice; }
   function getJurisName():     string  { return $this->jurisdictionName; }
   function getDistrict():      int     { return $this->district; }
   function getJurisType():     string  { return $this->jurisdictionType; }
   function getOfficePhrase():  OfficePhrase  { return $this->officePhrase; }

   function stderr(string $prefix, OfficePhrase $phrase): void {
      fwrite(STDERR, $prefix . " " . $phrase->getTop() . "\n");
   }

//   function extractAndRemoveJurisdiction(OfficePhrase $phrase): void {
//      if      ($phrase->foundAndRemovedPhrase("township"))  $this->jurisdictionType = "t";
//      else if ($phrase->foundAndRemovedPhrase("village"))   $this->jurisdictionType = "v";
//      else if ($phrase->foundAndRemovedPhrase("city"))      $this->jurisdictionType = "c";
//   }

   function fixHorribleSpellingCases(string $text): string {
      $text = " $text ";
      $text = Str::replaceAll($text, " council - ", " council ");
      $text = Str::replaceAll($text, "-city", " - city");
      foreach (self::$horribleSpellingCorrections as $bad => $good) {
         if (Str::contains($text, " $bad ")) {
            $text = Str::replaceFirst ($text, " $bad ", " $good ");
         }
      }
      $text = trim($text);
      if (Str::endsWith($text, " part"))  $text = Str::substringBefore($text, " part");  // Shows up, seems to have no meaning!
      return $text;
   }

   function extractAndRemoveOffice(OfficePhrase $phrase, bool $hasPctInfo): bool {
      if ($phrase->foundAndRemovedPhrase("county commissioner", "county comm", "co commissioner")  ||  $phrase->getTop() == "commissioner") { // last one is tricky!
         $this->org = "cnty-com";
         $this->jurisdictionType = "y";
      }

      else if ($phrase->foundAndRemovedPhrase("schools", "school", "lsd", "sch bd")) {
         $this->org = "schl-cou";
         $this->jurisdictionType = "s";
         $phrase->foundAndRemovedPhrase("members", "member", "community", "area", "district", "board", "brd", "mem", "bd");
      }

      else if ($phrase->foundAndRemovedPhrase("library", "libraries", "lib")) {
         $this->org = "libry-cou";
         $this->genericOffice    = "library";
         $this->jurisdictionType = "l";
         $phrase->foundAndRemovedPhrase("member", "board", "area");
      }

      else if ($phrase->foundAndRemovedPhrase("village"))  {
//       $this->stderr("Village: ", $phrase);
         $phrase->foundAndRemovedPhrase("township");   // remove if supplied via precinct info
         $this->jurisdictionType = "v";
         if ($phrase->foundAndRemovedPhrase("trustees", "trustee", "council", "trustee/council")) {
            $this->org  = "vil-cou";
            $this->office = "council";
         }
         else {
            $this->org = "vil";
            $this->jurisdictionType = "v";
            if (! $this->findAndRemoveSpecialCase($phrase, "clerk", "treasurer", "clerktreas"))
               $this->office = $this->findOfficeInMapThenRemove($phrase, self::$villageTitle2office);
         }
         if (empty(trim($phrase->getTop())))  return false;
      }

      else if ($phrase->foundAndRemovedPhrase("city commissioner", "council member", "commission member", "councilmember",
               "city council", "city commission", "council-at-large", "city member",
               "member/commissioner", "commissioner-at-large", "commissioner at-large", "city councilman",
               "councilperson")  ||
            $this->findAndRemoveSpecialCase($phrase, "city", "wards", "")  ||
            $this->findAndRemoveSpecialCase($phrase, "city", "commission", "")
      ) {
         $phrase->foundAndRemovedPhrase("at-large", "at large");
         $this->org = "city-cou";
         $this->jurisdictionType = "c";
//       echo "PTCou: " . $phrase->getTop() . "\n";
//       if (empty($phrase->getTop()))   fwrite(STDERR, "PHASE 6: ERROR: " . $phrase->getAllPhrases()[0] . "\n");
         // should be caught at return at the end
      }

      else if ($phrase->foundAndRemovedPhrase("city"))  {
         $this->jurisdictionType = "c";
         if (! $phrase->contains("police")  &&  $phrase->foundAndRemovedPhrase("council", "commissioner"))  {
            $phrase->foundAndRemovedPhrase("at-large", "at large");
            $this->org = "city-cou";
         }
         else {
            $this->org = "city";
            if (! $this->findAndRemoveSpecialCase($phrase, "clerk", "treasurer", "clerktreas")) {
               $this->office = $this->findOfficeInMapThenRemove($phrase, self::$cityTitle2office);
            }
         }
         if (empty($phrase->getTop()))   fwrite(STDERR, "PHASE 6: ERROR: " . $phrase->getAllPhrases()[0] . "\n");
      }

      else if ($phrase->foundAndRemovedPhrase("township", "townshp", "twp"))  {
         $this->jurisdictionType = "t";
         if ($phrase->foundAndRemovedPhrase("trustee", "council")) {
            $this->org  = "town-cou";
            $this->office = "council";
         }
         else {
            $this->org = "town";
            $this->office = $this->findOfficeInMapThenRemove($phrase, self::$townshipTitle2office);
         }
      }

      else if ($phrase->foundAndRemovedPhrase("college", "cc", "comm coll"))  {
         $phrase->foundAndRemovedPhrase("comm", "trustee", "trustees", "bd");
         $this->org = 'comcol-cou';
      }

      else if ($phrase->foundAndRemovedPhrase("county")  ||  $this->isACountyOffice($phrase))  {
         $this->org  = "cnty";
         $this->jurisdictionType = "y";
         if (! $this->findAndRemoveSpecialCase($phrase, "clerk", "register", "clerkreg")  &&
             ! $this->findAndRemoveSpecialCase($phrase, "road",  "drain",    "rd-drn")) {
//          echo "county: " . $phrase->getTop() . "\n";
            $this->office = $this->findOfficeInMapThenRemove($phrase, self::$countyTitle2office);
         }
//       fwrite(STDERR, "ERR1: " . $phrase->getTop() . "\n");
      }

      // Cases where no 'city', 'county', 'village'.  Default to city.
      else if ($phrase->foundAndRemovedPhrase("mayor")) {
         $this->org = "city";
         $this->jurisdictionType = "c";
         $this->office = "mayor";
      }

      else if ($phrase->foundAndRemovedPhrase("clerk")  &&  $hasPctInfo) {
         $this->org = "city";
         $this->jurisdictionType = "c";
         $this->office = "clerk";
      }

      else if ($phrase->foundAndRemovedPhrase("commissioner")) {
         $this->org = "city-cou";
         $this->jurisdictionType = "c";
         $this->office = "";
      }

      else if ($phrase->foundAndRemovedPhrase("treasurer")  && $hasPctInfo) {
         $this->org = "city";
         $this->jurisdictionType = "c";
         $this->office = "treas";
      }

      else return false;

      // Remove 'noise'
      $phrase->push(ltrim ($phrase->getTop(), " 123456789"));  // Remove leading #s, like "board member 2 for ..."
      $this->removeNoise($phrase);
//    echo "PT predup: " . $phrase->getTop() . "\n";
      $this->jurisdictionName = Str::removeDuplicateWords($phrase->getTop());
//    echo "PT postdup: " . $this->jurisdictionName . "\n";
      return Str::startsWith($this->org, "cnty")  ||  ! empty($this->jurisdictionName);
   }

   function removeNoise(OfficePhrase $phrase): void {
      if (Str::contains($phrase->getTop(), " ward ")) {  // remove "ward #"
         $phrase->push(preg_replace("/ ward [0-9]/", " ", $phrase->getTop()));
      }
      if (Str::contains($phrase->getTop(), "washtenaw results only")) {  // may have to generalize, but so far, only washtenaw. (??)
         $phrase->push(Str::replaceFirst($phrase->getTop(), "washtenaw results only", ""));
      }
      foreach (self::$noiseWords as $noiseWord)  $phrase->foundAndRemovedPhrase($noiseWord);
      if ($this->jurisdictionType != "v")  $phrase->foundAndRemovedPhrase("city");
      $top = $phrase->getTop();
      if (Str::startsWith($top, "-"))  $top = substr($top, 1);
      if (Str::contains($top, " - "))  $top = Str::replaceAll($top, " - ", " ");
      if (Str::contains($top, "- "))   $top = Str::replaceAll($top, "- ", "-");
      $phrase->push(trim($top));
   }

   function isACountyOffice(OfficePhrase $phrase): bool {
      $temp = new OfficePhrase($phrase->getTop());
      foreach (self::$countyTitle2office as $title => $office) $temp->foundAndRemovedPhrase($title);
//    echo "temp1=" . $temp->getTop() . "\n";
      $this->removeNoise($temp);
//    echo "temp2=" . $temp->getTop() . "\n";
      return empty(trim($temp->getTop()));
   }

   function findAndRemoveSpecialCase (OfficePhrase $phrase, string $title1, string $title2, string $office): bool {
//    echo "Special: [$title1 $title2] " . $phrase->getTop() . "\n";
      // Ugh, very special case (e.g. "clerk and register").
      $text = " " . $phrase->getTop() . " ";
      if (Str::contains($text, " $title1 ")  &&  Str::contains($text, " $title2 ")  &&  ! $phrase->contains("$title1/$title2")) {
         $this->office  = $office;
         $phrase->foundAndRemovedPhrase($title1);
         $phrase->foundAndRemovedPhrase($title2);
         return true;
      }
      return false;
   }

   function findOfficeInMapThenRemove(OfficePhrase $phrase, array $map): string {
      foreach ($map as $title => $office) {
         if ($phrase->foundAndRemovedPhrase($title))  return $office;
      }
      return "";
   }



   function extractAndRemoveDistrict(OfficePhrase $phrase): int {
      $isSchool = Str::contains($phrase->getTop(), "school");
      $number = 0;
      $words  = Str::split($phrase->getTop(), " ");
      $result = [];
      foreach ($words as $word) {
         if (Str::startsWith($word, "#"))  $word = Str::substringAfter($word, "#");
         $possibleNumber = strpbrk($word, "0123456789");
         if ($possibleNumber !== false  &&  ! $isSchool) {
            $number = intval($possibleNumber);
            continue;
         }
         if (in_array($word, ["district", "dist", "ward", "no", "precinct"]))  continue; // removed "wards" see city of niles
         if (in_array($word, $result))                                         continue;
         $result[] = $word;
      }
      $phrase->push(Str::join($result, " "));
//    echo "phrase = " . $phrase->getTop() . "  num=" . $number . "\n";
      return $number;
   }

   function removeParentheticalPhrase (string $title): string {
      $phrase = Str::substringBetween($title, "(", ")");
      if (empty($phrase))  return $title;
      $title = Str::replaceFirst($title, "($phrase)", "");
      $title = trim($title, " .");
      return $title;
   }


}
