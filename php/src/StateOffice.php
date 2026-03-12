<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class StateOffice {
   public string $org;
   public string $district;

   function __construct(string $org, string $district="") {
      $this->org      = $org;
      $this->district = $district;
   }

   public static function make(string $text, string $countyCode='0'): StateOffice {
      $text = strtoupper($text);
      $district = self::getDistrictFrom($text);

      if (Str::contains($text, "ELECTORS OF PRESIDENT"))                return new StateOffice("us");
      if (Str::contains($text, "PRESIDENT OF THE UNITED"))              return new StateOffice("us");
      if (Str::contains($text, "UNITED STATES SENATOR"))                return new StateOffice("us-sen");
      if (Str::contains($text, "REPRESENTATIVE IN CONGRESS"))           return new StateOffice("us-hou", $district);
      if (Str::contains($text, "SECRETARY OF STATE"))                   return new StateOffice("mi-sos");
      if (Str::contains($text, "ATTORNEY GENERAL"))                     return new StateOffice("mi-ag");
      if (Str::contains($text, "STATE SENATOR"))                        return new StateOffice("mi-sen", $district);
      if (Str::contains($text, "REPRESENTATIVE IN STATE LEGISLATURE"))  return new StateOffice("mi-hou", $district);
      if (Str::contains($text, "STATE BOARD OF EDUCATION"))             return new StateOffice("mi-boe");
      if (Str::contains($text, "REGENT OF THE UNIVERSITY OF MICHIGAN")) return new StateOffice("mi-um");
      if (Str::contains($text, "TRUSTEE OF MICHIGAN STATE"))            return new StateOffice("mi-msu");
      if (Str::contains($text, "GOVERNOR OF WAYNE STATE"))              return new StateOffice("mi-wsu");
      if (Str::contains($text, "JUSTICE OF SUPREME COURT"))             return new StateOffice("crt-sup");
      if (Str::contains($text, "COURT OF APPEALS DISTRICT"))   {
         $district = self::numOf(trim(Str::substringBetween($text, "COURT OF APPEALS DISTRICT ", " ")));  // Always a number, FWIW.
         return new StateOffice("crt-a", $district);
      }
      if (Str::contains($text, "GOVERNOR"))                             return new StateOffice("mi");
      if (Str::contains($text, "DISTRICT JUDGE OF COURT OF APPEALS"))   return new StateOffice("crt-a", $district);
      if (Str::contains($text, "CIRCUIT COURT JUDGE"))                  return new StateOffice("crt-c", $district);
      if (Str::contains($text, "JUDGE OF CIRCUIT COURT"))               return new StateOffice("crt-c", $district);
      if (Str::contains($text, "PROBATE COURT"))                        return new StateOffice("crt-p", $countyCode);

      if (Str::contains($text, "PROBATE DISTRICT COURT"))               return new StateOffice("crt-d", $district);  // think this may be wrong!!
      if (Str::contains($text, "JUDGE OF PROBATE COURT"))               return new StateOffice("crt-d", $district);  // think this may be wrong!!

      if (Str::containsAll($text, "DISTRICT COURT", "DIVISION")) {
         // 70TH DISTRICT COURT - FIRST DIVISION JUDGE OF DISTRICT COURT INCUMBENT 6 YEAR TERM (1) POSITION
         $divName = Str::substringBetween($text, "-", "DIVISION");
         $district = $district . "-" . strval(Str::ordinalValue($divName));
         return new StateOffice("crt-d", $district);
      }
      if (Str::contains($text, "JUDGE OF DISTRICT COURT")) {
         $district = Str::substringBefore($text, " ");
         foreach (["ST", "ND", "RD", "TH"] as $suffix) {
            $district = Str::replaceFirst($district, $suffix, " ");
         }
//       fwrite(STDERR, "judge of district court: ($district)  $text\n");
         return new StateOffice("crt-d", $district);
      }

      if (Str::contains($text, "DISTRICT COURT JUDGE")) {
         $tokens = Str::splitIntoTokens($text, " ");
         if ($tokens[2] == "DISTRICT")   $district = $district . $tokens[1];  // Handle "41ST A DISTRICT COURT..." case.
//       fwrite(STDERR, "district cout judge: ($district)  $text\n");
         return new StateOffice("crt-d", $district);
      }

      return new StateOffice("ERROR");
   }

   public static function getDistrictFrom (string $text): string {
      $number = intval($text);
      if ($number == 0)  return "";
      $text = Str::substringBefore("$text ", " ");

      $numlen = strlen(strval($number));
      return (strlen($text) == $numlen+1  ?  $text  :  strval($number));
   }

   private static function numOf(string $text): string {
      return strval((int) $text);
   }

}
