<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\ElectionImport\CadmusOffice;

class CadmusOfficeTest extends TestCase {
   #[Test]
   public function shouldCreateCadmusOffice_fromRow() {
      $office = new CadmusOffice(self::makeRowFrom("2024-11-05\t1\tCounty Prosecuting Attorney\t1\tCurtis G. Broughton\tR\t7133"));
      self::assertEquals("2024-11-05", $office->date);
      self::assertEquals(1, $office->voteFor);
      self::assertEquals(0, $office->votes_T);
   }

//   #[Test]
//   public function shouldAddCandidate() {
//      $row = self::makeRowFrom("2024-11-05\t1\tCounty Prosecuting Attorney\t1\tCurtis G. Broughton\tR\t7133");
//      $office = new CadmusOffice($row);
//      $office->addCandidate($row);
//      $winner = $office->computeWinners()[0];
//
//      self::assertEquals("2024-11-05", $winner[0]);
//      self::assertEquals(1,            $winner[1]);
//      self::assertEquals("County Prosecuting Attorney", $winner[2]);
//      self::assertEquals(1,            $winner[3]);
//      self::assertEquals("Curtis G. Broughton",         $winner[4]);
//      self::assertEquals("R",          $winner[5]);
//      self::assertEquals(7133,         $winner[6]);
//      self::assertEquals(0,            $winner[7]);
//      self::assertEquals(7133,         $winner[8]);
//      self::assertEquals(0,            $winner[9]);
//      self::assertEquals(7133,         $winner[10]);
//   }

//   #[Test]
//   public function shouldCalculateWinners() {
//      $row1 = "2024-11-05_1_Village of Turner Trustee_3_Joshua Vernon Hawley_W_6";
//      $row2 = "2024-11-05_1_Village of Turner Trustee_3_Timothy Lancaster_R_5";
//      $row3 = "2024-11-05_1_Village of Turner Trustee_3_Yolanda Miracle_D_4";
//      $row4 = "2024-11-05_1_Village of Turner Trustee_3_Robert Miracle_W_0";
//      $office = new CadmusOffice($this->makeRowFrom($row1));
//
//      $office->addCandidate($this->makeRowFrom($row1));
//      $office->addCandidate($this->makeRowFrom($row2));
//      $office->addCandidate($this->makeRowFrom($row3));
//      $office->addCandidate($this->makeRowFrom($row4));
//      $winners = $office->computeWinners();
//      self::assertEquals(3, count($winners));
//      foreach ($winners as $winner) {
//         echo Str::join($winner, "\t") . "\n";
//      }
//      self::assertEquals (15, $winners[2][10]);
//      self::assertEquals ("Yolanda Miracle", $winners[2][4]);
//      self::assertEquals (4, $winners[2][7]);
//   }

   private function makeRowFrom(string $text): array {
      $text = Str::replaceAll($text, "_", "\t");
      return Str::split($text, "\t");
   }

}
