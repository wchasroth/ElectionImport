#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\MatchableName;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT DISTINCT org, office, district, subdist FROM elections "
     . " ORDER BY org, office, district, subdist";
$result = $pdo->run($sql);
$offices  = $result->getRows();

foreach ($offices as $office) {

   $fields = ['org' => $office['org'], 'office' => $office['office'], 'district' => $office['district'],
      'subdist' => $office['subdist'] ];
   $result = $pdo->runSF("SELECT MAX(termlen) AS maxterm, MIN(termlen) AS minterm FROM v4elections WHERE ", "", new SqlFields($fields));
   if (! found($result))  continue;

   $row = $result->getRows()[0];
   $minterm = intval($row['minterm']);
   $maxterm = intval($row['maxterm']);
   if ($minterm >  0)  continue;
   if ($maxterm == 0)  continue;

   $updateFields = new SqlFields($fields);
   $sql = "UPDATE v4elections SET termlen=$maxterm WHERE " . $updateFields->getUpdateFragment();
   echo "$sql;\n";
// echo "max=$maxterm, " . Str::join($fields, ", ") . "\n";
}

function found($match): bool {
   return ($match->succeeded() && $match->getRowCount() > 0);
}
