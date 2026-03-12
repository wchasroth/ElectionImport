<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class ImportHelper {

   public static function filterNonPrintingChars(string $text): string {
      $text = preg_replace('/[[:^print:]]/', ' ', $text);
      $text = Str::replaceAll($text, "\m", " ");
      $text = Str::replaceAll($text, "\n", "<p/>");
      return $text;
   }

   public static function calculateTermLength(string $officeName): int {
      $officeName = strtoupper($officeName);
      if (!Str::contains($officeName, "YEAR TERM")) return 0;
      $prefix = Str::substringBefore($officeName, " YEAR TERM");
      $year = substr($prefix, -1);
      return intval($year);
   }

   public static function calculateTermCycle(string $officeName, int $year): int {
      $officeName = strtoupper($officeName);
      if (Str::contains($officeName, "PARTIAL TERM ENDING")) {
         $rest = Str::substringAfter($officeName, "PARTIAL TERM ENDING");
         if (preg_match("/[0-9]{4}/", $rest, $matches)) return intval($matches[0]) - 1;
         return 0;
      }
      $termlen = self::calculateTermLength($officeName);
      return $year + $termlen;
   }
}
