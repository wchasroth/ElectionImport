<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;

class IncumbentCompressor {
   private AlfredPDO $pdo;

   function __construct(AlfredPDO $pdo) {
      $this->pdo = $pdo;
   }

   function isCountyImported(int $county): bool {
      $sql = "SELECT 1 FROM v4imported WHERE county=$county LIMIT 1";
      $result = $this->pdo->run($sql);
      return $result->getRowCount() > 0;
   }

   function isCompleted(string $type, int $county): bool {
      $sql = "SELECT 1 FROM v4completed WHERE type='$type' AND county=$county LIMIT 1";
      $result = $this->pdo->run($sql);
      return $result->getRowCount() > 0;
   }

}