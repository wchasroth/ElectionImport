<?php
declare(strict_types=1);

use CharlesRothDotNet\ElectionImport\StateOffice;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StateOfficeTest extends TestCase {
   #[Test]
   public function shouldGetDistrictCodeFromText() {
      self::assertEquals("",   StateOffice::getDistrictFrom("House"));
      self::assertEquals("3",  StateOffice::getDistrictFrom("3"));
      self::assertEquals("3",  StateOffice::getDistrictFrom("3 district"));
      self::assertEquals("3",  StateOffice::getDistrictFrom("3rd house"));
      self::assertEquals("4A", StateOffice::getDistrictFrom("4A Court"));
   }

}
