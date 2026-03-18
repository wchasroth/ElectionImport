<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;

class MultiCountyOfficeCombiner {
   private AlfredPDO $pdo;

   function __construct(AlfredPDO $pdo) {
      $this->pdo = $pdo;
   }

   function combine(string $sql): void {
      $result = $this->pdo->run($sql);
      $races  = $result->getRows();
      foreach ($races as $race) {
         $fields = new SqlFields(['org' => $race['org'], 'office' => $race['office'], 'district' => $race['district'], 'subdist' => $race['subdist'],
            'partial' => $race['partial'], 'year' => $race['year'], 'termlen' => $race['termlen'], 'incumbent' => $race['incumbent'],
         ]);
         $result = $this->pdo->runSF("SELECT * FROM v4elections WHERE ", "ORDER BY name", $fields);
         $rows = $result->getRows();

         $combiner = new CandidateRowCombiner();
         foreach ($rows as $row)  $combiner->addRow($row);

         if (count($combiner->getOtherCounties(-1)) == 1)  continue;  // Skip if all entries are in the same county.

         $combinedRows = $combiner->getResolvedRows();
         $sql = "DELETE FROM v4elections WHERE id IN (" . Str::join($combiner->getIds(), ",") . ")";
         $this->pdo->run($sql);
         foreach ($combinedRows as $row) {
            unset($row['id']);
            $fields = new SqlFields($row);
            $result = $this->pdo->runSF("INSERT INTO v4elections", "", $fields, true);
            if ($result->failed()) fwrite (STDERR, $result->getError() . "\n");
         }
      }
   }

}