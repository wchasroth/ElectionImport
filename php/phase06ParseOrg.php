#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$rows = Csv::loadTrimmed(STDIN, "\t");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);
$debug = ($argc == 2) && $argv[1] == "-d";

$header = [
   "year", "county", "region", "voteFor", "name", "party", "votes_C", "votes_D", "votes_R", "votes_O",
   "votes_T", "org", "office", "termlen", "cycle", "partial", "subdist"
];
echo "#" . Str::join($header, "\t") . "\n";

$juris = null;
foreach ($rows as $row) {
   if (Str::startsWith($row[0], "#"))  continue;
   if ($juris == null) {
      $juris = new Jurisdictions();
      $juris->loadFrom($pdo, intval($row[1]));
   }

   //---Fix "QW - " (qualified write-in) names
   if (Str::startsWith ($row[Column::NAME], "QW - ")) {
      $row[Column::NAME] = Str::substringAfter($row[Column::NAME], "QW - ");
      $row[Column::PARTY] = "W";
   }

   if ($debug) fwrite(STDERR, "#" . Str::join($row, "\t") . "\n");
//   $title = $row[Column::REGION];
//   if (Str::contains($title, "{")) {
//      $pctinfo = Str::substringBetween($title, "{", "}");
//      $row[Column::REGION] = Str::replaceFirst($title, "{" . $pctinfo . "}", "");
//   }
   $cadmus = CadmusOrg::makeCadmusFromTitle($row[Column::REGION], $juris, intval($row[Column::YEAR]));
   $row[Column::ORG]       = $cadmus->org;
   $row[Column::OFFICE]    = $cadmus->office;
   $row[Column::REGION]    = $cadmus->region;
   $row[Column::TERMLEN]   = $cadmus->termlen;
   $row[Column::TERMCYCLE] = $cadmus->termcycle;
   $row[Column::PARTIAL]   = $cadmus->partial;
   $row[Column::SUBDIST]   = $cadmus->subdist;

   $output = Str::join($row, "\t") . "\n";
   if (in_array($cadmus->org, ["cnty", "city", "town", "vil"])  &&  empty($cadmus->office)) {
      fwrite(STDERR, "PHASE 6: NO OFFICE: $output");
   }
   echo $output;
}


