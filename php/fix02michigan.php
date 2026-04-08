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

// Change all incumbent addresses to replace various forms of "Michigan" with just "MI".

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);

$sql = "SELECT id, address FROM v4incumbents WHERE address != ''";
$result = $pdo->run($sql);
foreach ($result->getRows() as $row) {
    $address = FieldFormatFixer::fixMI($row['address']);
    if ($address != $row['address']) {
        $address = addslashes($address);
        $sql = "UPDATE v4incumbents SET address = '$address' WHERE id = $row[id]";
        $pdo->run($sql);
    }
}