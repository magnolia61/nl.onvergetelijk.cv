<?php

namespace Civi\Cv;

use Civi\Test\EndToEndInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test voor cv_get_field_map() in nl.onvergetelijk.cv.
 *
 * @group e2e
 *
 * cv_get_field_map() is de "Single Source of Truth" die database-kolomnamen
 * (met numeriek ID-suffix) koppelt aan interne Curriculum.*-sleutels.
 * Geen DB-afhankelijkheid — pure array-logica.
 *
 * Scenario's:
 *   - Retourneert een non-lege array
 *   - Alle sleutels bevatten een numeriek suffix (kolomnaam_NNNN)
 *   - Alle waarden beginnen met 'Curriculum.'
 *   - Bevat de kern-CV velden: totaal_keren_mee, keren_deel, keren_leid
 *   - Bevat de topkamp-velden: keren_topkamp
 *   - Bevat tekstvelden (CV_deel_text_, CV_leid_text_)
 *   - Alle waarden zijn uniek (geen dubbele mappings)
 */
class CvFieldMapTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    if (!function_exists('cv_get_field_map')) {
      $this->markTestSkipped('cv_get_field_map() niet beschikbaar; is nl.onvergetelijk.cv geïnstalleerd?');
    }
  }

  // ########################################################################
  // ### BASISSTRUCTUUR
  // ########################################################################

  /**
   * Retourneert een non-lege array.
   */
  public function testCvMapIsNonLeegArray() {
    $result = cv_get_field_map();
    $this->assertIsArray($result, 'cv_get_field_map() moet een array teruggeven.');
    $this->assertNotEmpty($result, 'De CV field map mag niet leeg zijn.');
  }

  /**
   * Alle sleutels bevatten een numeriek suffix (kolomnaam_NNNN patroon).
   */
  public function testCvMapSleutelsHebbenNumeriekeId() {
    foreach (cv_get_field_map() as $key => $value) {
      $this->assertMatchesRegularExpression('/_\d+$/', $key,
        "Sleutel '$key' moet eindigen op een numeriek suffix (bijv. _1435)."
      );
    }
  }

  /**
   * Alle waarden beginnen met 'Curriculum.' (interne namespace-conventie).
   */
  public function testCvMapWaardenBeginnenMetCurriculum() {
    foreach (cv_get_field_map() as $key => $value) {
      $this->assertStringStartsWith('Curriculum.', $value,
        "Waarde '$value' voor sleutel '$key' moet beginnen met 'Curriculum.'."
      );
    }
  }

  // ########################################################################
  // ### VERPLICHTE VELDEN
  // ########################################################################

  /**
   * Bevat de kern-tellersvelden: totaal_keren_mee, keren_deel en keren_leid.
   */
  public function testCvMapBevatKernTellers() {
    $values = array_values(cv_get_field_map());
    $this->assertContains('Curriculum.Totaal_keren_mee', $values, 'Curriculum.Totaal_keren_mee moet aanwezig zijn.');
    $this->assertContains('Curriculum.Keren_Deel',       $values, 'Curriculum.Keren_Deel moet aanwezig zijn.');
    $this->assertContains('Curriculum.Keren_Leid',       $values, 'Curriculum.Keren_Leid moet aanwezig zijn.');
  }

  /**
   * Bevat de eerste/laatste-datumvelden voor zowel deel als leid.
   */
  public function testCvMapBevatEersteLaatste() {
    $values = array_values(cv_get_field_map());
    $this->assertContains('Curriculum.Eerste_deel',  $values, 'Curriculum.Eerste_deel moet aanwezig zijn.');
    $this->assertContains('Curriculum.Laatste_deel', $values, 'Curriculum.Laatste_deel moet aanwezig zijn.');
    $this->assertContains('Curriculum.Eerste_leid',  $values, 'Curriculum.Eerste_leid moet aanwezig zijn.');
    $this->assertContains('Curriculum.Laatste_leid', $values, 'Curriculum.Laatste_leid moet aanwezig zijn.');
  }

  /**
   * Bevat de topkamp-teller.
   */
  public function testCvMapBevatTopkamp() {
    $values = array_values(cv_get_field_map());
    $this->assertContains('Curriculum.Keren_Topkamp', $values, 'Curriculum.Keren_Topkamp moet aanwezig zijn.');
  }

  /**
   * Bevat de event-vergelijkingsvelden (EventCV en EventTotaal voor deel én leid).
   */
  public function testCvMapBevatEventVelden() {
    $values = array_values(cv_get_field_map());
    $this->assertContains('Curriculum.EventCV_Deel',    $values, 'Curriculum.EventCV_Deel moet aanwezig zijn.');
    $this->assertContains('Curriculum.EventCV_Leid',    $values, 'Curriculum.EventCV_Leid moet aanwezig zijn.');
    $this->assertContains('Curriculum.EventTotaal_Deel',$values, 'Curriculum.EventTotaal_Deel moet aanwezig zijn.');
    $this->assertContains('Curriculum.EventTotaal_Leid',$values, 'Curriculum.EventTotaal_Leid moet aanwezig zijn.');
  }

  // ########################################################################
  // ### UNICITEIT
  // ########################################################################

  /**
   * Alle waarden zijn uniek — geen dubbele Curriculum.*-mappings.
   */
  public function testCvMapWaardenZijnUniek() {
    $values = array_values(cv_get_field_map());
    $this->assertEquals(
      count($values), count(array_unique($values)),
      'Elke Curriculum.*-waarde mag maar één keer voorkomen in cv_get_field_map().'
    );
  }
}
