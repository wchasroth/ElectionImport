<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class TermExtractor {
   public int    $termlen = 0;
   public string $title   = "";

//   function __construct(string $text) {
//      $this->title = $text;
//      $text = Str::replaceAll($text, "(", " ");
//      $text = Str::replaceAll($text, ")", " ");
//      $text = Str::replaceAll($text, "-year", " year");
//      foreach ([" 6 yrs ", " 4 yrs ", " 2 yrs ", " 6 yr ", " 4 yr ", " 2 yr ", " 6 year ", " 4 year ", " 2 year "] as $yearTerm) {
//         if (Str::contains($text, $yearTerm)) {
//            $this->termlen = intval(trim($yearTerm));
//            $replace = (Str::contains($text, $yearTerm . "term") ? $yearTerm . "term" : $yearTerm);
//            $this->title = trim(Str::replaceFirst($text, $replace, " "));
//            return;
//         }
//      }
//   }

   function __construct(string $text) {
      $this->title = $text;
      $text = Str::replaceAll($text, "-year", " year");
      foreach (["6 yrs", "4 yrs", "2 yrs", "6 yr", "4 yr", "2 yr", "6 year", "4 year", "2 year", "six year", "four year", "5 year", "3 year"] as $year) {
         foreach ([" $year ", "($year)", " $year term ", "($year term)"] as $yearTerm) {
            if (Str::contains($text, $yearTerm)) {
               $text = Str::replaceFirst($text, "terms", "term");
               $this->termlen = $this->yearNumber(trim(Str::replaceFirst($yearTerm, "(", "")));
               $replace = (Str::contains($text, $yearTerm . "term") ? $yearTerm . "term" : $yearTerm);
               $this->title = trim(Str::replaceFirst($text, $replace, " "));
               return;
            }
         }
      }
   }

   function yearNumber(string $text): int {
      $value = intval($text);
      if ($value > 0)  return $value;
      $word = Str::substringBefore($text, " ");
      switch ($word) {
         case "two":    return 2;
         case "three":  return 3;
         case "four":   return 4;
         case "five":   return 5;
         case "six":    return 6;
      }
      return 0;
   }
}
