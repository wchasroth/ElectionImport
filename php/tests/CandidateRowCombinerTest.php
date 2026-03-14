<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\CandidateRowCombiner;

class CandidateRowCombinerTest extends TestCase {

   #[Test]
   public function shouldGetOtherCounties() {
      $combiner = $this->makeTestCombiner();
      self::assertEquals ([62, 1],     $combiner->getOtherCounties(43));
      self::assertEquals ([43, 1],     $combiner->getOtherCounties(62));
      self::assertEquals ([43, 62, 1], $combiner->getOtherCounties( 0));
   }

   #[Test]
   public function shouldGetSimplifiedNamesForCounty() {
      $combiner = $this->makeTestCombiner();

      $names = $combiner->computeMatchableNamesForCounty(43);
      self::assertEquals ("lamont antonie hill",   $names[0]->getSimplifiedName());
      self::assertEquals ("latanya marie hill",    $names[1]->getSimplifiedName());
      self::assertEquals ("marion john carter sr", $names[2]->getSimplifiedName());
      self::assertEquals ("mary ann martin",       $names[3]->getSimplifiedName());
   }

   #[Test]
   public function shouldResolveDuplicateCandidates_andSumVoteCounts() {
      $combiner = $this->makeTestCombiner();
      $results = $combiner->getResolvedRows();
      self::assertCount(6, $results);
      $this->assertMatch($results[0], "Lamont Antonie Hill",       7,   10, 1, 4);
      $this->assertMatch($results[1], "LaTanya Marie Hill",        9,   11, 1, 4) ;
      $this->assertMatch($results[2], "Marion John Carter Sr",  1816, 2216, 2, 4);
      $this->assertMatch($results[4], "Paul (PT) Jones-Salaam",   97,  108, 4, 4);

      self::assertEquals ([47, 57, 121, 201, 311, 141, 211, 321, 331, 400], $combiner->getIds());
   }

   private function makeTestCombiner(): CandidateRowCombiner {
      $combiner = new CandidateRowCombiner();                       //  C    D     R  O     T TL  cyc  V4
      $combiner->addRow($this->makeRow(43, 47, "Lamont Antonie Hill",       7,   7,    3, 0,   10, 4, 2022, 1));
      $combiner->addRow($this->makeRow(43, 57, "LaTanya Marie Hill",        9,   9,    2, 0,   11, 4, 2022, 1));
      $combiner->addRow($this->makeRow(43, 121, "Marion John Carter Sr",  1488, 300, 1488, 0, 1788, 4, 2022, 1));
      $combiner->addRow($this->makeRow(62, 141, "Marion John Carter Sr",   328, 100,  328, 0,  428, 4, 2022, 2));
      $combiner->addRow($this->makeRow(43, 201, "Mary Ann Martin",        1846, 1846,1000, 1, 2847, 4, 2022, 0));
      $combiner->addRow($this->makeRow(62, 211, "Mary Ann Martin",         330,  330, 100, 0,  430, 4, 2022, 0));
      $combiner->addRow($this->makeRow(43, 311, "Paul (PT) Jones-Salaam",    9,    9,   0, 0,    9, 4, 2022, 0));
      $combiner->addRow($this->makeRow(62, 321, "Paul PT. Jones-Salaam",    80,   80,  10, 0,   90, 4, 2022, 0));
      $combiner->addRow($this->makeRow( 1, 331, "Paul PT. Jones-Salaam",     8,    8,   1, 0,    9, 4, 2022, 4));
      $combiner->addRow($this->makeRow( 1, 400, "Charles Roth",            666,  666,   0, 0,  666, 4, 2022, 2));
      return $combiner;
   }

   private function makeRow(int $county, int $id, string $name, int $c, int $d, int $r, int $o, int $t, int $termlen, int $cycle, int $voteFor): array {
      return ['county' => $county, 'name' => $name, 'id' => $id,
         'votes_C' => $c, 'votes_D' => $d, 'votes_R' => $r, 'votes_O' => $o, 'votes_T' => $t,
         'termlen' => $termlen, 'cycle' => $cycle, 'voteFor' => $voteFor];
   }

   private function assertMatch(array $row, string $name, int $c, int $total, int $voteFor, int $termlen): void {
      self::assertEquals ($name,    $row['name']);
      self::assertEquals ($c,       $row['votes_C']);
      self::assertEquals ($total,   $row['votes_T']);
      self::assertEquals ($voteFor, $row['voteFor']);
      self::assertEquals ($termlen, $row['termlen']);
   }



}
