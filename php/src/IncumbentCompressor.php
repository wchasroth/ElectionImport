<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

define ("ANY_NAME", false);
define ("MUST_MATCH_NAME", true);

class IncumbentCompressor {
   private AlfredPDO $pdo;
   private array     $maxSeatsCache;
   private array     $isCountyImported = [];

   function __construct(AlfredPDO $pdo) {
      $this->pdo = $pdo;
      $this->maxSeatsCache = $this->loadMaxSeatsCache($pdo);

      // Cache the isImported value for a county, so we only calculate it once.
      $sql = "SELECT county FROM v4imported";
      $result = $pdo->run($sql);
      foreach ($result->getRows() as $row)  $isCountyImported[intval($row["county"])] = 1;
      var_dump($isCountyImported);
   }

   private function getAllOfSingleFieldFrom (string $fieldName, string $sql): array {
      $values = [];
      $result = $this->pdo->run($sql);
      foreach ($result->getRows() as $row)  $values[] = intval($row[$fieldName]);
      return $values;
   }

   function isCountyImported(int $county): bool {
      return array_key_exists($county, $this->isCountyImported);
   }

   function isCompleted(string $type, int $district): bool {
      $sql = "SELECT 1 FROM v4completed WHERE type='$type' AND district=$district LIMIT 1";
      $result = $this->pdo->run($sql);
      return $result->getRowCount() > 0;
   }

   function setCompleted (string $type, int $district): void {
      $sql = "INSERT INTO v4completed (type, district) VALUES ('$type', $district)";
      $this->pdo->run($sql);
   }

   function getElectionDates(): array {
      return ['2018-11-06', '2020-11-03', '2021-11-02', '2022-11-08', '2023-11-07', '2024-11-05', '2025-11-04'];
   }

   function getUncompletedIdsFor(string $type): array {
      if      ($type == 'county')  $sql = "SELECT DISTINCT id FROM v4counties WHERE id NOT IN";
      else if ($type == 'school')  $sql = "SELECT DISTINCT id FROM v4schools  WHERE id NOT IN ";
      else    throw new \Exception('Not implemented', 501);

      $sql = $sql . "   (SELECT district FROM v4completed WHERE type='$type')";
      return $this->getAllOfSingleFieldFrom('id', $sql);
   }

   function hasCompleteCountiesFor(string $type, int $id): bool {
      if ($type != 'school')  throw new \Exception('Not implemented', 501);
      $sql = "SELECT DISTINCT county_id FROM v4schools WHERE id=$id";
      $counties = $this->getAllOfSingleFieldFrom('id', $sql);
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
            'partial' => $race['partial'], 'year' => $race['year'], 'termlen' => $race['termlen'], 'incumbent' => $race['incumbent'],
            'cycle' => $race['cycle']]);
         $result = $this->pdo->runSF("SELECT * FROM v4elections WHERE ", "ORDER BY votes_C DESC", $fields);
         $rows = $result->getRows();

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

   function applyRaceWinnersToIncumbents(string $sql, string $year): void {
      $yyyy    = intval($year);
      $result  = $this->pdo->run($sql);
      $offices = $result->getRows();

      foreach ($offices as $office) {
         $org = $office['org'];

         // Find all winners for this year for this office. For each winner:
         $electeds = $this->getMatchingElectedsForOffice($this->pdo, $office, $year);
         foreach ($electeds as $elected) {
            $debug = false;
//          $debug =           ($elected['name'] === 'KYRA HARRIS BOLDEN');
            if ($debug) echo "NAME: " . $elected['name'] . " $year ";
            // General match clause, used in several queries.
            $officeMatchClause = " s.org ='{$elected['org']}' "
               . "AND s.office  ='{$elected['office']}' "
               . "AND s.district='{$elected['district']}' "
               . "AND s.subdist = {$elected['subdist']} ";

            $partial = intval($elected['partial']);
            $isFullTerm = $partial == 0;
            $electedCycle = intval($elected['cycle']);

            //---Case A2: Does this elected match an incumbent (by seat) AND by name,
            //      AND where the incumbent's seat has termcycle = 0?
            //      (Typically from a case where there was a partial term election earlier, with no end year.)
            //      If so, replace that incumbent's data (best match by name, if possible) with the newly elected.
            $sql = "SELECT i.id, i.name, i.seat_id "
               . "  FROM v4incumbents AS i "
               . "  LEFT JOIN v4seats AS s ON (s.id = i.seat_id) "
               . " WHERE $officeMatchClause "
               . "   AND $yyyy > SUBSTRING(i.elected, 1, 4) "
               . "   AND s.termcycle = 0 ";
            $match = $this->pdo->run($sql);
            if ($match->failed()) {
               fwrite(STDERR, "Case A2 error " . $match->getError() . " " . $match->getRawSql() . "\n");
               continue;
            }
            if ($this->found($match)) {
               $bestIndex = $this->getBestMatchingRowIndex($elected, $match->getRows(), MUST_MATCH_NAME);
               if ($bestIndex >= 0) {
                  $row = $match->getRows()[$bestIndex];
                  echo "Case A2: $officeMatchClause\n";
                  $id = intval($row['id']);
                  $this->replaceIncumbentWithMatch($this->pdo, $id, $elected, $year, $debug);
                  $newCycle = intval($elected['cycle']);
                  if ($newCycle > 0) {
                     $seatId = intval($row['seat_id']);
                     $this->pdo->run("UPDATE v4seats SET termcycle=$newCycle WHERE id=$seatId");
                  }
                  continue;
               }
            }


            //---Case A1: Full term election.  Does this elected match an incumbent (by seat), where the termcycle shows open for election?
            //      I.e. current year > incumbent elected, AND term cycles match (year - termcycle % termlen == 0) ?
            //      If so, replace that incumbent's data (best match by name, if possible) with the newly elected.
            if ($isFullTerm) {
               $sql = "SELECT i.id, i.name "
                  . "  FROM v4incumbents AS i "
                  . "  LEFT JOIN v4seats AS s  ON (i.seat_id = s.id) "
                  . " WHERE $officeMatchClause "
                  . "   AND s.termcycle != 0 "
                  . "   AND $yyyy > SUBSTRING(i.elected, 1, 4) "
                  . "   AND MOD($yyyy - s.termcycle, s.termlen) = 0 ";
               $match = $this->pdo->run($sql);
               if ($match->failed()) {
                  fwrite(STDERR, "Case A1 error " . $match->getError() . " " . $match->getRawSql() . "\n");
                  continue;
               }
               if ($this->found($match)) {
                  $rows = $match->getRows();
                  $bestIndex = $this->getBestMatchingRowIndex($elected, $rows, ANY_NAME);
                  $id = intval($rows[$bestIndex]['id']);
                  echo "Case A1: $officeMatchClause\n";
                  $this->replaceIncumbentWithMatch($this->pdo, $id, $elected, $year, $debug);
                  continue;
               }
            }

            //---Case A3: Partial term. Does this elected match an incumbent (by seat), where the
            //      incumbent termcycle matches the elected's expected termcycle?
            //      If so, replace that incumbent's data with the newly elected.
            //
            //      Note: if there's more than one match, we can't tell WHICH one -- so fail, and go on.
            //      Mark the matches as possibly resigned (i.resigned='R'), which will cause some resigned incumbents to
            //      still appear as elected... but that's easier to detect and fix.
            else if ($electedCycle > 0) {
               $sql = "SELECT i.id, i.name "
                  . "  FROM v4incumbents AS i "
                  . "  LEFT JOIN v4seats AS s  ON (i.seat_id = s.id) "
                  . " WHERE $officeMatchClause "
                  . "   AND s.termcycle != 0 "
                  . "   AND $yyyy > SUBSTRING(i.elected, 1, 4) "
                  . "   AND MOD(s.termcycle, s.termlen) = MOD($electedCycle, s.termlen) ";
               $match = $this->pdo->run($sql);
               if ($match->failed()) {
                  fwrite(STDERR, "Case A1 error " . $match->getError() . " " . $match->getRawSql() . "\n");
                  continue;
               }
               if ($this->found($match)) {
                  $rows = $match->getRows();
                  if ($match->getRowCount() > 1) {
                     foreach ($rows as $row) $this->pdo->run("UPDATE v4incumbents SET resigned='R' WHERE id={$row['id']}");  // Flag that incumbent MAY have resigned.  MAY!
                  } else {
                     $id = intval($rows[0]['id']);
                     echo "Case A3: $officeMatchClause\n";
                     $this->replaceIncumbentWithMatch($this->pdo, $id, $elected, $year, $debug);
                     continue;
                  }
               }
            }

            //---Case B: If not, does the elected match an existing incumbent, where there can only be ONE seat?
            //      If the elected is not partial, OR the incumbent is from a previous year -- replace it with elected.
            //      Otherwise, skip this elected.  (Might be a partial term to fill out current year, where there's
            //      already one for the new term.)
            $sql = "SELECT i.id, i.elected"
               . "  FROM v4incumbents AS i "
               . "  LEFT JOIN v4seats AS s  ON (i.seat_id = s.id) "
               . "  LEFT JOIN v4title AS t  ON (t.org = s.org  AND  t.office = s.office) "
               . " WHERE $officeMatchClause "
               . "   AND t.seats = 1 "
               . " LIMIT 1 ";
            $incumbentResult = $this->pdo->run($sql);
            if ($this->found($incumbentResult)) {
               $incumbent = $incumbentResult->getRows()[0];
               if ($isFullTerm || intval($incumbent['elected']) < $year) {
                  $id = intval($incumbent['id']);
                  $this->replaceIncumbentWithMatch($this->pdo, $id, $elected, $year);
                  echo "Case B: $officeMatchClause\n";
               }
               continue;
            }

            //---Case C: If not, does elected match an existing v4seats row that does NOT have an incumbent?
            //      Add a new incumbent row, pointing at that v4seats id.
            $sql = "SELECT s.id "
               . "  FROM v4seats AS s "
               . " WHERE $officeMatchClause "
               . "   AND (SELECT COUNT(*) FROM v4incumbents WHERE seat_id = s.id) = 0 "
               . "   AND MOD(s.termcycle, s.termlen) = MOD($electedCycle, s.termlen) "
               . " LIMIT 1 ";
            $match = $this->pdo->run($sql);
            if ($this->found($match)) {
               $insertFields = $this->makeIncumbentFields($elected, $year);
               $insertFields['seat_id'] = intval($match->getRows()[0]['id']);
               $result = $this->pdo->runSF("INSERT INTO v4incumbents", "", new SqlFields($insertFields), true);
               echo "Case C: $officeMatchClause\n";
               continue;
            }

            //---Case D: If not, have we reached the max # of seats (if there is one, per v4title)?  Error message!
            // (NOT YET: Get the seatmax value for v4seats org/office/district/subdist)
            $maxSeats = $this->maxSeatsCache[$elected['org'] . "-" . $elected['office']] ?? 0;
            $currentSeats = $this->getCurrentMaxSeats($this->pdo, $officeMatchClause);
            if ($maxSeats > 0 && $currentSeats >= $maxSeats) {
               fwrite(STDERR, "Too many seats? $officeMatchClause\n");
               echo "Case D: too many\n";
               continue;
            }

            //---Case E: Finally!  Create a new v4seats row.  Add a new incumbent row, pointing at it.
            //     If there's an inherent seatmax (from v4title), supply that as well.
            $seatsFields = [
               'org' => $elected['org'], 'office' => $elected['office'], 'district' => $elected['district'], 'subdist' => $elected['subdist'],
               'seatnum' => $currentSeats + 1, 'termlen' => $elected['termlen'], 'termcycle' => $elected['cycle'],
               'seatmax' => $maxSeats
            ];
            $result = $this->pdo->runSF("INSERT INTO v4seats", "", new SqlFields($seatsFields), true);
            if ($result->failed()) {
               fwrite(STDERR, "Insert failed v4seats: " . $result->getError() . " " . Str::join($seatsFields, ', ') . "\n");
               echo "Case E fail\n";
               continue;
            }
            $id = $result->getInsertId();
            $insertFields = $this->makeIncumbentFields($elected, $year);
            $insertFields['seat_id'] = $id;
            $result = $this->pdo->runSF("INSERT INTO v4incumbents", "", new SqlFields($insertFields), true);

            // For community colleges, we also have to add the mapping between college and county
            // (since one college may cover multiple counties).
            if ($org == 'comcol-cou') {
               $sql = "INSERT INTO v4commcolleges (comm_college_id, county_id) VALUES "
                  . "   ({$elected['district']}, {$elected['county']})";
               $this->pdo->run($sql);
            }
            echo "Case E: $officeMatchClause\n";
         }
      }
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

   private function getCurrentMaxSeats (AlfredPDO $pdo, string $officeMatchClause): int {
      $sql = "SELECT MAX(s.seatnum) AS maxcurrent FROM v4seats AS s WHERE $officeMatchClause ";
      $result = $pdo->run($sql);
      if (! $this->found($result))  return 0;

      $row = $result->getRows()[0];
      return intval($row['maxcurrent']);
   }

   private function found($match): bool {
      return ($match->succeeded() && $match->getRowCount() > 0);
   }

   private function getMatchingElectedsForOffice(AlfredPDO $pdo, array $office, string $year): array {
      $fields = new SqlFields(['org' => $office['org'], 'office' => $office['office'], 'district' => $office['district'],
         'subdist' => $office['subdist'], 'year' => $year, 'winner' => 1]);
      $result = $pdo->runSF("SELECT * FROM v4elections WHERE ", "", $fields);
      $rows = $result->getRows();
      return $rows;
   }

   private function makeIncumbentFields(array $elected, string $year): array {
      $fields = ['name' => $elected['name'], 'elected' => $year, 'party' => $elected['party'],
         'votes_C' => $elected['votes_C'], 'votes_D' => $elected['votes_D'], 'votes_R' => $elected['votes_R'],
         'votes_O' => $elected['votes_O'], 'votes_T' => $elected['votes_T'],
         'num2elect' => $elected['voteFor'], 'county' => $elected['county'], 'partial' => $elected['partial']
      ];
      return $fields;
   }

   private function replaceIncumbentWithMatch(AlfredPDO $pdo, int $id, array $elected, string $year, bool $debug=false): void {
      $newName = strtolower($elected['name']);
      $updateFields = new SqlFields($this->makeIncumbentFields($elected, $year));

      $oldNameResult = $pdo->run("SELECT name FROM v4incumbents WHERE id=$id");
      if ($this->found($oldNameResult)) {
         echo "SUBSTITUTE: $newName REPLACES " . strtolower($oldNameResult->getRows()[0]['name']) . "\n";
      }

      $sql = "UPDATE v4incumbents SET " . $updateFields->getSetFragment() . " WHERE id=$id";
      $result = $pdo->run($sql);
      if ($result->failed()) echo "BAD: $sql\n";
      if ($debug) echo "DEBUG: $sql\n";
   }

   private function loadMaxSeatsCache(AlfredPDO $pdo): array {
      $sql = "SELECT org, office, seats FROM v4title WHERE seats > 0";
      $result = $pdo->run($sql);
      $rows = $result->getRows();
      $cache = [];
      foreach ($rows as $row) {
         $cache[$row['org'] . "-" . $row['office']] = intval($row['seats']);
      }
      return $cache;
   }
}