#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;

require "vendor/autoload.php";

$rows = Csv::loadTrimmed(STDIN, "\t");

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

$termLenCache = [];

foreach ($rows as $row) {
   #---Comments
   if (Str::startsWith($row[Column::YEAR], "#")) { writeRow($row, "diag", "cache-key");   continue; }

   #---Remove leading party indicator (bizarre!) from candidate name
   $name = strtolower($row[Column::NAME]);
   if (Str::startsWith($name, "dem ", "rep ", "npa ")) {
      $row[Column::NAME] = Str::substringAfter($row[Column::NAME], " ");
   }

   $cacheKey = makeCacheKey($row);

   #---Known term length
   $termlen = intval($row[Column::TERMLEN]);
   if ($termlen > 0)                  {
      $termLenCache[$cacheKey] = $termlen;
      writeRow($row, "known");
      continue;
   }

   #---Check cache
   $termlen = $termLenCache[$cacheKey] ?? 0;
   $row[Column::TERMLEN] = strval($termlen);
   if ($termlen > 0)                  { writeRow($row, "cache", $cacheKey);   continue; }

   #---"Hard" term length from DB for org/office.
   $termlen = getTermLenFromTitleTable($pdo, $row[Column::ORG], $row[Column::OFFICE]);
   if ($termlen > 0) {
      $termLenCache[$cacheKey] = $termlen;
      $row[Column::TERMLEN] = strval($termlen);
      writeRow($row, "db", $cacheKey);
      continue;
   }

   #---County commissioner terms set by law, based on year.
   $year = intval($row[Column::YEAR]);
   if ($row[Column::ORG] == "cnty-com") {
      $termlen = ($year >= 2024 ? 4 : 2);
      $row[Column::TERMLEN] = strval($termlen);
      $termLenCache[$cacheKey] = $termlen;
      writeRow($row, "cnty-com", $cacheKey);
      continue;
   }

   if ($row[Column::ORG] == "schl-cou") {
      $sd = $row[Column::DIST];
      $sql = "SELECT DISTINCT termlen FROM v4schools WHERE id=$sd";
      $result = $pdo->run($sql);
      if ($result->succeeded()  &&  $result->getRowCount() > 0) {
         $termlen = intval($result->getRows()[0]['termlen']);
         $row[Column::TERMLEN] = strval($termlen);
         $termLenCache[$cacheKey] = $termlen;
         writeRow($row, "schl-cou", $cacheKey);
         continue;
      }
      else {
         fwrite(STDERR, "PHASE 8 ERROR: $sql\n");
      }
   }

   #---No data found so far
   writeRow($row, "no-data", $cacheKey);
}

function makeCacheKey(array $row): string {
   return Str::join([$row[Column::REGION], $row[Column::ORG], $row[Column::OFFICE]], ":");
}

function writeRow (array $row, string $diag="", $key=''): void {
   $row[Column::DIAG]  = $diag;
   $row[Column::CACHE] = $key;
   echo Str::join($row, "\t") . "\n";
}

function getTermLenFromTitleTable (AlfredPDO $pdo, string $org, string $office): int {
   $sqlFields = new SqlFields(['org' => $org, 'office' => $office]);
   $result = $pdo->runSF("SELECT termlen FROM v4titles WHERE ", "", $sqlFields);
   if ($result->failed()  ||  $result->getRowcount() == 0)   return 0;
   return intval($result->getRows()[0]['termlen']);
}
