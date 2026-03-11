<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class NameSimplifier {
   private static array $removeSchoolWords = [
      "school", "schools", "district", "community", "public", "area", "consolidated", "system",
      "rural", "agricultural", "local",
      "board", "member", "for", "city", "mamber", /* dear gods, yes */
      "comm"
   ];
   private static array $removeLibraryWords = [
      "area", "district", "library", "board", "member", "for", "mamber"
   ];
   private static array $removeCollegeWords = [
      "board", "of", "trustees", "trustee", "member", "community", "college", "district", "mamber"
   ];
   private static array $removeJurisWords = [
      "city", "of", "township", "twp"
   ];
   private static array $removeVillageWords = [
      "village", "of"
   ];
   private static array $removeComCollegeWords = [
      "community", "college", "county", "district"
   ];

   private static array $schoolSpellingCorrections = [
      "melvindale-north allen park" => "melvindale-northern allen park",
      "au gres-sims"                => "augres-sims",
      "riverside hagar 6"           => "hagar township 6",
      "riverside school, hagar 6"   => "hagar township 6",
      "hagar township"              => "hagar township 6",
      "river sodus 5"               => "sodus township 5",
      "river school, sodus 5"       => "sodus township 5",
      "bangor wood 8"               => "bangor township 8",
      "dowagiac"                    => "dowagiac union",
      "st john"                     => "st johns",
      "saint louis"                 => "st louis",
      "godfrey-lee"                 => "godfrey lee",
//    "tri"                         => "tri county",    // inconsistent, retest
      "tri-county"                  => "tri",
      "thornapple kellogg"          => "thornapple-kellogg",
      "mt pleasant"                 => "mount pleasant",
      "delta bay"                   => "delta",
      "delta midland"               => "delta",
      "delta saginaw"               => "delta",
      "croswell lexington"          => "croswell-lexington",
      "croswell - lexington"        => "croswell-lexington"
   ];

   private static array $jurisSpellingCorrections = [
      "bridgeman"                   => "bridgman",
      "mt pleasant"                 => "mount pleasant",
      "la salle"                    => "lasalle"
   ];

   private static array $collegeSpellingCorrections = [
      "bay de noc"     => "bay",
      "big bay de noc" => "bay",
      "grcc"           => "grand rapids",
      "kvcc"           => "kalamazoo valley",
      "gocc"           => "glen oaks"
   ];

   private static array $villageSpellingCorrections = [
      "merill"    =>  "merrill",
      "ovid-elsi" => "ovid-elsie"
   ];

   public static function simplifySchoolName(string $name): string {
      $result = self::simplifyName($name, self::$removeSchoolWords);
      return self::correctSpelling($result, self::$schoolSpellingCorrections);
   }

   public static function simplifyLibraryName(string $name): string {
      return self::simplifyName($name, self::$removeLibraryWords);
   }

   public static function simplifyJurisdictionName(string $name): string {
      $result = self::simplifyName($name,   self::$removeJurisWords);
      return self::correctSpelling($result, self::$jurisSpellingCorrections);
   }

   public static function simplifyVillageName(string $name): string {
      $result = self::simplifyName($name, self::$removeVillageWords);
      return self::correctSpelling($result, self::$villageSpellingCorrections);
   }

   public static function simplifyCommCollegeName(string $name): string {
      $result = self::simplifyName($name, self::$removeComCollegeWords);
      return self::correctSpelling($result, self::$collegeSpellingCorrections);
   }

   public static function extractNumber(string $name): int {
      for ($i=0;  $i < strlen($name); $i++) {
         if (is_numeric($name[$i]))  return intval($name[$i]);
      }
      return 0;
   }

   public static function removeNumber(string $name): string {
      $words = Str::split(trim($name), " ");
      $result = [];
      foreach ($words as $word) {
         if (intval($word) == 0)  $result[] = $word;
      }
      return Str::join($result, " ");
   }

   private static function simplifyName(string $name, array $removeWords): string {
      $name = strtolower($name);
      $name = Str::replaceAll($name, "no.", " ");  /* To handle 'no.3' */
      $name = Str::replaceAll($name, "'",   "");
      $name = Str::replaceAll($name, ".",   "");
      $name = Str::replaceAll($name, " - ", " ");
      $name = Str::replaceAll($name, "- ", "-");
      $name = Str::replaceAll($name, " -", "-");
      $name = Str::replaceAll($name, "#",   " ");  /* To handle '#3' */
      $name = trim($name);

      $words = Str::splitIntoTokens(strtolower($name), " ");
      $result = [];
      foreach ($words as $word) {
         if (! in_array($word, $removeWords)) $result[] = $word;
      }
      return Str::join($result, " ");
   }

   private static function correctSpelling(string $name, array $corrections): string {
      $corrected = $corrections[$name] ?? "";
      return (! empty($corrected)) ? $corrected : $name;
   }

}
