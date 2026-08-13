<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\MichiganCounties;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

define ("ANY_NAME", false);
define ("MUST_MATCH_NAME", true);

class CandidateCompressor {
   private AlfredPDO $pdo;
// private array     $maxSeatsCache;
   private array     $countiesImported = [];
   private MichiganCounties $michiganCounties;

   function __construct(AlfredPDO $pdo) {
      $this->pdo = $pdo;
//      $this->maxSeatsCache = $this->loadMaxSeatsCache($pdo);
      $this->michiganCounties = new MichiganCounties();

      // Cache the isImported value for a county, so we only calculate it once.
      $sql = "SELECT county FROM v4imported";
      $result = $pdo->run($sql);
      foreach ($result->getRows() as $row)  $this->countiesImported[intval($row["county"])] = 1;
   }

   private function getAllOfSingleFieldFrom (string $fieldName, string $sql): array {
      $values = [];
      $result = $this->pdo->run($sql);
      foreach ($result->getRows() as $row)  $values[] = intval($row[$fieldName]);
      return $values;
   }

   function isCountyImported(int $county): bool {
      return array_key_exists($county, $this->countiesImported);
   }

//   function setCompleted (string $type, int $id): void {
//      $sql = "INSERT INTO v4completed (type, id) VALUES ('$type', $id)";
//      $this->pdo->run($sql);
//   }

   function getIdsFor(string $type): array {
      if      ($type === 'county')   $sql = "SELECT DISTINCT id FROM s4counties ";
      else if ($type === 'school')   $sql = "SELECT DISTINCT id FROM s4schools ";
      else if ($type === 'city')     $sql = "SELECT DISTINCT id FROM s4jurisdictions WHERE type='c'  ";
      else if ($type === 'township') $sql = "SELECT DISTINCT id FROM s4jurisdictions WHERE type='t' ";
      else if ($type === 'village')  $sql = "SELECT DISTINCT id FROM s4villages ";
      else if ($type === 'college')  $sql = "SELECT DISTINCT id FROM s4commcolleges ";
      else if ($type === 'state')    $sql = "SELECT 0 AS id ";
      else    throw new \Exception('Not implemented', 501);

      return $this->getAllOfSingleFieldFrom('id', $sql);
   }

   function hasCompleteCountiesFor(string $type, int $id): bool {
      if      ($type === 'school')    $sql = "SELECT DISTINCT county_id FROM s4schools              WHERE id=$id";
      else if ($type === 'city')      $sql = "SELECT DISTINCT county_id FROM s4jurisdictions        WHERE id=$id";
      else if ($type === 'township')  $sql = "SELECT DISTINCT county_id FROM s4jurisdictions        WHERE id=$id";
      else if ($type === 'village')   $sql = "SELECT DISTINCT county_id FROM s4villages             WHERE id=$id";
      else if ($type === 'college')   $sql = "SELECT DISTINCT county_id FROM v4commcolleges_county  WHERE id=$id";
      else  throw new \Exception('Not implemented', 501);

      $counties = $this->getAllOfSingleFieldFrom('county_id', $sql);
      foreach ($counties as $county) {
         if (! $this->isCountyImported($county)) return false;
      }
      return true;
   }

   function markRaceWinners(string $sql): void {
      $result = $this->pdo->run($sql);
      $races  = $result->getRows();

      foreach ($races as $race) {

         $fields = new SqlFields(['org' => $race['org'], 'office' => $race['office'], 'district' => $race['district'], 'subdist' => $race['subdist'],
            'partial' => $race['partial'], 'termlen' => $race['termlen'],
            'cycle' => $race['cycle']]);
         $result = $this->pdo->runSF("SELECT * FROM v4elections WHERE ", "ORDER BY votes_C DESC", $fields);
         if (! $this->found($result))           continue;
         $rows = $result->getRows();
         if (intval($rows[0]['votes_C']) == 0)  continue;   // Nobody wins if # votes = 0.

         $maxVoteFor = 1;  // At least one, no matter what!
         foreach ($rows as $row)  $maxVoteFor = max($maxVoteFor, intval($row['voteFor']));
         $maxVoteFor = min ($maxVoteFor, count($rows));  // sometimes voteFor > number of candidates!

         $winnerIds = [];

         for ($i=0;   $i<$maxVoteFor;   $i++)  $winnerIds[] = $rows[$i]['id'];
         $sql = "UPDATE v4elections SET winner=1 WHERE id in (" . Str::join($winnerIds, ",") . ")";
         $result = $this->pdo->run($sql);
         if ($result->failed()) fwrite(STDERR, "Failed: $sql\n");
      }
   }

   function applyRaceWinnersToCandidates(string $sql, string $year): void {
      $yyyy    = intval($year);
      $result  = $this->pdo->run($sql);
      if ($result->failed()) fwrite(STDERR, "Error: $sql\n");
      $offices = $result->getRows();

      foreach ($offices as $office) {
         $org = $office['org'];

         // For each of the winners for this year for this office:
         $electeds = $this->getMatchingElectedsForOffice($this->pdo, $office);
         foreach ($electeds as $elected) {
            $debug = false;
//          $debug =           ($elected['name'] === 'KYRA HARRIS BOLDEN');
            if ($debug) echo "NAME: " . $elected['name'] . " $year ";
            // General match clause, used in several queries.
            $officeMatchClause
               = "    s.org     ='{$elected['org']}' "
               . "AND s.office  ='{$elected['office']}' "
               . "AND s.district='{$elected['district']}' "
               . "AND s.subdist = {$elected['subdist']} ";

            $partial = intval($elected['partial']);
            $isFullTerm = $partial == 0;
            $electedCycle = intval($elected['cycle']);

            //---Case 1: does this elected match an existing candidate by seat AND by name? Update termlen if old=0 and new>0
            //---Case 2: is there a candidate row that matches by seat, but has an EMPTY name?  Update name, termlen if old=0 and new>0
            //---Case 3: is there a v4seats rows that matches by office, but has no candidate row at all?  (Create a candidate row, put all data there)
            //---Case 4: create a new v4seats row.  Create a candidate row, put all data there.
            $sql = "SELECT c.id, c.seat_id, c.name, c.seat_id, s.termlen "
               . "  FROM      v4candidates AS c "
               . "  LEFT JOIN v4seats      AS s ON (s.id = c.seat_id) "
               . " WHERE $officeMatchClause "
               . "   AND (s.termcycle = $yyyy  OR  s.is_open = 1)";
            $match = $this->pdo->run($sql);
            if ($match->failed()) {
               fwrite(STDERR, "Case 1 error " . $match->getError() . " " . $match->getRawSql() . "\n");
               continue;
            }

            //---Case 4: no v4seats row at all (for partial-term election)
            $matchRows = $match->getRows();
            if (count($matchRows) == 0  &&  $elected['partial'] == 1) {
               $this->reportCase("Case 5: no partial term seat", $elected);
               continue;
            }

            //---Case 5: no v4seats at all (regular election)
            if (count($matchRows) == 0) {
               $this->reportCase("Case 4: no regular seat", $elected);
               continue;
            }

            //---Case 1: Does this elected match an existing candidate by seat AND by name? Update termlen if old=0 and new>0
            $bestIndex = $this->getBestMatchingRowIndex($elected, $matchRows, MUST_MATCH_NAME);
            if ($bestIndex >= 0) {
               $row = $matchRows[$bestIndex];
//             echo "Case 1 match: {$row['name']}  $officeMatchClause\n";
               $this->updateCandidateName($row['id'],      $elected['name']);
               $this->updateSeatTermlen  ($row['seat_id'], $elected['termlen']);
               $this->markElectedRowAsImported($elected['id']);
               $this->reportCase("Case 1: success", $elected);
               continue;
            }

            //---Case 2: find empty name row
            $emptyIndex = $this->findRowWithName($matchRows, "");
            if ($emptyIndex > -1) {
//             echo "Case 2: empty name match for {$elected['name']}, $officeMatchClause\n";
               $row = $matchRows[0];
               $this->updateCandidateName($row['id'],      $elected['name']);
               $this->updateSeatTermlen  ($row['seat_id'], $elected['termlen']);
               $this->markElectedRowAsImported($elected['id']);
               $this->reportCase("Case 2: success (empty slot)", $elected);
               continue;
            }

            //---Case 3: v4seats row, but no candidate row at all.
            $nullIndex = $this->findRowWithName($matchRows, null);
            if ($nullIndex > -1) {
               $this->reportCase("Case 3: no candidates row", $elected);
               continue;
            }

//          echo "ERROR: should be impossible case: $officeMatchClause {$elected['name']}\n";
            $this->reportCase("ERROR: impossible ($officeMatchClause)", $elected);
         }
      }
   }

   function reportCase(string $text, array $elected) {
      $countyName = ucwords($this->michiganCounties->getName($elected['county']));
      $juris  = "";
      $office = $elected['office'];
      $org = $elected['org'];
      if      ($org === 'cnty-com')    $office = "commissioner";
      else if ($org === 'city')        $juris  = $this->getJurisName($elected['district']);
      else if ($org === 'city-cou')  { $juris  = $this->getJurisName($elected['district']);  $office = "council"; }
      else if ($org === 'town')        $juris  = $this->getJurisName($elected['district']);
      else if ($org === 'town-cou')    $juris  = $this->getJurisName($elected['district']);

      echo "$countyName County, $juris, $office, $text, {$elected['name']}\n";
   }

   function getJurisName (string $district): string {
      $sql = "SELECT name FROM s4jurisdictions WHERE id='$district'";
      return $this->pdo->run($sql)->getSingleValue('name');
   }

   function updateCandidateName(int $id, string $name): void {
      $sqlFields = new SqlFields(['name' => $name]);
      $sqlFields->getUpdateFragment();
      $sql = "UPDATE v4candidates SET " . $sqlFields->getUpdateFragment() . " WHERE id = $id";
      $this->runQuery($sql);
   }

   function updateSeatTermlen (int $id, int $termlen): void {
      if ($termlen > 0) $this->runQuery ("UPDATE v4seats SET termlen=$termlen WHERE id = $id AND termlen = 0");
   }

   function runQuery(string $sql): void {
//    echo "   About to run: $sql\n";
      $result = $this->pdo->run($sql);
      if ($result->failed()) fwrite(STDERR, "Query failed: $sql\n");
   }

   private function markElectedRowAsImported(int $id): void {
      $sql = "UPDATE v4elections SET imported=1 WHERE id=$id";
      $this->pdo->run($sql);
   }

   function findRowWithName (array $rows, $nameValue): int {
      for ($i=0;   $i<count($rows); $i++) {
         if ($rows[$i]['name'] === $nameValue) return $i;
      }
      return -1;
   }

   function getBestMatchingRowIndex(array $elected, array $rows, bool $nameMustMatch=false): int {
      $rowCount = count($rows);
      if (! $nameMustMatch  &&  $rowCount === 1)  return 0;

      $electedName = new MatchableName($elected['name']);
      $incumbentNames = [];
      for ($i=0;   $i<$rowCount;   $i++) {
         $row = $rows[$i];
         $incumbentNames[] = new MatchableName($row['name']);
      }

      $bestIndex    = $electedName->findBestMatch($incumbentNames, 2);
      $hadNameMatch = ($bestIndex >= 0);
      if ($hadNameMatch) {
         if ($incumbentNames[$bestIndex]->getSimplifiedName() != $electedName->getSimplifiedName()) {
            echo "Replacing " . $incumbentNames[$bestIndex]->getSimplifiedName() . " with " . $electedName->getSimplifiedName() . "\n";
         }
      }

      if (! $hadNameMatch  &&  $nameMustMatch)  return -1;

      return max($bestIndex, 0);
   }

//   private function getCurrentMaxSeats (AlfredPDO $pdo, string $officeMatchClause): int {
//      $sql = "SELECT MAX(s.seatnum) AS maxcurrent FROM v4seats AS s WHERE $officeMatchClause ";
//      $result = $pdo->run($sql);
//      if (! $this->found($result))  return 0;
//
//      $row = $result->getRows()[0];
//      return intval($row['maxcurrent']);
//   }

   private function found($match): bool {
      return ($match->succeeded() && $match->getRowCount() > 0);
   }

   private function getMatchingElectedsForOffice(AlfredPDO $pdo, array $office): array {
      $fields = new SqlFields(['org' => $office['org'], 'office' => $office['office'], 'district' => $office['district'],
         'subdist' => $office['subdist'], 'winner' => 1, 'imported' => 0]);
      $result = $pdo->runSF("SELECT * FROM v4elections WHERE ", "", $fields);
      $rows = $result->getRows();
      return $rows;
   }

//   private function makeIncumbentFields(array $elected, string $year): array {
//      $fields = ['name' => $elected['name'], 'elected' => $year, 'party' => $elected['party'],
//         'votes_C' => $elected['votes_C'], 'votes_D' => $elected['votes_D'], 'votes_R' => $elected['votes_R'],
//         'votes_O' => $elected['votes_O'], 'votes_T' => $elected['votes_T'],
//         'num2elect' => $elected['voteFor'], 'county' => $elected['county'], 'partial' => $elected['partial']
//      ];
//      return $fields;
//   }

//   private function replaceIncumbentWithMatch(AlfredPDO $pdo, int $id, array $elected, string $year, bool $debug=false): void {
//      $newName = strtolower($elected['name']);
//      $updateFields = new SqlFields($this->makeIncumbentFields($elected, $year));
//
//      $oldNameResult = $pdo->run("SELECT name FROM v4incumbents WHERE id=$id");
//      if ($this->found($oldNameResult)) {
//         echo "SUBSTITUTE: $newName REPLACES " . strtolower($oldNameResult->getRows()[0]['name']) . "\n";
//      }
//
//      $sql = "UPDATE v4incumbents SET " . $updateFields->getSetFragment() . " WHERE id=$id";
//      $result = $pdo->run($sql);
//      if ($result->failed()) echo "BAD: $sql\n";
//      if ($debug) echo "DEBUG: $sql\n";
//   }

//   private function loadMaxSeatsCache(AlfredPDO $pdo): array {
//      $sql = "SELECT org, office, seats FROM s4titles WHERE seats > 0";
//      $result = $pdo->run($sql);
//      $rows = $result->getRows();
//      $cache = [];
//      foreach ($rows as $row) {
//         $cache[$row['org'] . "-" . $row['office']] = intval($row['seats']);
//      }
//      return $cache;
//   }

//   private function simplifyDistrict(string $district): string {
//      if (! Str::startsWith($district, '0'))  return $district;
//      return Str::substringAfter ($district, '0');
//   }
}