#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$rows = Csv::loadTrimmed(STDIN, "\t");
$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$rowNum = 1;
foreach ($rows as $row) {
   if (Str::startsWith($row[Column::YEAR], "#"))  continue;
   if (empty(trim($row[Column::NAME])))           continue;

   $sqlFields = new SqlFields([
      "year"    => $row[Column::YEAR],
      "county"  => $row[Column::COUNTY],
      "region"  => $row[Column::REGION],
      "voteFor" => $row[Column::VOTEFOR],
      "name"    => $row[Column::NAME],
      "party"   => $row[Column::PARTY],
      "votes_C" => $row[Column::VOTES_C],
      "votes_D" => $row[Column::VOTES_D],
      "votes_R" => $row[Column::VOTES_R],
      "votes_O" => $row[Column::VOTES_O],
      "votes_T" => $row[Column::VOTES_T],
      "org"     => $row[Column::ORG],
      "office"  => $row[Column::OFFICE],
      "termlen" => $row[Column::TERMLEN],
      "cycle"   => $row[Column::TERMCYCLE],
      "partial" => intval($row[Column::PARTIAL]),
      "subdist" => intval($row[Column::SUBDIST]) % 100,  // Because Roscommon county district #s = county# * 100 + district #!
      "district" => $row[Column::DIST],
      "incumbent" => $row[Column::INCUMBENT]
   ]);

   $result = $pdo->runSF("INSERT INTO v4elections", "", $sqlFields, true);
   if ($result->failed()) {
      fwrite(STDERR, $rowNum . " " . $result->getError() . "  " . $result->getRawSql() . "\n");
   }
}
