#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\CsvFile;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\AlfredPDO;

require "vendor/autoload.php";

//---phase21aiMissing.php
//
//   Jon O keeps giving us different data formats, so we need a new importer each time (sigh).
//   This one scans thru a TSV file, and tries to match each row with an existing row in v4filings.
//   The matching has to use MatchableNames, since there name variations like "John Smith" vs "Smith, John".
//
//   If it succeeds, update any empty fields in v4filings that have data in the TSV.
//
//   If it fails, insert a NEW row in v4filings with whatever data we have in the TSV.

if ($argc < 2) {
   fwrite (STDERR, "Useage: php phase21aiMissing.php filename\n");
   exit(1);
}

$csv = new CsvFile();
if (! $csv->loadfile($argv[1], false)) $csv->exitWithError();
$rowCount = $csv->getRowCount();

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);
$fieldsUpdated = 0;

$sql = "select max(id) AS maxId from v4filings where id like '10%' and length(id) = 7";
$newId = $pdo->run($sql)->getSingleValue('maxId');
if (empty($newId)) $newId = 1000000;

for ($i=1;  $i<$rowCount;  $i++) {
   $row = $csv->getRow($i);
   if (empty($row['org']))  continue;

   $fields = ['org' => $row['org'], 'office' => $row['office'], 'district' => $row['district'], 'subdist' => intval($row['subdist'])];
   $sqlFields = new SqlFields($fields);
   $filingRows = getFilings($pdo, $sqlFields);

   $possibles   = prepareMatchables($filingRows);
   $nameToMatch = new MatchableName($row['name']);
   $bestIndex = $nameToMatch->findBestMatch($possibles, 2);
   if ($bestIndex >= 0) {
      $id = $filingRows[$bestIndex]['id'];
      echo "found match for {$row['name']} as $id: " . $filingRows[$bestIndex]['name'] . "\n";
      $fieldsUpdated += updateField($pdo, $id, 'email', $row['email']);
      $fieldsUpdated += updateField($pdo, $id, 'web',   $row['web']);
      $fieldsUpdated += updateField($pdo, $id, 'phone', $row['phone']);
      $fieldsUpdated += updateField($pdo, $id, 'description', $row['description']);
      $fieldsUpdated += updateField($pdo, $id, 'headshot_url', $row['headshot_url']);
      if (intval($row['partialterm']) === 1)  {
         runQuery ($pdo, "UPDATE v4filings SET partialterm = 1 WHERE id = '$id'");
         $fieldsUpdated++;
      }
   }
   else {
      ++$newId;
      $fields['id'] = strval($newId);
      $fields['name'] = $row['name'];
      $fields['email'] = $row['email'];
      $fields['web'] = $row['web'];
      $fields['phone'] = $row['phone'];
      $fields['description'] = $row['description'];
      $fields['partialterm'] = intval($row['partialterm']);
      $fields['headshot_url'] = $row['headshot_url'];
      $sqlFields = new SqlFields($fields);
      $sql = "INSERT INTO v4filings " . $sqlFields->getInsertFragment();
      runQuery($pdo, $sql);
      echo "made new record $newId for {$row['name']}\n";
   }
}
echo "fieldsUpdated = $fieldsUpdated\n";

function runQuery(AlfredPDO $pdo, string $sql): void {
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite(STDERR, "Error: $sql\n");
}

function updateField (AlfredPDO $pdo, string $id, string $field, string $value): int {
   if (empty(trim($value))) return 0;
   $sqlFields = new SqlFields([$field => $value]);
   $sql = "UPDATE v4filings SET " . $sqlFields->getSetFragment() . " WHERE id = '$id'  ";
#  echo "   $sql\n";
   runQuery($pdo, $sql);
   return 1;
}

function prepareMatchables(array $possibles): array {
   $matches = [];
   for ($i=0;   $i<count($possibles);   $i++) $matches[$i] = new MatchableName($possibles[$i]['name']);
   return $matches;
}

function getFilings(AlfredPDO $pdo, SqlFields $sqlFields): array {
   $sql = "SELECT * FROM v4filings "
        . " WHERE " . $sqlFields->getSelectFragment();
   $result = $pdo->run($sql);
   return $result->getRows();
}