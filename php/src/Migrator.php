<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\SqlFields;

class Migrator {
   function __construct() { }

   public function insertSeatAndIncumbent(AlfredPDO $pdo, array $seat, array $incumbent): bool {
      $sqlFields = new SqlFields(["org" => $seat['org'], "office" => $seat['office'], 'district' => $seat['district'],
         'seatnum' => $seat['seatnum'], 'seatmax' => $seat['seatmax'], 'termlen' => $seat['termlen'], 'termcycle' => $seat['termcycle']]);
      $sql = "INSERT INTO v4seats " .  $sqlFields->getInsertFragment();
//    $result    = $pdo->run($sql);
      echo "   $sql\n";
      return true;
//      if ($result->failed()) {
//         echo $result->getError() . "\n";
//         return false;
//      }
//
//      if ($incumbent === null || count($incumbent) == 0)  return true;   // unlikely, but possible: creating an empty seat?
//
//      $newSeatid = $result->getInsertId();
//      $sqlFields = new SqlFields(['seat_id' => $newSeatid, 'name' => $incumbent['name'], 'role' => $incumbent['role'], 'elected' => $incumbent['elected'], 'party' => $incumbent['party'],
//         'votes_C' => $incumbent['votes_C'], 'votes_D' => $incumbent['votes_D'], 'votes_R' => $incumbent['votes_R'], 'votes_O' => $incumbent['votes_O'], 'votes_T' => $incumbent['votes_T'],
//         'web' => $incumbent['web'], 'email' => $incumbent['email'], 'phone' => $incumbent['phone'], 'address' => $incumbent['address'], 'num2elect' => $incumbent['num2elect'],
//         'county' => $incumbent['county'], 'resigned' => $incumbent['resigned'], 'partial' => $incumbent['partial'], 'headshot' => $incumbent['headshot'], 'status' => $incumbent['status']]);
//      $sql = "INSERT INTO v4incumbents " .  $sqlFields->getInsertFragment();
//      $result    = $pdo->run($sql);
//      if ($result->failed()) echo $result->getError() . "\n";
   }

   public function getSeat(AlfredPDO $pdo, int $id): array {
      $sql = "SELECT * FROM v4seats WHERE id = $id";
      $result = $pdo->run($sql);
      if ($result->failed())  fwrite(STDERR, "getSeat Error: " . $result->getError() . "\n");
      return ($result->getRowCount() == 1 ? $result->getRows()[0] : []);
   }

   public function getIncumbent(AlfredPDO $pdo, int $id): array {
      $sql = "SELECT * FROM v4incumbents WHERE id = $id";
      $result = $pdo->run($sql);
      if ($result->failed())  fwrite(STDERR, "getIncumbent Error: " . $result->getError() . "\n");
      return ($result->getRowCount() == 1 ? $result->getRows()[0] : []);
   }

}