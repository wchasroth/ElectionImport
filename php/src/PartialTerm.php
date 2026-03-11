<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class PartialTerm {
   public string $title;
   public string $pctInfo;
   public bool   $isPartial = false;
   public int    $termcycle = 0;
   public int    $termlen   = 0;

   function removedFullTermNoise(string $title, string $noise): bool {
      if (! Str::contains($title, $noise))  return false;
      $title = trim(Str::replaceFirst($title, $noise, " "));
      $this->title = $title;
      return true;
   }

   function fixTermEndVariants(string $title, string $termEndWord): string {
      // Similarly, special case of " term mm/dd/yyyy" is also partial term.
      if (! Str::contains($title, " $termEndWord "))  return $title;

      $rest = trim(Str::substringAfter ($title, " $termEndWord "));
      $rest = trim(Str::substringBefore($rest, " "));
      if (intval($rest) > 0  &&  Str::contains($rest, "/")) {
         $title = Str::replaceFirst($title, " $termEndWord ", " partial term ending ");
      }
      return $title;
   }

   function __construct(string $title) {
      $this->pctInfo = "";
      if (Str::contains($title, "{")) {
         $this->pctInfo = Str::substringBetween($title, "{", "}");
         $title = trim(Str::replaceFirst($title, "{" . $this->pctInfo . "}", ""));
         $this->pctInfo = strtolower($this->pctInfo);
         if (Str::contains($this->pctInfo, " precinct")) {
            $this->pctInfo = trim(Str::substringBefore($this->pctInfo, " precinct", ""));
         }
      }

      $title = strtolower($title);
      $title = Str::replaceFirst($title, "(partial term)", " partial term ");

      //---Spelling corrections
      $title = Str::replaceFirst($title, " fulll ", " full ");  // may need to abstract spelling corrections here.
      $title = Str::replaceFirst($title, " pt ",    " partial term ");

      //---Noise case ("full term")
      $title = " $title ";
      if ($this->removedFullTermNoise($title, " full term "))  return;
      if ($this->removedFullTermNoise($title, " full  "))      return;

      //---Handle special case of "term end mm/dd/yyyy" without the word "partial".  But it really IS partial term.
      if (Str::contains($title, " term end ")  &&  ! Str::contains($title, " partial")) {
         $title = Str::replaceFirst($title, " term end ", " partial term ending ");
      }
      else if (Str::contains($title, " term ending ")  &&  ! Str::contains($title, " partial")) {  // abstract this and the above?
         $title = Str::replaceFirst($title, " term ending ", " partial term ending ");
      }
      else {
         $title = $this->fixTermEndVariants($title, "term");
         $title = $this->fixTermEndVariants($title, "( ending");
         $title = $this->fixTermEndVariants($title, "ending");
         $title = $this->fixTermEndVariants($title, "expiring");
         $title = $this->fixTermEndVariants($title, "end");
      }

      $title = Str::replaceFirst($title, "(partial)", " partial ");
      $title = Str::replaceFirst($title, " p/t ",     " partial ");
      $this->title = $title;
      if (! Str::contains(" $title ", " partial "))  return;
      $this->isPartial = true;

      $te = new TermExtractor($title);
      $title = $te->title;
      $this->termlen = $te->termlen;

      $words  = Str::splitIntoTokens($title, " ");

      //---Handle special case of bare year at very end, e.g. "Sch Bd Member Partial for Burr Oak Community Schools  2024"
      $lastIndex = count($words) - 1;
      $lastYear  = intval($words[$lastIndex]);
      if ($lastYear >= 2020  &&  $lastYear < 2099) {
         $this->termcycle = $lastYear;
         array_splice($words, $lastIndex, 1);
      }


      $result = [];
      $state  = 'pre';
      foreach ($words as $word) {
         if      ($word  == "partial") { $state = 'partial';  continue; }

         if ($state == 'pre')          { $result[] = trim($word, " ,");   continue; }

         if ($state == 'partial') {
            if ($word == "term"  ||  $word == "ending")        continue;
            if (Str::contains($word, "/")  &&  intval($word) > 0) {
               $this->termcycle = intval(Str::substringAfterLast($word, "/"));
               if ($this->termcycle < 100) $this->termcycle += 2000;
               $state = 'post';
               continue;
            }
            $state = 'post';
         }
         $result[] = trim($word, " ,");
      }

      $this->title = Str::join($result, " ");
   }

}
