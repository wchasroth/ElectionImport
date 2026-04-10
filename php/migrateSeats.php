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

//---migrateSeats.php:  Migrate new data from county election report db, to mivoter editor db.
//
//   We keep TWO copies of the v4 mivoter database going at any one time:
//      "dmeditor"  (frequently, 'elections2')
//      "importer"  (frequently, 'elections')
//
//   As we finish the parsing of the county election reports, we update the v4seats and v4incumbents
//   tables in 'importer'.  Meanwhile, human editors are making changes to v4seats and v4incumbents
//   tables in 'dmeditor'.  So we have to be very careful about merging the two.
//
//   Here's the approach.  As new data is added to 'importer', we add records to v4completed to
//   indicate which org/offices are now "complete" (meaning we finally got all the election reports
//   parsed for a particular county).  By comparing the v4completed tables between 'importer' and
//   'dmeditor', we can tell which v4seats/v4incumbents rows in 'importer' are new (do not exist) in
//   'dmeditor'.  Those are the rows that we need to copy over from 'importer' to 'dmeditor'.
//
//   That's what this script does.

$env  = new EnvFile("_env");
$pdo1 = PdoHelper::makePdo($env);  // importer
$pdo2 = new AlfredPDO($env->get('dbname2'), $env->get('dbuser'), $env->get('dbpw'));

$sql = "SELECT * FROM v4completed ORDER BY type, id";
$result1 = $pdo1->run($sql);
$result2 = $pdo2->run($sql);

echo "result1: " . $result1->getRowCount() . "\n";
echo "result2: " . $result2->getRowCount() . "\n";
