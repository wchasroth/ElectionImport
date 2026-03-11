<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class CadmusOffice {
   public string $date    = "";
   public string $county  = "";
   public string $title   = "";
   public int    $voteFor = 0;
   public int    $votes_D = 0;
   public int    $votes_R = 0;
   public int    $votes_O = 0;
   public int    $votes_T = 0;
   public array  $candidates = [];

   function __construct(array $phase3Row) {
      if (count($phase3Row) == 0)  return;
      $this->date    = $phase3Row[0];
      $this->county  = $phase3Row[1];
      $this->title   = $phase3Row[2];
      $this->voteFor = intval($phase3Row[3]);
   }

   function addCandidate(array $phase3Row): void {
      $votes = intval($phase3Row[6]);
      $party = $phase3Row[5];
      $this->candidates["$party:{$phase3Row[4]}"] = $votes;
      $this->votes_T += $votes;
      switch ($party) {
         case 'R':  $this->votes_R += $votes; break;
         case 'D':  $this->votes_D += $votes; break;
         default:   $this->votes_O += $votes; break;  // W (write-in), N (npa), etc.
      }
   }

   function computeResults(): array {
      if (empty($this->title))  return [];
      $results = [];

      foreach ($this->candidates as $candidate => $votes) {
         $name   = Str::substringAfter ($candidate, ":");
         $party  = Str::substringBefore($candidate, ":");
         $results[] = [$this->date, $this->county, $this->title, $this->voteFor, $name, $party,
            $votes, $this->votes_D, $this->votes_R, $this->votes_O, $this->votes_T];
      }
      return $results;
   }

//   function computeWinners(): array {
//      if (empty($this->title))  return [];
//      $results = [];
//      uasort($this->candidates, function (int $a, int $b): int { return $b <=> $a; });
//
//      $elected = 0;
//      foreach ($this->candidates as $candidate => $votes) {
//         $name   = Str::substringAfter ($candidate, ":");
//         $party  = Str::substringBefore($candidate, ":");
//         $results[] = [$this->date, $this->county, $this->title, $this->voteFor, $name, $party,
//            $votes, $this->votes_D, $this->votes_R, $this->votes_O, $this->votes_T];
//         if (++$elected >= $this->voteFor)  break;
//      }
//      return $results;
//   }




}
