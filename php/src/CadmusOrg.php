<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class CadmusOrg {
   public string $org       = "";
   public string $office    = "";
   public string $region    = "";
   public string $block     = "";
   public string $type      = "";
   public int    $termlen   = 0;
   public int    $termcycle = 0;
   public bool   $partial   = false;
   public int    $subdist   = 0;

   public OfficePhrase $officePhrase;

   public static function makeCadmusFromTitle(string $title, Jurisdictions $juris, $year=0): CadmusOrg {
      $title =   trim(strtolower($title)) . " ";
      $title = Str::replaceFirst($title, "- partial ",     " partial ");
      $title = Str::replaceFirst($title, "-partial ",      " partial ");
      $title = Str::replaceFirst($title, "- term ending ", " partial term ending ");

      $cadmus = new CadmusOrg($title, $year);

      // If we had seemingly ambiguous regions, add in their type: city, township, or village,
      // and try again.
      if ($cadmus->org == "unknown") {
         $region = $cadmus->region;
         $type = $juris->getType($region);
         $title = Str::replaceFirst($title, $region, "$region $type");
         $cadmus = new CadmusOrg($title, $year);
      }
      return $cadmus;
   }

//   function extractAndRemovePartial(string $title): string {
//      if (! Str::contains(" $title ", " partial "))  return $title;
//      $words  = Str::split($title, " ");
//      $result = [];
//      $state  = 'pre';
//      foreach ($words as $word) {
//         if      ($word  == "partial") { $state = 'partial';  $this->partial = 1;  continue; }
//
//         if ($state == 'pre')          { $result[] = $word;   continue; }
//
//         if ($state == 'partial') {
//            if ($word == "term"  ||  $word == "ending")        continue;
//            if (Str::contains($word, "/")  &&  intval($word) > 0) {
//               $this->termcycle = intval(Str::substringAfterLast($word, "/"));
//               $state = 'post';
//               continue;
//            }
//            $state = 'post';
//         }
//         $result[] = $word;
//      }
//
//      return Str::join($result, " ");
//   }

   function __construct(string $title, int $year=0) {
      $title = Str::replaceAll($title, ",", " ");
      $this->termcycle = $year;  // for cases where we don't know the termlen (yet!!)

      $partialTerm = new PartialTerm($title);
      $title         = $partialTerm->title;
      $this->partial = $partialTerm->isPartial;
      if ($partialTerm->termcycle > 0)  $this->termcycle = $partialTerm->termcycle;
      if ($partialTerm->termlen   > 0)  $this->termlen   = $partialTerm->termlen;
      if ($partialTerm->isPartial  &&  $partialTerm->termcycle === 0)  $this->termcycle = 0;

//      if (Str::contains($title, "year term")) {
//         // City Commissioner 4 Year Term for Watervliet City
//         // Village of Webberville Trustee (4 Year Term)
//         // Board Member for 6 Year Term Bridgman Public Schools
//         $ytPos  = Str::indexOf($title, "year term");
//         $forPos = Str::indexOf($title, " for ");
//         $prefix = $forPos > $ytPos ? substr($title, $forPos + 5) : "";  // case where 'for <name>' happens AFTER the "year term".
//         $titleWithTrailingYear = Str::substringBefore($title, "year term");
//         $titleWithTrailingYear = trim(Str::replaceAll($titleWithTrailingYear, "(", ""));
//         $this->termlen   = intval(trim(Str::substringAfterLast($titleWithTrailingYear, " ")));
//         $this->termcycle = $year + $this->termlen;
//         $title = trim($prefix . Str::substringBeforeLast($titleWithTrailingYear, " "));
//      }
//    echo "COb: $title\n";

      $te = new TermExtractor($title);
      if ($te->termlen > 0)  $this->termlen = $te->termlen;
      $title = $te->title;
//    echo "TermExt: $title\n";

//    echo "CO1: $title\n";
      $pt = new ParsedTitle($title, $partialTerm->pctInfo);
//    echo "CO2: " . $pt->getOffice() . "  '" . $pt->getJurisName() . "'\n";
      $this->org     = $pt->getOrg();
      $this->office  = $pt->getOffice();
      $this->subdist = $pt->getDistrict();
      $this->region  = $pt->getJurisName();
      $this->type    = $pt->getJurisType();

      $this->officePhrase = $pt->getOfficePhrase();

      if (empty($this->org))  $this->org = "unknown";
   }

   function getOfficePhrase (): OfficePhrase  { return $this->officePhrase; }
}
