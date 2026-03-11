<?php
declare(strict_types=1);

use CharlesRothDotNet\ElectionImport\OfficePhrase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OfficePhraseTest extends TestCase {

   #[Test]
   public function shouldMakeOfficePhrase() {
      $phrase = new OfficePhrase("hello, world");
      self::assertEquals("hello, world", $phrase->getTop());
   }

   #[Test]
   public function shouldPushPhrase_andRetrieveEntireStack() {
      $phrase = new OfficePhrase("hello");
      $phrase->push("goodbye");
      self::assertEquals("goodbye", $phrase->getTop());
      self::assertEquals(["hello", "goodbye"], $phrase->getAllPhrases());
   }

   #[Test]
   public function shouldFindAndRemovePhrase() {
      $phrase = new OfficePhrase("hello, worlds of mars venus earth ");
      self::assertFalse($phrase->foundAndRemovedPhrase("jupiter"));
      self::assertTrue ($phrase->foundAndRemovedPhrase("mars"));
      self::assertTrue ($phrase->foundAndRemovedPhrase("earth"));
      self::assertEquals("hello, worlds of venus", $phrase->getTop());
   }

   #[Test]
   public function shouldHandleRemovingMultiplePhrases() {
      $phrase = new OfficePhrase("hello, worlds of mars venus earth ");
      self::assertTrue($phrase->foundAndRemovedPhrase("jupiter", "venus"));
      self::assertEquals("hello, worlds of mars earth", $phrase->getTop());
   }

}
