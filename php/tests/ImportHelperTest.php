<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\ImportHelper;
class ImportHelperTest extends TestCase {

   #[Test]
   public function shouldGetCycle_givenPartialTerm() {
      $officeName = "JUSTICE OF SUPREME COURT INCUMBENT  - PARTIAL TERM ENDING 1/1/2029 (1) POSITION";
      $cycle = ImportHelper::calculateTermCycle($officeName, 2024);
      self::assertEquals (2028, $cycle);
   }

   #[Test]
   public function shouldGetCycle_givenFullTerm() {
      $officeName = "MEMBER OF THE STATE BOARD OF EDUCATION 8 YEAR TERMS (2) POSITIONS";
      $cycle = ImportHelper::calculateTermCycle($officeName, 2024);
      self::assertEquals (2032, $cycle);
   }

}
