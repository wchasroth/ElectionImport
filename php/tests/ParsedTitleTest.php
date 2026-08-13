<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use CharlesRothDotNet\ElectionImport\ParsedTitle;

class ParsedTitleTest extends TestCase {

   #[Test]
   public function shouldExtractMayor_withNoCityName() {
      $pt = new ParsedTitle("Mayor for Ecorse", "");
      self::assertEquals (0,       $pt->getDistrict());
      self::assertEquals("c",      $pt->getJurisType());
      self::assertEquals("ecorse", $pt->getJurisName());
      self::assertEquals("mayor",  $pt->getOffice());
      self::assertEquals("city",   $pt->getOrg());
   }

   #[Test]
   public function shouldExtractTreasurer_withNoCityName() {
      $pt = new ParsedTitle("Treasurer for Dearborn Heights", "");
      self::assertEquals (0,       $pt->getDistrict());
      self::assertEquals("c",      $pt->getJurisType());
      self::assertEquals("dearborn heights", $pt->getJurisName());
      self::assertEquals("treas",  $pt->getOffice());
      self::assertEquals("city",   $pt->getOrg());
   }

   #[Test]
   public function shouldExtractDrainCommissioner() {
      $pt = new ParsedTitle("Drain Commissioner for Ingham County", "");
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
      self::assertEquals("ingham",   $pt->getJurisName());
      self::assertEquals("drain", $pt->getOffice());
   }

   #[Test]
   public function shouldExtractPoliceCommissioner() {
      $pt = new ParsedTitle("City of Detroit Police Commissioner District 2", "");
      self::assertEquals("c",  $pt->getJurisType());
      self::assertEquals("detroit", $pt->getJurisName());
      self::assertEquals("police",  $pt->getOffice());
      self::assertEquals (2,   $pt->getDistrict());
   }

   #[Test]
   public function shouldExtractDrainComm() {
      $pt = new ParsedTitle("Drain Comm.", "");
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
      self::assertEquals("drain", $pt->getOffice());
   }

   #[Test]
   public function shouldExtractCC_asCommunityCollege() {
      $pt = new ParsedTitle("GRCC Board of Trustees", "");
      self::assertEquals ("comcol-cou",   $pt->getOrg());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("grand rapids", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractGRCC_asCommunityCollege() {
      $pt = new ParsedTitle("Montcalm CC Board of Trustees", "");
      self::assertEquals ("comcol-cou",   $pt->getOrg());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("montcalm", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractGOCC_asCommunityCollege() {
      $pt = new ParsedTitle("Trustees for GOCC", "");
      self::assertEquals ("comcol-cou",   $pt->getOrg());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("glen oaks", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractCommColl() {
      $pt = new ParsedTitle("Comm Coll Member Kalamazoo Valley Community College", "");
      self::assertEquals ("comcol-cou",   $pt->getOrg());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("kalamazoo valley", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractClerkRegDeeds() {
      $pt = new ParsedTitle("Clerk/Reg. Deeds", "");
      self::assertEquals("clerkreg", $pt->getOffice());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
   }

   #[Test]
   public function shouldExtractClerkAmpRegDeeds() {
      $pt = new ParsedTitle("Clerk & Register of Deeds", "");
      self::assertEquals("clerkreg", $pt->getOffice());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
   }
   #[Test]
   public function shouldExtractClerkAndRegisterOfDeeds() {
      $pt = new ParsedTitle("Clerk and Register of Deeds", "");
      self::assertEquals("clerkreg", $pt->getOffice());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
   }

   #[Test]
   public function shouldExtractClerkAndRegisterOfDeeds2() {
      $pt = new ParsedTitle("County Clerk and Register of Deeds for Baraga County", "");
      self::assertEquals("cnty", $pt->getOrg());
      self::assertEquals("clerkreg", $pt->getOffice());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
      self::assertEquals("baraga",  $pt->getJurisName());
   }

   #[Test]
   public function shouldCountyProsecutingAttorney() {
      $pt = new ParsedTitle("Prosecuting Attorney", "Burr Oak Township Precinct 1");
      self::assertEquals("cnty", $pt->getOrg());
      self::assertEquals("atty", $pt->getOffice());
      self::assertEquals (0,    $pt->getDistrict());
      self::assertEquals("y",   $pt->getJurisType());
      self::assertEquals("",    $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractTreasure() {
      $pt = new ParsedTitle("Village of Casnovia Treasure", "");
      self::assertEquals("treas", $pt->getOffice());
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("v",  $pt->getJurisType());
   }

   #[Test]
   public function shouldExtractTreas() {
      $pt = new ParsedTitle("CITY OF MCBAIN TREAS (1)", "");
      self::assertEquals("treas", $pt->getOffice());
      self::assertEquals (0,      $pt->getDistrict());
      self::assertEquals("c",     $pt->getJurisType());
   }

   #[Test]
   public function shouldExtractClerkTreasurer() {
      $pt = new ParsedTitle("City Clerk/Treasurer for City of Monroe", "");
      self::assertEquals("clerktreas", $pt->getOffice());
      self::assertEquals (0,           $pt->getDistrict());
      self::assertEquals("c",          $pt->getJurisType());
      self::assertEquals("monroe",     $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractVillageOffice_whenRegionName_isInPrecintInfo() {
      $pt = new ParsedTitle("Village Trustee", "Burr Oak Township");
      self::assertEquals ("vil-cou",   $pt->getOrg());
      self::assertEquals (0,           $pt->getDistrict());
      self::assertEquals("v",          $pt->getJurisType());
      self::assertEquals("burr oak",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractTownshipTrustee_fromBareTrustee() {
      $pt = new ParsedTitle("Trustee", "Burr Oak Township");
      self::assertEquals ("town-cou",  $pt->getOrg());
      self::assertEquals (0,           $pt->getDistrict());
      self::assertEquals("t",          $pt->getJurisType());
      self::assertEquals("burr oak",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractAndRemoveDistrict() {
      $pt = new ParsedTitle("County Commissioner 3rd District", "");
      self::assertEquals (3,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
      self::assertEquals("",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractAndRemoveCountyComm() {
      $pt = new ParsedTitle("County Comm.  1st Dist.", "");
      self::assertEquals (1,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
      self::assertEquals("cnty-com", $pt->getOrg());
      self::assertEquals("",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractCommissioner_implictCounty() {
      $pt = new ParsedTitle("Commissioner District 1", "Fabius Township");
      self::assertEquals (1,   $pt->getDistrict());
      self::assertEquals("y",  $pt->getJurisType());
      self::assertEquals("cnty-com", $pt->getOrg());
      self::assertEquals("",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractAtLargeCityCouncil() {
      $pt = new ParsedTitle("City of East Lansing At-Large City Council Member", "");
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("c",  $pt->getJurisType());
      self::assertEquals("east lansing",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractCityCouncilPrecinct() {
      $pt = new ParsedTitle("City Council Member for City of Monroe  Precinct 6", "");
      self::assertEquals (6,   $pt->getDistrict());
      self::assertEquals("c",  $pt->getJurisType());
      self::assertEquals("monroe", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractCityWards_asCouncil() {
      $pt = new ParsedTitle("City of Niles Wards for Niles City Ward 1", "");
      self::assertEquals(1,          $pt->getDistrict());
      self::assertEquals("c",        $pt->getJurisType());
      self::assertEquals("city-cou", $pt->getOrg());
      self::assertEquals("",         $pt->getOffice());
      self::assertEquals("niles",    $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractCityWard_whenPrecinctAlsoPresent() {
      $pt = new ParsedTitle("commissioner bay city  ward 5  precinct 1", "");
      self::assertEquals (5, $pt->getDistrict());
   }

   #[Test]
   public function shouldExtractAndRemoveCommissionerAtLarge() {
      $pt = new ParsedTitle("Commissioner At Large for City of Benton Harbor", "");
      self::assertEquals (0,   $pt->getDistrict());
      self::assertEquals("c",  $pt->getJurisType());
      self::assertEquals("benton harbor",   $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractAndRemoveWard() {
      $pt = new ParsedTitle("City Council Ward 5 for City of Wayne", "");
      self::assertEquals (5,      $pt->getDistrict());
      self::assertEquals ('c',    $pt->getJurisType());
      self::assertEquals("wayne", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractAndRemoveComplicatedWard() {
      $pt = new ParsedTitle("City of Niles Wards for Niles City Ward 1", "");
      self::assertEquals (1, $pt->getDistrict());
      self::assertEquals ('c', $pt->getJurisType());
      self::assertEquals("niles", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractAndRemoveCityCouncilForCityNamedVillage() {
      $pt = new ParsedTitle("council member - lathrup village", "");
      self::assertEquals (0,        $pt->getDistrict());
      self::assertEquals ('c',      $pt->getJurisType());
      self::assertEquals("lathrup", $pt->getJurisName());
   }

   #[Test]
   public function shouldExtractBoardOfReview() {
      $pt = new ParsedTitle("Board of Review Member for City of Petersburg", "");
      self::assertEquals (0,        $pt->getDistrict());
      self::assertEquals ('c',      $pt->getJurisType());
      self::assertEquals ('city',   $pt->getOrg());
      self::assertEquals ('review', $pt->getOffice());
      self::assertEquals ("petersburg",   $pt->getJurisName());
   }

   #[Test]
   public function shouldNotFindAnyDistrictNumber() {
      $pt = new ParsedTitle("Plymouth District Library Board Member", "");
      self::assertEquals (0,         $pt->getDistrict());
      self::assertEquals ('l',       $pt->getJurisType());
      self::assertEquals("plymouth", $pt->getJurisName());
   }

   #[Test]
   public function shouldHandleSchoolBoard() {
      $pt = new ParsedTitle("Riverside School, Hagar #6", "");
      self::assertEquals ("schl-cou",          $pt->getOrg());
      self::assertEquals ("s",                 $pt->getJurisType());
      self::assertEquals ("riverside hagar 6", $pt->getJurisName());
   }

   #[Test]
   public function shouldHandleSchoolBoard_withParenthenticalPartial() {
      $pt = new ParsedTitle("Godwin Hgts. Public Schools Brd. Mem.(Partial)", "");
      self::assertEquals ("schl-cou",          $pt->getOrg());
      self::assertEquals ("s",                 $pt->getJurisType());
      self::assertEquals ("godwin heights",    $pt->getJurisName());
   }

   #[Test]
   public function shouldHandleVillageCouncilMember() {
      $pt = new ParsedTitle("Village of Ashley Council Member", "");
      self::assertEquals ("vil-cou",          $pt->getOrg());
      self::assertEquals ("v",                 $pt->getJurisType());
      self::assertEquals ("ashley", $pt->getJurisName());
   }

   #[Test]
   public function shouldHandleSchoolWithComma() {
      $pt = new ParsedTitle("Airport Community School Board Member", "");
      self::assertEquals ("schl-cou", $pt->getOrg());
      self::assertEquals ("s",        $pt->getJurisType());
      self::assertEquals ("airport",  $pt->getJurisName());
   }

   #[Test]
   public function shouldHandleComplexSchoolBoardName() {
      $pt = new ParsedTitle("Dearborn Heights School District #7 School Board Member", "");
      self::assertEquals(0,                    $pt->getDistrict());  // counter-intuitive, but correct!
      self::assertEquals("schl-cou",           $pt->getOrg());
      self::assertEquals("s",                  $pt->getJurisType());
      self::assertEquals("dearborn heights 7", $pt->getJurisName());
   }

   #[Test]
   public function shouldHandleParkComm() {
      $pt = new ParsedTitle("Park Comm. Comstock Charter Township", "");
      self::assertEquals(0,           $pt->getDistrict());  // counter-intuitive, but correct!
      self::assertEquals("town",      $pt->getOrg());
      self::assertEquals("town-park", $pt->getOffice());
      self::assertEquals("t",         $pt->getJurisType());
      self::assertEquals("comstock",  $pt->getJurisName());
   }

}
