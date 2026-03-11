<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

class OfficePhrase {

   private array $phraseStack = [];

   public function __construct(string $text) {
      array_push($this->phraseStack, $text);
   }

   public function getTop(): string {
      $lastKey = array_key_last($this->phraseStack);
      return ($lastKey !== null ? $this->phraseStack[$lastKey] : "");
   }

   public function push(string $text): void {
      array_push($this->phraseStack, $text);
   }

   public function append(string $text): void {
      array_push($this->phraseStack, $this->getTop() . $text);
   }

   public function getAllPhrases(): array {
      return $this->phraseStack;
   }

   public function contains(string $text): bool {
      return Str::contains($this->getTop(), $text);
   }

   public function foundAndRemovedPhrase(string ... $texts): bool {
      $found = false;
      $top   = $this->pad($this->getTop());
      foreach ($texts as $text) {
         $text = $this->pad($text);
         if (Str::contains($top, $text)) {
            $found = true;
            $top = Str::replaceAll($top, $text, " ");
            $top = $this->removeAllDoubleSpaces($top);
            $this->push(trim($top));
         }
      }
      return $found;
   }

   private function pad(string $text): string {
      return " $text ";
   }

   private function removeAllDoubleSpaces(string $text): string {
      while (strpos($text, '  ') !== false)   $text = str_replace('  ', ' ', $text);
      return $text;
   }



}
