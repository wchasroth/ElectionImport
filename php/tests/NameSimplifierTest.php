<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\NameSimplifier;

class NameSimplifierTest extends TestCase {

   #[Test]
   public function shouldSimplifySchoolDistrictNames() {
      self::assertEquals ("west branch-rose",      NameSimplifier::simplifySchoolName("WEST BRANCH-ROSE CITY AREA SCHOOLS"));
      self::assertEquals ("bangor township 8",     NameSimplifier::simplifySchoolName("BANGOR TOWNSHIP SCHOOL DISTRICT NO.8"));
      self::assertEquals ("bangor township 8",     NameSimplifier::simplifySchoolName("BANGOR TOWNSHIP SCHOOL DISTRICT #8"));
      self::assertEquals ("mcbain",                NameSimplifier::simplifySchoolName("MCBAIN RURAL AGRICULTURAL SCHOOL"));
      self::assertEquals ("standish-sterling",     NameSimplifier::simplifySchoolName("Board Member for Standish- Sterling Community Schools"));
      self::assertEquals ("benton harbor",         NameSimplifier::simplifySchoolName("Local School District Board Member for Benton Harbor Area Schools"));
   }

   #[Test]
   public function shouldSimplifyLibraryDistrictNames() {
      self::assertEquals ("northville", NameSimplifier::simplifyLibraryName("Board Member - Northville District Library"));
      self::assertEquals ("belleville", NameSimplifier::simplifyLibraryName("Belleville Area District Library Board Member"));
   }

   #[Test]
   public function shouldSimplifyTownshipNames() {
      self::assertEquals ("bainbridge", NameSimplifier::simplifyJurisdictionName("Bainbridge Twp"));
      self::assertEquals ("lanse",      NameSimplifier::simplifyJurisdictionName("L'Anse Township"));
   }

   #[Test]
   public function shouldSimplifyCollegeNames() {
      self::assertEquals ("grand rapids", NameSimplifier::simplifyCommCollegeName("GRCC"));
   }

   #[Test]
   public function shouldExtractNumber() {
      self::assertEquals (5, NameSimplifier::extractNumber("district 5 trustee"));
      self::assertEquals (0, NameSimplifier::extractNumber("Board of Trustees Member Schoolcraft Community College"));
   }

}
