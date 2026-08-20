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

//---phase81ImportWashtenaw.php
//
//   Scan the CSV sent by Liz Salley with the full list of candidates endorsed by the WCDP.
//   Scan thru stdin (output from phase07Districts.php).  Attempt to match each office and candidate
//   name with the entries in BOTH v4candidates and v4filings.
//
//   Report cases that match, and the respective table and id.
//   Report cases that DO NOT MATCH.  (We may want to eventually import them somewhere.)

if ($argc < 2) {
   fwrite (STDERR, "Useage: php phase82importWashtenaw.php filename\n");
   exit(1);
}
fwrite (STDERR, "filename=" . $argv[1] . "\n");

$csv = new CsvFile();
if (! $csv->loadfile($argv[1], false)) $csv->exitWithError();
$rowCount = $csv->getRowCount();

$env = new EnvFile("_env");
$pdo = PdoHelper::makePdo($env);

for ($i=1;  $i<$rowCount;  $i++) {
   $row = $csv->getRow($i);
   if (empty($row['org']))  continue;

   $fields = ['s.org' => $row['org'], 's.office' => $row['office'], 's.district' => $row['district'], 's.subdist' => $row['subdist'],
      'is_open' => intval($row['partial'])];
   $sqlFields = new SqlFields($fields);
   $candidateRows = getCandidates($pdo, $sqlFields);

#  echo "For " . Str::join($fields, ",") . "   :";

   //---Look for any existing v4candidate rows with a matching name.
   $namedRows = [];
   foreach ($candidateRows as $candidateRow)  if (!empty($candidateRow['name']))  $namedRows[] = $candidateRow;
   $possibles = prepareMatchables($namedRows);
   $candidateName = new MatchableName($row['name']);
   $bestIndex = $candidateName->findBestMatch($possibles, 2);
   if ($bestIndex >= 0) {
      $id = $namedRows[$bestIndex]['id'];
#     echo "found match for {$row['name']} as $id: " . $namedRows[$bestIndex]['name'] . "\n";
      updateField($pdo, $id, 'email', $row['email']);
      updateField($pdo, $id, 'web',   $row['web']);
      updateField($pdo, $id, 'phone', $row['phone']);
      updateField($pdo, $id, 'description', $row['description']);
      writeEndorsed($pdo, $id);
      continue;
   }

   $emptyRows = [];
   foreach ($candidateRows as $candidateRow)  if ( empty($candidateRow['name']))  $emptyRows[] = $candidateRow;
   if (count($emptyRows) == 0) {
      echo "Impossible? " . Str::join($fields, ",") . "\n";
      continue;
   }

   $id = $emptyRows[0]['id'];
   if (empty($id)) {
      $seatId = $emptyRows[0]['sid'];
      $sql = "INSERT INTO v4candidates (seat_id) VALUES ($seatId)";
      $result = $pdo->run($sql);
      if ($result->failed()) fwrite(STDERR, "Error: $sql\n");
      $id = $result->getInsertId();
   }
   updateField($pdo, $id, 'name',  $row['name']);
   updateField($pdo, $id, 'email', $row['email']);
   updateField($pdo, $id, 'web',   $row['web']);
   updateField($pdo, $id, 'phone', $row['phone']);
   updateField($pdo, $id, 'party', 'D');
   updateField($pdo, $id, 'description', $row['description']);
   writeEndorsed ($pdo, $id);
#  echo "found empty slot {$emptyRows[0]['id']} for {$row['name']}\n";
}

function runQuery(AlfredPDO $pdo, string $sql): void {
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite(STDERR, "Error: $sql\n");
   echo "$sql\n";
}

function updateField (AlfredPDO $pdo, int $id, string $field, string $value): void {
   $sqlFields = new SqlFields([$field => $value]);
   $sql = "UPDATE v4candidates SET " . $sqlFields->getSetFragment() . " WHERE id = $id AND reviewed=0 ";
#  echo "   $sql\n";
   runQuery($pdo, $sql);
}

function writeEndorsed (AlfredPDO $pdo, int $id): void {
   $sql = "UPDATE v4candidates SET endorsed=1 WHERE id=$id";
   runQuery($pdo, $sql);
}

// email, web, phone, description
// reviewed, endorsed

function prepareMatchables(array $possibles): array {
   $matches = [];
   for ($i=0;   $i<count($possibles);   $i++) $matches[$i] = new MatchableName($possibles[$i]['name']);
   return $matches;
}

function getCandidates(AlfredPDO $pdo, SqlFields $sqlFields): array {
   $sql = "SELECT s.id as sid, c.* "
        . "  FROM v4seats AS s "
        . "  LEFT JOIN v4candidates AS c  ON (c.seat_id = s.id) "
        . " WHERE " . $sqlFields->getSelectFragment()
        . "   AND (s.termcycle=2026 OR s.is_open=1)";
   $result = $pdo->run($sql);
   return $result->getRows();
}