<?php

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once 'curriculum.civix.php';

/**
 * Implementation of hook_civicrm_custom
 *
 * This is needed only if there is a computed (View Only) custom field in this set.
 */
function curriculum_civicrm_custom($op, $groupID, $entityID, &$params) {

	//   watchdog('php', '<pre>'. print_r($op, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
	//   watchdog('php', '<pre>'. print_r($groupID, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

	if ($op != 'create' && $op != 'edit') { //    did we just create or edit a custom object?
		return; //    if not, get out of here
	}

	$tableName1 = "civicrm_value_curriculum_103"; //  table name for the custom group (each set of custom fields has a corresponding table in the database)
	$tableName2 = "civicrm_value_leid_part_125"; //  table name for the custom group (each set of custom fields has a corresponding table in the database)

	if ($groupID == 125) {
		$sql11 = "SELECT lp.welk_kamp_567 AS welkkamp, lp.functie_568 AS kampfunctie, pt.contact_id AS contactid, pt.event_id AS eventid FROM $tableName2 AS lp INNER JOIN `civicrm_participant` AS pt ON lp.entity_id = pt.id WHERE lp.entity_id = '$entityID'";
		watchdog('php', '<pre>sql11:' . print_r($sql11, true) . '</pre>', null, WATCHDOG_DEBUG);
		$dao11 = CRM_Core_DAO::executeQuery($sql11);
		#watchdog('php', '<pre>dao11:'. print_r($dao11, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

		while ($dao11->fetch()) {
			$welkkamp = $dao11->welkkamp;
			$kampfunctie = $dao11->kampfunctie;
			$contactid = $dao11->contactid;
			$eventid = $dao11->eventid;
		}

		$sql13 = "SELECT start_date AS eventstart FROM civicrm_event WHERE id = '$eventid'";
		watchdog('php', '<pre>sql3:' . print_r($sql13, true) . '</pre>', null, WATCHDOG_DEBUG);
		$dao13 = CRM_Core_DAO::executeQuery($sql13);
		#watchdog('php', '<pre>dao13:'. print_r($dao13, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

		while ($dao13->fetch()) {
			$eventstart = $dao13->eventstart;
		}

		$date1 = date_create($eventstart);
		$date2 = date_create();
		$diff = date_diff($date1, $date2);
		$diffyears = $diff->y;
		$diffmonths = $diff->m;

		watchdog('php', '<pre>kamp:' . print_r($welkkamp, true) . '</pre>', null, WATCHDOG_DEBUG);
		watchdog('php', '<pre>functie:' . print_r($kampfunctie, true) . '</pre>', null, WATCHDOG_DEBUG);
		watchdog('php', '<pre>contactid:' . print_r($contactid, true) . '</pre>', null, WATCHDOG_DEBUG);
		watchdog('php', '<pre>diff:' . print_r($diff, true) . '</pre>', null, WATCHDOG_DEBUG);
		watchdog('php', '<pre>diffyears:' . print_r($diffyears, true) . '</pre>', null, WATCHDOG_DEBUG);
		watchdog('php', '<pre>diffmonths:' . print_r($diffmonths, true) . '</pre>', null, WATCHDOG_DEBUG);
		watchdog('php', '<pre>date2:' . print_r($date2, true) . '</pre>', null, WATCHDOG_DEBUG);

		if ($diffyears == 0) {
			$sql12 = "UPDATE $tableName1 SET ditjaar_welk_kamp_label_862 = '$welkkamp', ditjaar_welk_kamp_value_864 = '$welkkamp', ditjaar_functie_865 = '$kampfunctie' WHERE entity_id = '$contactid'";
			watchdog('php', '<pre>sql12:' . print_r($sql12, true) . '</pre>', null, WATCHDOG_DEBUG);
			$dao12 = CRM_Core_DAO::executeQuery($sql12);
		}
	}

	if ($groupID == 103) {
		//    group ID of the Curriculum custom field set
		watchdog('php', '<pre>--- START EXTENSION CV ---</pre>', null, WATCHDOG_DEBUG);

		// watchdog('php', '<pre>'. print_r($dao1, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

		// UPDATE the Deelnemer CV according to the tags and only if Deelnemer CV is empty or null
		$sql3 = "SELECT TG.description FROM civicrm_entity_tag ET INNER JOIN civicrm_tag TG ON ET.tag_id = TG.id WHERE ET.entity_id = $entityID AND TG.name LIKE 'D%' ORDER BY TG.description ASC";
		$dao3 = CRM_Core_DAO::executeQuery($sql3);
		$welkejarendeel = array();
		while ($dao3->fetch()) {
			$welkejarendeel[] = $dao3->description;
			#watchdog('php', '<pre>dao3:'. print_r($dao3, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		}
		$tgdeel = count(array_filter($welkejarendeel));
		$cvdeel = implode('', $welkejarendeel);
		$sql4 = "UPDATE $tableName1 SET welke_jaren_mee_als_deelnemer__376 = '$cvdeel' WHERE entity_id = '$entityID' AND welke_jaren_mee_als_deelnemer__376 = ''";
		#$sql4     = "UPDATE $tableName1 SET welke_jaren_mee_als_deelnemer__376 = '$cvdeel' WHERE entity_id = '$entityID'";
		$dao4 = CRM_Core_DAO::executeQuery($sql4);
		$sql14 = "UPDATE $tableName1 SET tagcv_deel_856 = '$cvdeel' WHERE entity_id = '$entityID'";
		$dao14 = CRM_Core_DAO::executeQuery($sql14);
		$sql24 = "UPDATE $tableName1 SET tagtotaal_deel_848 = '$tgdeel' WHERE entity_id = $entityID";
		$dao24 = CRM_Core_DAO::executeQuery($sql24);

		#watchdog('php', '<pre>tgdeel:'. print_r($tgdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>sql3:'. print_r($sql3, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>sql4:'. print_r($sql4, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>tgdeel:'. print_r($tgdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>cvdeel:'. print_r($cvleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

		// UPDATE the Leiding CV according to the tags and only if Leiding CV is empty or null
		$sql5 = "SELECT TG.description FROM civicrm_entity_tag ET INNER JOIN civicrm_tag TG ON ET.tag_id = TG.id WHERE ET.entity_id = '$entityID' AND TG.name LIKE 'L%' ORDER BY TG.description ASC";
		$dao5 = CRM_Core_DAO::executeQuery($sql5);
		$welkejarenleid = array();
		while ($dao5->fetch()) {
			$welkejarenleid[] = $dao5->description;
			#watchdog('php', '<pre>dao3:'. print_r($dao5, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		}
		$tgleid = count(array_filter($welkejarenleid));
		$cvleid = implode('', $welkejarenleid);
		$sql6 = "UPDATE $tableName1 SET welke_jaren_ben_je_als_leiding_m_73 = '$cvleid' WHERE entity_id = '$entityID' AND welke_jaren_ben_je_als_leiding_m_73 = ''";
		#$sql6     = "UPDATE $tableName1 SET welke_jaren_ben_je_als_leiding_m_73 = '$cvleid' WHERE entity_id = '$entityID'";
		$dao6 = CRM_Core_DAO::executeQuery($sql6);
		$sql16 = "UPDATE $tableName1 SET tagcv_leid_857 = '$cvleid' WHERE entity_id = '$entityID'";
		$dao16 = CRM_Core_DAO::executeQuery($sql16);
		$sql26 = "UPDATE $tableName1 SET tagtotaal_leid_849 = '$tgleid' WHERE entity_id = $entityID";
		$dao26 = CRM_Core_DAO::executeQuery($sql26);

		#watchdog('php', '<pre>tgleid:'. print_r($tgleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>sql5:'. print_r($sql5, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>sql6:'. print_r($sql6, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>tgleid:'. print_r($tgleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		#watchdog('php', '<pre>cvleid:'. print_r($cvleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

		$sql1 = "SELECT `welke_jaren_mee_als_deelnemer__376`, `welke_jaren_ben_je_als_leiding_m_73`, `id` FROM $tableName1 WHERE entity_id = '$entityID'";
		$dao1 = CRM_Core_DAO::executeQuery($sql1, CRM_Core_DAO::$_nullArray);
		while ($dao1->fetch()) {
			//    loop through each Curriculum record, calculating the HoeVaakLeiding in jaren
			$id = $dao1->id;
			$arraydeel = explode("", $dao1->welke_jaren_mee_als_deelnemer__376);
			$arrayleid = explode("", $dao1->welke_jaren_ben_je_als_leiding_m_73);
			$hoevaakdeel = count(array_filter($arraydeel));
			$hoevaakleid = count(array_filter($arrayleid));
			$totaalmee = $hoevaakdeel + $hoevaakleid;
			$eerstedeel = 0;
			$laatstedeel = 0;
			if (!empty($arraydeel)) {
				$eerstedeel = min(array_filter($arraydeel));
				$laatstedeel = max(array_filter($arraydeel));
			}
			$eersteleid = 0;
			$laatsteleid = 0;
			if (!empty($arrayleid)) {
				$eersteleid = min(array_filter($arrayleid));
				$laatsteleid = max(array_filter($arrayleid));
			}
			$eerstekeer = $hoevaakdeel > 0 ? $eerstedeel : $eersteleid;
			$laatstekeer = $hoevaakleid > 0 ? $laatsteleid : $laatstedeel;

			#$welkejarendeelmin    = min(array_filter($welkejarendeel));
			#$welkejarenleidmin    = min(array_filter($welkejarenleid));
			#watchdog('php', '<pre>welkejarendeelmin:'. print_r($welkejarendeelmin, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>welkejarenleidmin:'. print_r($welkejarenleidmin, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);

			#watchdog('php', '<pre>arraydeel:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>arrayleid:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>eerstedeel:'. print_r($eerstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>laatstedeel:'. print_r($laatstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>eersteleid:'. print_r($eersteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>laatsteleid:'. print_r($laatsteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>eerstekeer:'. print_r($eerstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>laatstekeer:'. print_r($laatstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			$sql2 = "UPDATE $tableName1 SET hoe_vaak_mee_als_deelnemer__382 = '$hoevaakdeel', hoeveel_jaar_ben_je_in_totaal_me_74 = '$hoevaakleid', totaal_keren_mee_458 = '$totaalmee', eerste_keer_846 = '$eerstekeer', laatste_keer_847 = '$laatstekeer', eerste_deel_842 = '$eerstedeel', laatste_deel_843 = '$laatstedeel', eerste_leid_844 = '$eersteleid', laatste_leid_845 = '$laatsteleid' WHERE id = '$id' AND entity_id = '$entityID'";
			$dao2 = CRM_Core_DAO::executeQuery($sql2, CRM_Core_DAO::$_nullArray);
			#watchdog('php', '<pre>'. print_r($sql2, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			$tagverschildeel = $tgdeel - $hoevaakdeel;
			$tagverschilleid = $tgleid - $hoevaakleid;
			$sql12 = "UPDATE $tableName1 SET tagverschil_deel_850 = '$tagverschildeel', tagverschil_leid_851 = '$tagverschilleid' WHERE id = '$id' AND entity_id = '$entityID'";
			$dao12 = CRM_Core_DAO::executeQuery($sql12);
			#watchdog('php', '<pre>tagverschildeel:'. print_r($tagverschildeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
			#watchdog('php', '<pre>tagverschilleid:'. print_r($tagverschilleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG);
		}

		watchdog('php', '<pre>--- END EXTENSION CV ---</pre>', null, WATCHDOG_DEBUG);
	}
}

/**
 * Implementation of hook_civicrm_config
 */
function curriculum_civicrm_config(&$config) {
	_curriculum_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function curriculum_civicrm_xmlMenu(&$files) {
	_curriculum_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function curriculum_civicrm_install() {
	#CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, __DIR__ . '/sql/auto_install.sql');
	return _curriculum_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function curriculum_civicrm_uninstall() {
	#CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, __DIR__ . '/sql/auto_uninstall.sql');
	return _curriculum_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function curriculum_civicrm_enable() {
	return _curriculum_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function curriculum_civicrm_disable() {
	return _curriculum_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function curriculum_civicrm_managed(&$entities) {
	return _curriculum_civix_civicrm_managed($entities);
}
