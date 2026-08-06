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
use CharlesRothDotNet\Alfred\FieldFormatFixer;
use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

// Change all incumbent phone #s to a standard format: "123-456-7890 (additional text)".

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT id, phone FROM v4incumbents WHERE phone != ''";
$result = $pdo->run($sql);
foreach ($result->getRows() as $row) {
    $phone = FieldFormatFixer::fixPhone($row['phone']);
    if ($phone != $row['phone']) {
        $sql = "UPDATE v4incumbents SET phone = '$phone' WHERE id = $row[id]";
        $pdo->run($sql);
    }
}