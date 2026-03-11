<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\Jurisdictions;

class JurisdictionsTest extends TestCase {

   #[Test]
   public function shouldAddAndGetJurisdiction() {
      $juris = new Jurisdictions();
      self::assertEquals ("", $juris->getType("brownstown"));
      $juris->add("brownstown township", "township");
      self::assertEquals ("township", $juris->getType("brownstown"));
   }

   #[Test]
   public function shouldGetNothingForAmbiguousJurisdiction() {
      $juris = new Jurisdictions();
      $juris->add("brownstown", "township");
      $juris->add("brownstown", "city");
      self::assertEquals ("", $juris->getType("brownstown"));
   }

}
