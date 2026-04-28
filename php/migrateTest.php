#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\Csv;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---migrateTest.php:  Test and list seats to be migrated, before running migrateSeats.php

$env  = new EnvFile("_env");
$pdo1 = PdoHelper::makePdo($env);  // importer
$pdo2 = new AlfredPDO($env->get('dbname2'), $env->get('dbuser'), $env->get('dbpw'));

$sql = "SELECT type, id FROM v4completed ORDER BY type, id";
$result1 = $pdo1->run($sql);
$result2 = $pdo2->run($sql);

$typeIds1 = makeCompletedTypeIds($result1->getRows());
$typeIds2 = makeCompletedTypeIds($result2->getRows());
$diffs = array_diff($typeIds1, $typeIds2);
foreach ($diffs as $typeId)  echo "New $typeId\n";

function makeCompletedTypeIds (array $rows): array {
    $typeIds = [];
    foreach ($rows as $row) $typeIds[] = $row['type'] . ":" . $row['id'];
    return $typeIds;
}