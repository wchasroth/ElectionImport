#!/usr/bin/env php
<?php
declare(strict_types=1);

// phase2ClarityHtml translates the raw HTML from a Clarity election reporting site,
// into the phase3 TSV.
//
// Unfortunately, while the Clarity pages usually provide a CSV download (that is easily translatable
// into phase 3 TSV), they are often... incomplete.  E.g. 37 contests for "Supervisor", with no indication
// of the locale.  That problem makes this tool necessary, because the raw HTML *does* contain the full
// title and locale, that we need.

namespace CharlesRothDotNet\ImportTools;

use DOMDocument;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\DomUtils;
use DOMXPath;

require "vendor/autoload.php";

if ($argc < 3) {
   fwrite(STDERR, "Usage: php phase2ClarityHtml.php yyyy-mm-dd county#\n");
   exit();
}
$text = file_get_contents('php://stdin');
$electionDate = $argv[1];
$countyCode   = $argv[2];

$doc = new DOMDocument();
@$doc->loadHTML($text);  // @ disables warnings
$xpathableDom = new DOMXPath($doc);

echo "#yyyy-mm-dd\tcounty#\ttitle\tvoteFor#\tcandidateName\tpartyLetter\t#votes\n";

$prefix   = "$electionDate\t$countyCode";
$contests = $doc->getElementsByTagName("enr-contest");
foreach ($contests as $contest)   display($prefix, $contest, $xpathableDom);

function display(string $prefix, $obj, \DOMXPath $xpathableDom): void {
   $voteFor = getVoteFor($obj);

   // Look for h3 (or else h2) tags to identify the office.
   $h3s = $obj->getElementsByTagName("h3");
   if (count($h3s) == 0)  $h3s = $obj->getElementsByTagName("h2");
   if (count($h3s) == 0)  return;

   $office = DomUtils::cleanDomNodeText($h3s[0]->textContent);

   $children = DomUtils::getChildrenAtLevel($obj, 5);
   foreach ($children as $child) {
      $party = DomUtils::getTextByXpath($xpathableDom, $child, "./div/enr-party-name/div/strong");
      $name  = DomUtils::getTextByXpath($xpathableDom, $child, "./div/div");
      $votes = intval(DomUtils::getTextByXpath($xpathableDom, $child, "./div/strong"));
      if (! empty($name)) {
         if (Str::contains($name, "Write-In")) {
            $name  = Str::replaceFirst($name, "Write-In", "");
            $name  = trim($name, " -");
            $party = "W";
         }
         echo "$prefix\t$office\t$voteFor\t$name\t$party\t$votes\n";
      }
   }
}

function getVoteFor($obj): string {
   $text = $obj->textContent;
   $text = Str::substringBetween($text, "(Vote", ")");
   $text = strtolower($text);
   $text = Str::replaceFirst($text, "for", "");
   return DomUtils::cleanDomNodeText($text);
}
