<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Str;

class Jurisdictions {
   private array $name2Type = [];

   function __construct() {
   }

   function loadFrom(AlfredPDO $pdo, int $county): void {
      $sql = "SELECT name FROM v4jurisdiction WHERE county_id = $county AND name LIKE '%township%'";
      $result = $pdo->run($sql);
      foreach ($result->getRows() as $row)  $this->add($row['name'], "township");

      $sql = "SELECT name FROM v4jurisdiction WHERE county_id = $county AND name LIKE '%city%'";
      $result = $pdo->run($sql);
      foreach ($result->getRows() as $row)  $this->add($row['name'], "city");

      $sql = "SELECT name FROM v4village WHERE county_id = $county";
      $result = $pdo->run($sql);
      foreach ($result->getRows() as $row)  $this->add($row['name'], "village");
   }

   function add(string $name, string $type): void {
      $name = strtolower($name);
      $name = trim(Str::replaceFirst($name, $type, ""));
      if (isset($this->name2Type[$name])) {
         $this->name2Type[$name] = "ambiguous";
         return;
      }

      $this->name2Type[$name] = $type;
   }

   function getType (string $name): string {
      $result = $this->name2Type[$name] ?? "";
      return ($result == "ambiguous" ? "" : $result);
   }

}
