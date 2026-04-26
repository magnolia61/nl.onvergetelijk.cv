<?php

// === FUNCTIE-INDEX ===
// Bestand: cv.php
// Functies in dit bestand:
//   cv_get_field_map()      De centrale mapping voor alle CV-gerelateerde custom fields binnen
//   cv_civicrm_customPre()  De "Portier" voor de CV-module. Deze hook controleert of de inkomende
//   cv_civicrm_configure()  De "Rekenmachine" voor CV. Consolideert handmatige vinkjes uit het
//   cv_civicrm_install()
//   cv_civicrm_uninstall()
//   cv_civicrm_enable()
//   cv_civicrm_disable()
// === EINDE FUNCTIE-INDEX ===

require_once 'cv.civix.php';

/**
 * =======================================================================================
 * COLOFON: cv_get_field_map (SINGLE SOURCE OF TRUTH)
 * =======================================================================================
 * @description     De centrale mapping voor alle CV-gerelateerde custom fields binnen 
 * deze module. Koppelt de database-kolommen aan APIv4-namen.
 * @return array    Associatieve array in het format: ['db_naam_ID' => 'API.naam'].
 * =======================================================================================
 */
function cv_get_field_map(): array {
	return [
		'totaal_keren_mee_458'			=> 'Curriculum.Totaal_keren_mee',
		'eerste_keer_846'				=> 'Curriculum.Eerste_keer',
		'laatste_keer_847'				=> 'Curriculum.Laatste_keer',

		'cv_deel_1435'					=> 'Curriculum.CV_Deel',
		'keren_deel_1437'				=> 'Curriculum.Keren_Deel',
		'eerste_deel_842'				=> 'Curriculum.Eerste_deel',
		'laatste_deel_843'				=> 'Curriculum.Laatste_deel',
		'event_cv_deel_1209'			=> 'Curriculum.EventCV_Deel',
		'event_totaal_deel_1001'		=> 'Curriculum.EventTotaal_Deel',
		'event_verschil_deel_1110'		=> 'Curriculum.Eventverschil_Deel',
		'cv_deel_text_1486'				=> 'Curriculum.CV_deel_text_',

		'keren_topkamp_1027'			=> 'Curriculum.Keren_Topkamp',
		'eerste_topkamp_1053'			=> 'Curriculum.Eerste_Topkamp',
		'laatste_topkamp_1054'			=> 'Curriculum.Laatste_Topkamp',

		'cv_leid_1436'					=> 'Curriculum.CV_Leid',
		'keren_leid_1438'				=> 'Curriculum.Keren_Leid',
		'eerste_leid_844'				=> 'Curriculum.Eerste_leid',
		'laatste_leid_845'				=> 'Curriculum.Laatste_leid',
		'event_cv_leid_1210'			=> 'Curriculum.EventCV_Leid',
		'event_totaal_leid_1002'		=> 'Curriculum.EventTotaal_Leid',
		'event_verschil_leid_1112'		=> 'Curriculum.Eventverschil_Leid',
		'cv_leid_text_1487'				=> 'Curriculum.CV_leid_text_',
	];
}

/**
 * =======================================================================================
 * COLOFON: cv_civicrm_customPre
 * =======================================================================================
 * @description     De "Portier" voor de CV-module. Deze hook controleert of de inkomende
 * data CV-velden bevat via de field_map. Hij voorkomt dat de zware
 * herberekening draait bij irrelevante formulier-submits.
 * @trigger         Wordt getriggerd bij het aanpassen van een Contact ('edit').
 * =======================================================================================
 */

function cv_civicrm_customPre($op, $groupID, $entityID, &$params) {

	// --- STAP 0: PREVENTIE VAN DUBBELE UITVOERING ---
	static $processing_cv_pre = FALSE;
	if ( $op !== 'edit' || $processing_cv_pre ) {
		return;
	}

	$extdebug      = 0;
	$profilecontcv = [103]; // CV Beheer profiel ID

	if (!in_array($groupID, $profilecontcv)) {
		return;
	}

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV [PRE] 0.1 EXTRACTIE DATA",                            "[EXTRACT]");
	wachthond($extdebug, 2, "########################################################################");

	// Gebruik de centrale map om CV-data uit de ruwe hook-params te vissen.
	$name_map  = cv_get_field_map();
	$field_ids = base_get_field_ids($name_map); // Nodig voor de injectie in stap 3
	$params_cv = base_extract_from_params($params, $name_map);

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV [PRE] 0.2 EARLY EXIT & WEBFORM FILTER",               "[FILTER]");
	wachthond($extdebug, 2, "########################################################################");

	if (!in_array($groupID, $profilecontcv)) {
		$has_trigger = isset($params_cv['Curriculum.CV_Deel']) || isset($params_cv['Curriculum.CV_Leid']);
		if (!$has_trigger) {
			wachthond($extdebug, 2, "CV PRE: Geen triggers gevonden. Stop processing.",   "[SKIP]");
			return;
		}
	}

	$processing_cv_pre = TRUE;
	
	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV [PRE] 1.0 START ANALYSE VOOR CONTACT: $entityID",     "[CUSTOMPRE]");
	wachthond($extdebug, 2, "########################################################################");

	// --- STAP 2.0: LOGICA UITBESTEDEN AAN DE REKENMACHINE ---
	// Let op: we sturen &$params NIET meer mee. We geven de context 'hook' mee.
	$data_cv = cv_civicrm_configure($entityID, NULL, NULL, 'hook', $params_cv);

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV [PRE] 3.0 INJECTIE IN FORMULIER",                     "[INJECT]");
	wachthond($extdebug, 2, "########################################################################");

	// --- STAP 3.0: RESULTATEN TERUGSTOPPEN IN HET FORMULIER ---
	if (!empty($data_cv)) {
		$success_list = base_inject_params($params, $data_cv, $field_ids, $entityID, "CV_HOOK");
		
		if (!empty($success_list)) {
			wachthond($extdebug, 1, "CV [PRE] SUCCES: Injectie voltooid", $success_list);
		}
	}

	// --- STAP 4.0: DRUPAL DATUM CRASH VOORKOMEN ---
	if (function_exists('drupal_timestamp_sweep')) {
		drupal_timestamp_sweep($params);
	}

	$processing_cv_pre = FALSE;
}

/**
 * =======================================================================================
 * COLOFON: cv_civicrm_configure
 * =======================================================================================
 * @description     De "Rekenmachine" voor CV. Consolideert handmatige vinkjes uit het
 * formulier (indien aanwezig) met de feitelijke deelname-historie.
 * @logic           Het script hanteert generatie-afhankelijke logica: voor jongere 
 * vrijwilligers is de database leidend; voor ouderen worden 
 * historische handmatige invoer en database-events samengevoegd.
 * =======================================================================================
 */

function cv_civicrm_configure($contactid, $array_contact = NULL, $ditjaar_array = NULL, $context = 'direct', $params_cv = []) {

	// --- RECURSIE BEVEILIGING (HET SLOT) ---
	static $processing_cv_configure = FALSE;
	if ($processing_cv_configure || empty($contactid)) {
		return;
	}

	$extdebug                = 0; 
	$apidebug                = FALSE;

	$processing_cv_configure = TRUE;
	$contact_id              = $contactid;

	base_microtimer("Start CV Analyse");

	if (empty($array_contact)) {
		$array_contact = base_cid2cont($contact_id);
	}
	$displayname = $array_contact['displayname'] ?? "Contact $contact_id";

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 1.0 CONFIGURATIE & STATUS IDS OPHALEN",                 "[CONFIG]");
	wachthond($extdebug, 2, "########################################################################");

	// Haal alle relevante statussen op (o.a. 'Positive' voor deelname-telling).
	$status_data      = function_exists('find_partstatus') ? find_partstatus() : NULL;
	$status_positive  = $status_data['ids']['Positive'] ?? [];
	$status_negative  = $status_data['ids']['Negative'] ?? [];

	// Definieer de kamp-categorieën uit de centrale types.
	$eventtypes       = get_event_types();
	$eventtypesdeel   = $eventtypes['deel']     ?? [];
	$eventtypesdeeltop= $eventtypes['deeltop']  ?? [];
	$eventtypesleid   = $eventtypes['leid']     ?? [];

	$birth_date       = $array_contact['birth_date'] ?? NULL;

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 2.0 BEPAAL LEIDENDE WAARDEN (FORM vs DB)",               "[INPUT]");
	wachthond($extdebug, 2, "########################################################################");

	// Formulier-input (params_cv) heeft altijd prioriteit over de database ($array_contact).
	$db_cv_deel_array = (array)($array_contact['curcv_deel_array'] ?? []);
	$db_cv_leid_array = (array)($array_contact['curcv_leid_array'] ?? []);

	$form_cv_deel_array = NULL;
	$form_cv_leid_array = NULL;

	// Als de gebruiker jaren heeft aangepast in het formulier, vangen we die hier op.
	if (isset($params_cv['Curriculum.CV_Deel'])) {
		$raw_deel           = $params_cv['Curriculum.CV_Deel'];
		$form_cv_deel_array = array_filter(explode("\x01", trim($raw_deel, "\x01")));
	}
	
	if (isset($params_cv['Curriculum.CV_Leid'])) {
		$raw_leid           = $params_cv['Curriculum.CV_Leid'];
		$form_cv_leid_array = array_filter(explode("\x01", trim($raw_leid, "\x01")));
	}

	$curcv_deel_array = ($form_cv_deel_array !== NULL) ? $form_cv_deel_array : $db_cv_deel_array;
	$curcv_leid_array = ($form_cv_leid_array !== NULL) ? $form_cv_leid_array : $db_cv_leid_array;

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 3.0 OPHALEN PARTICIPANT DATA (DEEL & LEID)",              "[DATA]");
	wachthond($extdebug, 2, "########################################################################");

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 3.1 OPHALEN DEELNEMER HISTORIE",                          "[DEEL]");
	wachthond($extdebug, 2, "########################################################################");

	// Zoek alle Participant records van het type 'Deelnemer' of 'Topkamp'.
	$all_relevant_types_deel = array_merge($eventtypesdeel, $eventtypesdeeltop);
	$all_relevant_status_deel = array_unique(array_merge($status_positive, [4]));

	$params_combined_deel = [
		'checkPermissions' => FALSE,
		'debug'            => $apidebug,
		'select'           => ['event_id.start_date', 'status_id', 'event_id.event_type_id'],
		'where'            => [
			['contact_id', 				'=',  $contact_id],
			['event_id.event_type_id', 	'IN', $all_relevant_types_deel],
			['status_id', 				'IN', $all_relevant_status_deel],
			['is_test', 				'IN', [TRUE, FALSE]],
		],
	];

	wachthond($extdebug, 7, 'params_combined_deel', $params_combined_deel);
	$result_combined_deel = civicrm_api4('Participant', 'get', $params_combined_deel);
	wachthond($extdebug, 9, 'result_combined_deel', $result_combined_deel);

	$evtcv_deel_array     = [];
	$evtcv_deel_top_array = [];
	
	foreach ($result_combined_deel as $part) {
		$year   = date('Y', strtotime($part['event_id.start_date']));
		$status = (int)$part['status_id'];
		$type   = (int)$part['event_id.event_type_id'];

		if (in_array($status, $status_positive)) {
			if (in_array($type, $eventtypesdeel)) 	 $evtcv_deel_array[] 	 = $year;
			if (in_array($type, $eventtypesdeeltop)) $evtcv_deel_top_array[] = $year;
		}
	}

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 3.2 OPHALEN LEIDING HISTORIE",                            "[LEID]");
	wachthond($extdebug, 2, "########################################################################");

	// Zoek alle Participant records van het type 'Leiding'.
	$all_relevant_types_leid   = $eventtypesleid;
	$all_relevant_status_leid  = array_unique(array_merge($status_positive, $status_negative));

	$params_combined_leid = [
		'checkPermissions' => FALSE,
		'debug'            => $apidebug,
		'select'           => ['event_id.start_date', 'status_id', 'PART_LEID.Welk_kamp', 'PART_LEID.Functie'],
		'where'            => [
			['contact_id',              '=',  $contact_id],
			['event_id.event_type_id',  'IN', $all_relevant_types_leid],
			['status_id',               'IN', $all_relevant_status_leid],
		],
	];

	wachthond($extdebug, 7, 'params_combined_leid', $params_combined_leid);
	$result_combined_leid = civicrm_api4('Participant', 'get', $params_combined_leid);
	wachthond($extdebug, 9, 'result_combined_leid', $result_combined_leid);

	$evtcv_leid_array      = [];
	$evtcv_staf_array      = [];

	foreach ($result_combined_leid as $part) {
		$year              = date('Y', strtotime($part['event_id.start_date']));
		$status            = (int)$part['status_id'];
		$part_welkkamp     = $part['PART_LEID.Welk_kamp']  ?? '';
		$part_functie      = $part['PART_LEID.Functie']    ?? '';

		// Exclusie-logica: Staf, Bestuur en Kampstaf tellen NIET mee voor het reguliere Leiding-CV.
		$is_staf = ($part_welkkamp == 'bestuur' || $part_welkkamp == 'kampstaf' || $part_functie == 'bestuurslid' || $part_functie == 'kampstaf' || $part_functie == 'deelnemer');

		if (in_array($status, $status_positive)) {
			if (!$is_staf) { $evtcv_leid_array[] = $year; }
			if ($part_welkkamp == 'kampstaf') { $evtcv_staf_array[] = $year; }
		}
	}

	$evtcv_leid_array = array_values(array_unique($evtcv_leid_array));
	sort($evtcv_leid_array);

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 4.0 LOGICA CONSOLIDATIE (HANDMATIG + HISTORIE)",   "[CONSOLIDATE]");
	wachthond($extdebug, 2, "########################################################################");

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 4.1 LOGICA CONSOLIDATIE DEELNEMER",                       "[DEEL]");
	wachthond($extdebug, 2, "########################################################################");

	// Combineer handmatige input met de gevonden database events.
	$base_deel_array     = ($form_cv_deel_array !== NULL) ? $form_cv_deel_array : $db_cv_deel_array;
	$maxcv_deel_array    = array_unique(array_merge($base_deel_array, $evtcv_deel_array));
	
	// Verwerk 'Dit Jaar' status uit het intake-scherm.
	$ditevent_kampjaar   = date("Y");
	if (($ditjaar_array['ditjaardeelyes'] ?? 0) == 1) { $maxcv_deel_array[] = $ditevent_kampjaar; }
	else { $maxcv_deel_array = array_diff($maxcv_deel_array, [$ditevent_kampjaar]); }

	// GENERATIE-REGEL: Voor deelnemers geboren na 2007 is de database ALTIJD leidend.
	if ($birth_date > "2007-08-01") { $cv_deel_array_final = array_values(array_unique(array_filter($evtcv_deel_array))); }
	else { $cv_deel_array_final = array_values(array_unique(array_filter($maxcv_deel_array))); }
	sort($cv_deel_array_final);

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 4.2 LOGICA CONSOLIDATIE LEIDING",                         "[LEID]");
	wachthond($extdebug, 2, "########################################################################");

	$base_leid_array     = ($form_cv_leid_array !== NULL) ? $form_cv_leid_array : $db_cv_leid_array;
	$clean_evt_leid      = array_diff($evtcv_leid_array, $evtcv_staf_array);
	$temp_merge          = array_unique(array_merge($base_leid_array, $clean_evt_leid));
	$eerste_leid_jaar    = !empty($temp_merge) ? min($temp_merge) : 9999;

	// GENERATIE-REGEL: Voor leiding (geboren na 1995 OF nieuw ingestroomd vanaf 2012) is de database leidend.
	if ($birth_date > "1995-08-01" || $eerste_leid_jaar >= 2012) { $cv_leid_array_final = array_values(array_unique($clean_evt_leid)); }
	else { $cv_leid_array_final = array_values(array_unique(array_merge($base_leid_array, $clean_evt_leid))); }
	sort($cv_leid_array_final);

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 5.0 BEREKENING VERSCHILLEN & OUTPUT PREPARATIE",      	  "[CALC]");
	wachthond($extdebug, 2, "########################################################################");

	$keren_deel   = count($cv_deel_array_final);
	$keren_leid   = count($cv_leid_array_final);
	$totaal_mee   = $keren_deel + $keren_leid;
	$keren_top    = count($evtcv_deel_top_array);

	// Converteer arrays naar technische strings voor CiviCRM
	$cv_deel_str  = !empty($cv_deel_array_final) ? "\x01" . implode("\x01", $cv_deel_array_final) . "\x01" : null;
	$cv_leid_str  = !empty($cv_leid_array_final) ? "\x01" . implode("\x01", $cv_leid_array_final) . "\x01" : null;
	$evtcv_deel   = !empty($evtcv_deel_array)    ? "\x01" . implode("\x01", $evtcv_deel_array)    . "\x01" : null;
	$evtcv_leid   = !empty($evtcv_leid_array)    ? "\x01" . implode("\x01", $evtcv_leid_array)    . "\x01" : null;

	// VOORKOM VALUEERROR: Voeg controle toe voor min() en max()
	$all_years    = array_merge($cv_deel_array_final, $cv_leid_array_final);
	$eerste_keer  = !empty($all_years) ? min($all_years) : NULL;
	$laatste_keer = !empty($all_years) ? max($all_years) : NULL;	

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 6.0 ARRAYS BOUWEN VOOR RETURN",                         "[ARRAYS]");
	wachthond($extdebug, 2, "########################################################################");

	$data_cv = [
		'Curriculum.Totaal_keren_mee'     => $totaal_mee,
		'Curriculum.Eerste_keer'          => $eerste_keer,
		'Curriculum.Laatste_keer'         => $laatste_keer,

		// GEEF DIRECT DE ARRAYS DOOR (Base.php doet de formatting!)
		'Curriculum.CV_Deel'              => $cv_deel_array_final,
		'Curriculum.Keren_Deel'           => $keren_deel,
		'Curriculum.Eerste_deel'          => $cv_deel_array_final[0] ?? NULL,
		'Curriculum.Laatste_deel'         => end($cv_deel_array_final) ?: NULL,
		'Curriculum.EventCV_Deel'         => $evtcv_deel_array,
		'Curriculum.EventTotaal_Deel'     => count($evtcv_deel_array),
		'Curriculum.Eventverschil_Deel'   => count($evtcv_deel_array) - count($db_cv_deel_array),

		'Curriculum.Keren_Topkamp'        => $keren_top,

		// GEEF DIRECT DE ARRAYS DOOR
		'Curriculum.CV_Leid'              => $cv_leid_array_final,
		'Curriculum.Keren_Leid'           => $keren_leid,
		'Curriculum.Eerste_leid'          => $cv_leid_array_final[0] ?? NULL,
		'Curriculum.Laatste_leid'         => end($cv_leid_array_final) ?: NULL,
		'Curriculum.EventCV_Leid'         => $evtcv_leid_array,
		'Curriculum.EventTotaal_Leid'     => count($evtcv_leid_array),
		'Curriculum.Eventverschil_Leid'   => count($evtcv_leid_array) - count($db_cv_leid_array),

		'Curriculum.CV_deel_text_'        => implode(", ", $cv_deel_array_final),
		'Curriculum.CV_leid_text_'        => implode(", ", $cv_leid_array_final),
	];

	wachthond($extdebug, 3, "data_cv", $data_cv);

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 7.0 OPSLAAN VOOR $displayname",                       	  "[SAVE]");
	wachthond($extdebug, 2, "########################################################################");

	/**
	 * UPDATE STRATEGIE:
	 * De rekenmachine hanteert twee routes voor het opslaan van de data:
	 * 1. 'hook': De data wordt geretourneerd aan de aanroepende functie (bijv. cv_civicrm_customPre).
	 * Daar wordt het via base_inject_params() in de $params gezet, zodat CiviCRM het in de
	 * lopende database-transactie meeneemt. Dit voorkomt een extra database-hit.
	 * 2. 'direct': De data wordt onmiddellijk naar de database geschreven via base_api_wrapper().
	 * Dit wordt gebruikt bij standalone aanroepen buiten formulieren om (cron/API).
	 */

	if ($context === 'direct' && !empty($data_cv)) {
		
		wachthond($extdebug, 1, "### UPDATE STRATEGIE: API CALL",        "[FLOW]");
		$res_cv = base_api_wrapper('Contact', $contact_id, $data_cv, "CV_API");

		if ($res_cv === false) {
			wachthond($extdebug, 1, "API-WRAPPER CV update gefaald",      "ERROR");
		} elseif (isset($res_cv['status']) && $res_cv['status'] == 'skipped') {
			wachthond($extdebug, 2, "API-WRAPPER CV geen wijzigingen",    "SKIPPED");
		} else {
			wachthond($extdebug, 2, "API-WRAPPER CV bijgewerkt",          "SUCCES");
		}
	} else {
		wachthond($extdebug, 1, "### UPDATE STRATEGIE: RETOUR VOOR HOOK", "[FLOW]");
	}

	$processing_cv_configure = FALSE;
	
	// Geef de data altijd terug, zodat de customPre hook het kan injecteren
	return $data_cv;
}

function cv_civicrm_install()   { return _cv_civix_civicrm_install();   }
function cv_civicrm_uninstall() { return _cv_civix_civicrm_uninstall(); }
function cv_civicrm_enable()    { return _cv_civix_civicrm_enable();    }
function cv_civicrm_disable()   { return _cv_civix_civicrm_disable();   }