#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace CharlesRothDotNet\ElectionImport;

use CharlesRothDotNet\Alfred\Str;

require "vendor/autoload.php";

//---phase81fixWashtenaw.php
//
//   The WCDP endorsed candidates data that we receive, has some problems that need to be
//   fixed, before we can truly import it:
//
//      0. Delete all '\r' s!   Must be done BEFORE THIS SCRIPT IS RUN!!
//
//      1. Candidate descriptions with newlines must be re-joined with a "<p/>" instead of a newline.
//         (Each candidate line begins with "Yes", others must be concatenated.)
//
//      2. Evil Word characters (quotes, bullet points, etc) must be converted to ASCII or
//         HTML entities.

$prev = "";
while ($line = fgets(STDIN)) {
   $line = rtrim($line, "\r\n");
   $line = trim($line);
   if (isNewCandidate($line)) {
      output($prev);
      $prev = $line;
   }
   else $prev = $prev . "<p/>" . $line;
}
output ($prev);

function isNewCandidate(string $line): bool {
   $line = strtolower($line);
   return Str::startsWith($line, "yes")  ||  Str::startsWith($line, "endorsed");
}

function output(string $line): void {
   if (empty($line))  return;

   $line = Str::replaceAll($line, "\222", "'");
   $line = Str::replaceAll($line, "\226", "--");
   $line = Str::replaceAll($line, "\227", "--");
   $line = Str::replaceAll($line, "\205", "--");
   $line = Str::replaceAll($line, "\223", "\"");
   $line = Str::replaceAll($line, "\224", "\"");
   $line = Str::replaceAll($line, "\225", "&bull;");
   echo "$line\n";
}