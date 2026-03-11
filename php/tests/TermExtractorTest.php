<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\TermExtractor;

class TermExtractorTest extends TestCase {

   #[Test]
   public function shouldExtractTerm() {
      $te = new TermExtractor("Breckenridge Village Library Board Trustee Partial 4 yr term");
      self::assertEquals(4, $te->termlen);
      self::assertEquals("Breckenridge Village Library Board Trustee Partial", $te->title);
   }


   #[Test]
   public function shouldExtractTerm_withNumberWord() {
      $te = new TermExtractor("Breckenridge Village Library Board Trustee Partial four year term");
      self::assertEquals(4, $te->termlen);
      self::assertEquals("Breckenridge Village Library Board Trustee Partial", $te->title);
   }

   #[Test]
   public function shouldExtractYearTerm_withDash() {
      $te = new TermExtractor("Village Trustee for Burr Oak Village  4-year Term");
      self::assertEquals(4, $te->termlen);
      self::assertEquals("Village Trustee for Burr Oak Village  Term", $te->title);

      $te = new TermExtractor("Almont Community Schools - Board Member 6-year terms");
      self::assertEquals(6, $te->termlen);
      self::assertEquals("Almont Community Schools - Board Member", $te->title);
   }

   #[Test]
   public function shouldExtractTerm_givenLongTitle() {
      $te = new TermExtractor("Local School District Board Member 6 yrs for Eau Claire Public Schools");
      self::assertEquals(6, $te->termlen);
      self::assertEquals("Local School District Board Member for Eau Claire Public Schools", $te->title);
   }

   #[Test]
   public function shouldDoNothing() {
      $te = new TermExtractor("Breckenridge Village Library Board Trustee");
      self::assertEquals(0, $te->termlen);
      self::assertEquals("Breckenridge Village Library Board Trustee", $te->title);

   }

}
