<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\MatchableName;

// Purpose: some offices cross county boundaries (e.g. school boards, community college trustees,
// even a few cities.  So we need to merge the 'elections' table rows, for the same candidate,
// across multiple counties, into one row.
//
//
class CandidateRowCombiner {
   private array $results;
   private array $byCounty;
   private array $ids;

   public function __construct() {
      $this->results  = [];
      $this->byCounty = [];
      $this->ids      = [];
   }

   public function addRow (array $electionsRow): void {
      $county = intval($electionsRow["county"]);
      if (! isset($byCounty[$county]))   $byCounty[$county] = [];
      $this->byCounty[$county][] = $electionsRow;
   }

   public function getOtherCounties(int $county): array {
      $allCounties = array_keys($this->byCounty);
      return array_values(array_diff($allCounties, [$county]));
   }

   public function getResolvedRows(): array {
      $counties = array_keys($this->byCounty);

      $names = [];
      foreach ($counties as $county)  $names[$county] = $this->computeMatchableNamesForCounty($county);

      $results = [];
      $count = -1;
      foreach ($counties as $county) {
         $otherCounties = $this->getOtherCounties($county);

         $rowCount = count($this->byCounty[$county]);
         for ($i=0;   $i < $rowCount;   ++$i) {
            $row   = $this->byCounty[$county][$i];
            $this->ids[] = $row['id'];
            if (empty($row['name'])) continue;

            $count++;
            $results[$count] = $row;
            $results[$count]['county'] = 99;  // indicate this is a 'merged' multi-county entry
            $myName = $names[$county][$i];

            // Cross-check this name with the names in EACH of the other counties.
            foreach ($otherCounties as $otherCounty) {
               $otherNames = $names[$otherCounty];
               $bestIndex = $myName->findBestMatch($otherNames);
//             $votes = $row['votes_C'];
//             echo $county . " " . $myName->getSimplifiedName() . "  $otherCounty  $bestIndex  $votes \n";
               if ($bestIndex >= 0)  {
                  $this->byCounty[$otherCounty][$bestIndex]['name'] = "";
                  $otherRow = $this->byCounty[$otherCounty][$bestIndex];
                  foreach (['votes_C', 'votes_D', 'votes_R', 'votes_O', 'votes_T'] as $column) {
                     $results[$count][$column] += $otherRow[$column];
                  }
                  foreach (['voteFor', 'termlen', 'cycle'] as $column) {
                     $results[$count][$column] = max($results[$count][$column], $otherRow[$column]);
                  }
                  $results[$count]['party'] = $this->bestPartyChoice($results[$count]['party'], $otherRow['party']);
               }
            }
         }
      }

      return $results;
   }

   private function bestPartyChoice(string $original, string $other): string {
      if ($original !== '?')  return $original;
      if ($other    !== '?')  return $other;
      return $original;
   }


   public function getIds(): array {
      return $this->ids;
   }

   public function computeMatchableNamesForCounty(int $county): array {
      $names = [];
      $rows  = $this->byCounty[$county];
      $rowCount = count($rows);
      for ($i=0;   $i < $rowCount;  ++$i) {
         $names[$i] = new MatchableName($rows[$i]['name']);
      }
      return $names;
   }
}
