<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\PartyFinder;

class PartyFinderTest extends TestCase {

   #[Test]
   public function shouldGetPartyCode_fromFullName() {
      self::assertEquals ("D", PartyFinder::getPartyCode("Democratic"));
      self::assertEquals ("N", PartyFinder::getPartyCode("NON PARTISAN"));
      self::assertEquals ("N", PartyFinder::getPartyCode("NO  AFFILIATION"));
      self::assertEquals ("U", PartyFinder::getPartyCode("Fruitcake"));
   }


}
