<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\ElectionImport\CadmusOrg;
use CharlesRothDotNet\ElectionImport\Jurisdictions;

class CadmusOrgTest extends TestCase {
//   static array $townships = ["brownstown", "canton", "grosse ile", "huron", "northville", "plymouth", "redford", "sumpter", "van buren"];
   private static Jurisdictions $juris;

   public static function setUpBeforeClass(): void {
      self::$juris = new Jurisdictions();
   }

   #[Test]
   public function shouldKeepSchoolDistrictNumber_asPartOfRegion() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Dearborn Heights School District No. 7", self::$juris);
      self::assertEquals ("dearborn heights 7", $cadmus->region);
   }

   #[Test]
   public function shouldGetTownshipOffice() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Adams Township Clerk", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-clerk", "adams");
   }

//   #[Test]
//   public function shouldGetTownshipOffice_whenTownshipNotShown() {
//      $cadmus = CadmusOrg::makeCadmusFromTitle("Brownstown Clerk- Partial Term Ending 11/20/2024", self::$juris, 2024);
//      self::assertCadmusHas($cadmus, "town", "town-clerk", "brownstown");
//   }

   #[Test]
   public function shouldGetTownshipSupervisor() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Supervisor for Alaiedon Township", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-super", "alaiedon");
   }

   #[Test]
   public function shouldGetTownshipSupervisor_givenOnlyTownshipName() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Supervisor Almena {Almena Township Precinct 1}", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-super", "almena");
   }

   #[Test]
   public function shouldGetTownshipTreasurer_givenOnlyTownshipName() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Treasurer Almena {Almena Township Precinct 1}", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-treas", "almena");
   }

   #[Test]
   public function shouldGetTownshipParksCommission() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Parks Commission for Redford Township", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-park", "redford");
   }

   #[Test]
   public function shouldGetLeadingTownship_withOfficeName() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Township Treasurer for Aurelius Township", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-treas", "aurelius");
   }

   #[Test]
   public function shouldGetTownshipCouncil() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Adams Township Trustee", self::$juris);
      self::assertCadmusHas($cadmus, "town-cou", "council", "adams");
   }

   #[Test]
   public function shouldGetTownshipTrustee() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Township Trustee for Alaiedon Township", self::$juris);
      self::assertCadmusHas($cadmus, "town-cou", "council", "alaiedon");
   }

//   #[Test]
//   public function shouldGetTownshipTrustee_withPartial() {
//      $cadmus = CadmusOrg::makeCadmusFromTitle("Brownstown Trustee- Partial Term Ending 11/20/2024", self::$juris, 2024);
//      self::assertCadmusHas($cadmus, "town-cou", "council", "brownstown");
//   }

   #[Test]
   public function shouldGetCharterTownshipTrustee() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Trustee for Charter Township of Brownstown", self::$juris);
      self::assertCadmusHas($cadmus, "town-cou", "council", "brownstown");
   }

   #[Test]
   public function shouldGetPartialTownshipTrustee() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Township Treasurer Partial 11/20/2024 for Batavia Township", self::$juris, 2026);
      self::assertCadmusHas($cadmus, "town", "town-treas", "batavia");
      self::assertEquals (2024, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetPartialTownshipTrustee_withNoEndDate() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Township Treasurer Partial for Batavia Township", self::$juris, 2026);
      self::assertCadmusHas($cadmus, "town", "town-treas", "batavia");
      self::assertEquals (0, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetPartialTownshipClerk() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Township Clerk for Partial 11/20/2024 Gilead Township", self::$juris);
      self::assertCadmusHas($cadmus, "town", "town-clerk", "gilead");
   }

   #[Test]
   public function shouldGetCountyOffice() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("County Clerk", self::$juris);
      self::assertCadmusHas($cadmus, "cnty", "clerk", "");
   }

   #[Test]
   public function shouldGetCountyClerkRegister() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("County Clerk and Register of Deeds for Baraga County", self::$juris);
      self::assertCadmusHas($cadmus, "cnty", "clerkreg", "baraga");
   }

   #[Test]
   public function shouldGetCountyExecutive() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("County Executive Wayne County", self::$juris, 2024);
      self::assertCadmusHas($cadmus, "cnty", "executive", "wayne");
   }

   #[Test]
   public function shouldGetCountyOffice_withoutCountyPrefix() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Prosecuting Attorney", self::$juris);
      self::assertCadmusHas($cadmus, "cnty", "atty", "");
   }

   #[Test]
   public function shouldGetCountyCommissioner() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("County Commissioner 3rd District", self::$juris);
      self::assertCadmusHas($cadmus, "cnty-com", "", "", 3);
   }

   #[Test]
   public function shouldGetCityOfCouncil() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City of Standish Council Member", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "standish");
      self::assertEquals (0, $cadmus->subdist);
      self::assertEquals ("c", $cadmus->type);
   }

   #[Test]
   public function shouldGetCityCommissioner() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City Commissioner 4 Year Term for Watervliet City", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "watervliet");
      self::assertEquals (0, $cadmus->subdist);
      self::assertEquals ("c", $cadmus->type);
   }

   #[Test]
   public function shouldGetCityCommission() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City of Gladstone City Commission", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "gladstone");
      self::assertEquals (0, $cadmus->subdist);
      self::assertEquals ("c", $cadmus->type);
   }

   #[Test]
   public function shouldGetCityCouncilCityOf() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City Council - City of Ecorse", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "ecorse");
   }

   #[Test]
   public function shouldGetCityCouncilCityWithDistrict() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City Council District 2 for Highland Park", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "highland park", 2);
   }

   #[Test]
   public function shouldGetCityCouncilCityWithWard() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("city council ward 4 for city of wayne", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "wayne", 4);
   }

   #[Test]
   public function shouldGetCityCouncilCityWithWard2() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City Ward Member for Midland Ward 1", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "midland", 1);
   }

   #[Test]
   public function shouldGetCityCouncilCityWithWard_givenPctInfo() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Council Member Ward 1 {City of South Haven Ward 1 Precinct 1}", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "south haven", 1);
   }

   #[Test]
   public function shouldGetCityOfOffice() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City of Standish Clerk", self::$juris);
      self::assertCadmusHas($cadmus, "city", "clerk", "standish");
   }

   #[Test]
   public function shouldGetCityPoliceCommissioner() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City of Detroit Police Commissioner District 2 Partial Term Ending 01/01/2022 (Vote for  1)", self::$juris);
      self::assertCadmusHas($cadmus, "city", "police", "detroit", 2);
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (2022, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetSchoolBoardPartial_withTermAtEnd() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Sch Bd Member Partial for Burr Oak Community Schools  2026 {Burr Oak Township Precinct 1}", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "burr oak", 0);
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (2026, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetSchoolBoard_withUselessFullTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("NonPartisan - Board Member Local School District Lake City Fulll Term", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "lake", 0);
      self::assertEquals (0, $cadmus->partial);
   }

   #[Test]
   public function shouldGetSchoolBoard_withJustEndWord() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("NonPartisan - Board Member Local School District McBain End 12/31/24", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "mcbain", 0);
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (2024, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetSchoolBoard_forDoubledDistrictName() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("School Board Member Paw Paw", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "paw-paw", 0);
      self::assertEquals (0, $cadmus->partial);
   }

   #[Test]
   public function shouldGetSchoolBoard_withBackwardsSyntax() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board Member for 6 Year Term Bridgman Public Schools", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "bridgman", 0);
      self::assertEquals (0, $cadmus->partial);
      self::assertEquals (6, $cadmus->termlen);
   }

   #[Test]
   public function shouldGetSchoolBoard_withSeatNumberYikes() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board Member 2 for 4 Year Term New Buffalo Area Schools", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "new buffalo", 0);
      self::assertEquals (0, $cadmus->partial);
      self::assertEquals (4, $cadmus->termlen);
   }

   #[Test]
   public function shouldHandleCityCommissioner_withBarePartial() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City Commissioner for Three Rivers City At-Large Partial {Three Rivers City Precinct 1}", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "three rivers", 0);
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (0, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetOfficeThenCityOf() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Treasurer - City of Ecorse", self::$juris);
      self::assertCadmusHas($cadmus, "city", "treas", "ecorse");
   }

   #[Test]
   public function shouldGetCityMayor() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Mayor Garden City", self::$juris);
      self::assertCadmusHas($cadmus, "city", "mayor", "garden");
   }

   #[Test]
   public function shouldGetDashedCityMayor() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Mayor - City of Ecorse", self::$juris);
      self::assertCadmusHas($cadmus, "city", "mayor", "ecorse");
   }

   #[Test]
   public function shouldGetCityOfMayor() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Mayor City of Ecorse", self::$juris);
      self::assertCadmusHas($cadmus, "city", "mayor", "ecorse");
   }

   #[Test]
   public function shouldGetCityOfOffice_withPartialTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Treasurer - City of Highland Park, Partial Term Ending 12/31/2026", self::$juris);
      self::assertCadmusHas($cadmus, "city", "treas", "highland park");
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (2026, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetVillageOffice_withPartialTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Village Trustee/Council Member Partial for Berrien Springs Village", self::$juris);
      self::assertEquals (1, $cadmus->partial);
      self::assertCadmusHas($cadmus, "vil-cou", "council", "berrien springs");
      self::assertEquals (0, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetCntyOffice_withPartialTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Sheriff Partial Term Ending 12/31/2024", self::$juris);
      self::assertCadmusHas($cadmus, "cnty", "sheriff", "");
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (2024, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetCityOfOffice_withLeadingOfficeName() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Mayor - City of Ecorse", self::$juris);
      self::assertCadmusHas($cadmus, "city", "mayor", "ecorse");
   }

   #[Test]
   public function shouldGetCityCouncil() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Au Gres City Council Member", self::$juris);
      self::assertCadmusHas($cadmus, "city-cou", "", "au gres");
   }

   #[Test]
   public function shouldGetCityOffice() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Au Gres City Mayor", self::$juris);
      self::assertCadmusHas($cadmus, "city", "mayor", "au gres");
   }

   #[Test]
   public function shouldGetSchoolBoardMember() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board Member for Standish- Sterling Community Schools 6 Year Term", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "standish-sterling");
   }

   #[Test]
   public function shouldGetSchoolBoardMember_withPartialTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board Member -Partial Term Colon Community Schools", self::$juris);
      self::assertEquals(1, $cadmus->partial);
      self::assertCadmusHas($cadmus, "schl-cou", "", "colon");
   }

   #[Test]
   public function shouldGetLibraryBoardMember_withoutDashes() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board Member - Belleville Area District Library", self::$juris);
      self::assertCadmusHas($cadmus, "libry-cou", "", "belleville");
   }

   #[Test]
   public function shouldGetSchoolBoardMemberReversed() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Dansville Schools Board Member", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "dansville");
   }

   #[Test]
   public function shouldGetSchoolBoardMember_withTermInfo() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Haslett Public Schools Board Member Partial Term", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "haslett");
      self::assertEquals(1, $cadmus->partial);
   }

   #[Test]
   public function shouldGetLeadingBoardMember() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board Member Garden City Public Schools", self::$juris);
      self::assertCadmusHas($cadmus, "schl-cou", "", "garden");
   }

   #[Test]
   public function shouldGetVillageOfTrustee() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Village of Twining Trustee", self::$juris);
      self::assertCadmusHas($cadmus, "vil-cou", "council", "twining");
   }

   #[Test]
   public function shouldGetVillageOfCityTrustee() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Village of Union City Trustee", self::$juris);
      self::assertCadmusHas($cadmus, "vil-cou", "council", "union city");
   }

   #[Test]
   public function shouldGetVillageOfOffice() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Village of Sterling Treasurer", self::$juris);
      self::assertCadmusHas($cadmus, "vil", "treas", "sterling");
   }

   #[Test]
   public function shouldGetCommunityCollege() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Lansing Community College Trustee", self::$juris);
      self::assertCadmusHas($cadmus, "comcol-cou", "", "lansing");
   }

   #[Test]
   public function shouldGetCommunityCollege_withDistricts() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Board of Trustees Member Wayne County Community College 1st District", self::$juris);
      self::assertCadmusHas($cadmus, "comcol-cou", "", "wayne", 1);
   }

   #[Test]
   public function shouldGetVillageOffice() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Village President for Village of Sterling", self::$juris, 2024);
      self::assertCadmusHas($cadmus, "vil", "pres", "sterling");
      self::assertEquals (2024, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetVillagePresident() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Dansville Village President", self::$juris);
      self::assertCadmusHas($cadmus, "vil", "pres", "dansville");
   }

   #[Test]
   public function shouldGetPartialTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City of Omer Council Member Partial Term Ending 12/31/2026", self::$juris);
      self::assertEquals (1, $cadmus->partial);
      self::assertEquals (2026, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetComCollege_withTerm() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Glen Oaks Community College Board of Trustees Member - 4 Year Term", self::$juris);
      self::assertEquals (0, $cadmus->partial);
      self::assertEquals (4, $cadmus->termlen);
      self::assertEquals ("glen oaks", $cadmus->region);
      self::assertEquals ("comcol-cou", $cadmus->org);
   }

   #[Test]
   public function shouldGetTermFromYrs() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Local School District Board Member 6 yrs for Eau Claire Public Schools", self::$juris);
      self::assertEquals ("eau claire", $cadmus->region);
      self::assertEquals ("schl-cou", $cadmus->org);
      self::assertEquals (6, $cadmus->termlen);
   }

   #[Test]
   public function shouldGetTermEnd() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Norwich Township Clerk Term End 11/20/24", self::$juris);
      self::assertEquals ("norwich", $cadmus->region);
      self::assertEquals ("town", $cadmus->org);
      self::assertEquals ("town-clerk", $cadmus->office);
      self::assertEquals (2024, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetTerm_withNoEndNoPartialYikes() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Forest Township Trustee Term 11/20/24", self::$juris);
      self::assertEquals ("forest",   $cadmus->region);
      self::assertEquals ("town-cou", $cadmus->org);
      self::assertEquals (2024,       $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetTermAndCycle() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("City of Omer Council Member 4 Year Term", self::$juris, 2024);
      self::assertCadmusHas($cadmus, "city-cou", "", "omer");
      self::assertEquals (0, $cadmus->partial);
      self::assertEquals (4, $cadmus->termlen);
      self::assertEquals (2024, $cadmus->termcycle);
   }

   #[Test]
   public function shouldGetTermAndCycle_withParentheses() {
      $cadmus = CadmusOrg::makeCadmusFromTitle("Village of Webberville Trustee (4 Year Term)", self::$juris, 2024);
      self::assertEquals (0, $cadmus->partial);
      self::assertEquals (4, $cadmus->termlen);
      self::assertEquals (2024, $cadmus->termcycle);
   }

   private function assertCadmusHas(CadmusOrg $cadmus, string $org, string $office, string $region, int $subdist=0): void {
      self::assertTrue(self::isMatch($org,     $cadmus->org,     "org"));
      self::assertTrue(self::isMatch($office,  $cadmus->office,  "office"));
      self::assertTrue(self::isMatch($region,  $cadmus->region,  "region"));
      self::assertTrue(self::isMatch($subdist, $cadmus->subdist, "subdist"));
   }

   private function isMatch($value1, $value2, string $type): bool {
      if ($value1 == $value2)  return true;
      echo "$type mismatch: '$value1'   '$value2'\n";
      return false;
   }

}
