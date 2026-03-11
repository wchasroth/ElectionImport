<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\ElectionImport\PartialTerm;

class PartialTermTest extends TestCase {

   #[Test]
   public function shouldHandlePartialDate() {
      $partial = new PartialTerm("Township Treasurer Partial 11/20/2024 for Batavia Township");
      self::assertHas($partial, 1, 2024, "township treasurer for batavia township");
   }

   #[Test]
   public function shouldHandlePartialTermEndingDate() {
      $partial = new PartialTerm("Brownstown Clerk- Partial Term Ending 11/20/2024");
      self::assertHas($partial, 1, 2024, "brownstown clerk-");
   }

   #[Test]
   public function shouldHandlePartialTermEnding2() {
      $partial = new PartialTerm("trustee village of bingham farms term ending 11/21/2022");
      self::assertHas($partial, 1, 2022, "trustee village of bingham farms");
   }

   #[Test]
   public function shouldHandlePartial_withYearAtEnd() {
      $partial = new PartialTerm("Sch Bd Member Partial for Burr Oak Community Schools  2024");
      self::assertHas($partial, 1, 2024, "sch bd member for burr oak community schools");
   }

   #[Test]
   public function shouldHandlePartialDateJurisdiction() {
      $partial = new PartialTerm("Township Clerk for Partial 11/20/2024 Gilead Township");
      self::assertHas($partial, 1, 2024, "township clerk for gilead township");
   }

   #[Test]
   public function shouldHandlePartialTermEndingDate_withNoLocale() {
      $partial = new PartialTerm("Sheriff Partial Term Ending 12/31/2024");
      self::assertHas($partial, 1, 2024, "sheriff");
   }

   #[Test]
   public function shouldHandlePartialTermNoDate() {
      $partial = new PartialTerm("Haslett Public Schools Board Member Partial Term");
      self::assertHas($partial, 1, 0, "haslett public schools board member");
   }

   #[Test]
   public function shouldHandlePartialTermEnding2026() {
      $partial = new PartialTerm("City of Omer Council Member Partial Term Ending 12/31/2026");
      self::assertHas($partial, 1, 2026, "city of omer council member");
   }

   #[Test]
   public function shouldHandlePartialTermColon_withNoDateInfo() {
      $partial = new PartialTerm("Board Member - Partial Term Colon Community Schools");
      self::assertHas($partial, 1, 0, "board member - colon community schools");
   }

   private function assertHas (PartialTerm $partial, int $isPartial, int $year, string $resultingTitle): void {
      self::assertEquals ($isPartial,      $partial->isPartial);
      self::assertEquals ($year,           $partial->termcycle);
      self::assertEquals ($resultingTitle, $partial->title);
   }
}
