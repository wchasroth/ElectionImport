<?php

namespace CharlesRothDotNet\ElectionImport;

class PartyFinder {

   private static array $codesByPartyNames = [
      "DEMOCRATIC"           => "D",
      "GREEN"                => "G",
      "LIBERTARIAN"          => "L",
      "NATURAL LAW"          => "A",
      "NO PARTY AFFILIATION" => "Z",
      "NON PARTISAN"         => "N",
      "NO  AFFILIATION"      => "N",
      "NO AFFILIATION"       => "N",
      "REPUBLICAN"           => "R",
      "U.S. TAXPAYERS"       => "T",
      "WORKING CLASS PARTY"  => "C",
      "WRITE-IN"             => "W",
      "UNKNOWN"              => "U"
   ];

   public static function getPartyCode(string $name): string {
      return self::$codesByPartyNames[strtoupper($name)] ?? "U";
   }

}
