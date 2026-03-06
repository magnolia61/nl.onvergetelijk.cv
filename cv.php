<?php

require_once 'cv.civix.php';

/**
 * Hook om CV herberekening te triggeren bij profiel edit.
 * Beveiligd tegen recursie via $processing_cv_pre.
 */
function cv_civicrm_customPre($op, $groupID, $entityID, &$params) {

	// 1. Configuratie en Variabelen
    $extdebug       = 0;
    $profilecontcv  = array(103);
    
    // Statische variabele als slot (voorkomt infinite loops in hooks)
    static $processing_cv_pre = FALSE;

    // 2. Recursion Check & Operation Check
    // Functioneel: Stop als we al bezig zijn met dit proces (loop preventie) OF als het geen 'edit' is.
    // Technisch  : Check static variabele $processing_cv_pre en $op variabele.
    if ( $op !== 'edit' || $processing_cv_pre ) {
        // Geen log hier om de logfiles schoon te houden bij elke aanroep die niet relevant is
        return;
    }

    // 3. Whitelist Check: Is dit een relevante groep?
    // Functioneel: Als de huidige groupID niet in de whitelist staat ($profilecontcv), stoppen we.
    // Technisch  : !in_array check.
    if ( ! in_array( $groupID, $profilecontcv ) ) {
        
        // Optioneel: loggen dat we skippen voor debugging (nu veilig omdat $extdebug al bestaat)
        // wachthond($extdebug, 1, "--- Skipping: GroupID $groupID is geen target ---", "[SKIP]");
        
        return;
    }

    // Als we hier zijn, gaan we door. Zet het slot aan.
    $processing_cv_pre = TRUE;
    
    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### HOOK [PRE] CUSTOM TRIGGER VOOR CONTACT: $entityID", "[CUSTOMPRE]");
    wachthond($extdebug, 2, "########################################################################");

    // Zet het slot erop
    $processing_cv_pre = TRUE;
   
	// Roep configure aan en geef $params BY REFERENCE mee
    // We geven ook de context 'hook_edit' mee zodat de functie weet wat hij moet doen
    cv_civicrm_configure($entityID, NULL, NULL, $params, 'hook_edit');

    // Slot er weer af voor eventuele volgende contacten in hetzelfde proces
    $processing_cv_pre = FALSE;
}

/**
 * Functie om het CV van een contactpersoon te berekenen en te corrigeren.
 * * DEFINITIEVE VERSIE (29-01-2026):
 * - Arrays blijven arrays (voor compatibiliteit met acl.php).
 * - PHP 8 Type Error fix voor fee_level.
 * - Single-Pass queries voor snelheid.
 * - Return array exact volgens specificatie.
 */

function cv_civicrm_configure($contactid, $array_contact = NULL, $ditjaar_array = NULL, &$params = [], $context = 'direct') {

	// --- RECURSIE BEVEILIGING (HET SLOT) ---
    // We gebruiken de door jou gekozen naam om aan te geven dat we bezig zijn.
    static $processing_cv_configure = FALSE;

    if ($processing_cv_configure) {
        // We zijn al bezig met een berekening voor dit process.
        // Dit gebeurt als de API update (verderop) de hook triggert, die deze functie weer aanroept.
        // We stoppen direct om een infinite loop te voorkomen.
        return;
    }

    // Zet het slot erop
    $processing_cv_configure = TRUE;


	if (empty($contactid)) return;
	$contact_id 		= $contactid;

	// Start de overkoepelende timer voor de CV module
	core_microtimer("Start CV Analyse");

	$ditjaar_array 		= $ditjaar_array ?? []; 

	$extdebug   		= 0; // Debug op 3 voor uitgebreide Watchdog logging
	$apidebug   		= FALSE;

	// Als de contact array leeg is, halen we deze zelf op
    if (empty($array_contact)) {
        $array_contact 	= base_cid2cont($contact_id);
    }
    wachthond($extdebug, 4, "array_contditjaar voor $displayname", $array_contact);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 0.X CONFIGURATIE & STATUS IDS OPHALEN");
	wachthond($extdebug,3, "########################################################################");
	
	$status_data 		= function_exists('find_partstatus') ? find_partstatus() : NULL;
    $status_positive    = $status_data['ids']['Positive']   ?? [];
    $status_pending     = $status_data['ids']['Pending']    ?? [];
    $status_waiting     = $status_data['ids']['Waiting']    ?? [];
    $status_negative    = $status_data['ids']['Negative']   ?? [];

	$eventtypes 		= get_event_types(); 
	$eventtypesdeel     = $eventtypes['deel'] 				?? [];
	$eventtypesdeeltop  = $eventtypes['deeltop'] 			?? [];
	$eventtypesleid     = $eventtypes['leid'] 				?? [];

	$birth_date  		= $array_contact['birth_date'] 		?? NULL;
	$displayname 		= $array_contact['displayname'] 	?? "Contact $contact_id";

	wachthond($extdebug, 1, "CV Config geladen voor $displayname. Status IDs (Positief): " . implode(',', $status_positive));

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 1.X OPHALEN DEELNEMER DATA (DEEL, TOP & CANCEL)");
	wachthond($extdebug,3, "########################################################################");

	$all_relevant_types_deel = array_merge($eventtypesdeel, $eventtypesdeeltop);
	$all_relevant_status     = array_unique(array_merge($status_positive, [4])); 

	$params_combined_deel = [
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
		'select' => [
			'event_id.start_date', 
			'status_id', 
			'event_id.event_type_id'
		],
		'where' => [
			['contact_id', 				'=',  $contact_id],
			['event_id.event_type_id', 	'IN', $all_relevant_types_deel],
			['status_id', 				'IN', $all_relevant_status],
			['is_test', 				'IN', [TRUE, FALSE]],
		],
	];

	wachthond($extdebug, 7, 'params_combined_deel', $params_combined_deel);
	$result_combined_deel = civicrm_api4('Participant', 'get', $params_combined_deel);
	wachthond($extdebug, 9, 'result_combined_deel', $result_combined_deel);

	$evtcv_deel_array     = []; 
	$evtcv_deel_top_array = []; 
	$cancv_deel_array     = []; 

	foreach ($result_combined_deel as $part) {
		$year   = date('Y', strtotime($part['event_id.start_date']));
		$status = (int)$part['status_id'];
		$type   = (int)$part['event_id.event_type_id'];

		if (in_array($status, $status_positive)) {
			if (in_array($type, $eventtypesdeel)) 	 $evtcv_deel_array[] 	 = $year;
			if (in_array($type, $eventtypesdeeltop)) $evtcv_deel_top_array[] = $year;
		} elseif ($status === 4 && in_array($type, $eventtypesdeel)) {
			$cancv_deel_array[] = $year;
		}
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 1.4 OPHALEN TAGS DEELNEMERS");
	wachthond($extdebug,3, "########################################################################");

	$params_tags_deel = [
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
		'select' => [
			'row_count', 
			'tag.id', 
			'tag.name', 
			'tag.description', 
			'tag.parent_id:label',
		],
		'join' => [
			['Tag AS tag', TRUE, 'EntityTag'],
		],
		'where' => [
			['id', 				'=', $contact_id], 
			['tag.parent_id', 	'=', 37], 
			['tag.name', 		'LIKE', 'D%'],
		],
	];

	wachthond($extdebug,7, 'params_tags_deel', 	 		$params_tags_deel);
	$result_tags_deel = civicrm_api4('Contact','get', 	$params_tags_deel);
	wachthond($extdebug,9, 'result_tags_deel', 			$result_tags_deel);

	wachthond($extdebug, 1, core_microtimer("Sectie 1 (Deelnemer data & Tags) afgehandeld"));

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.X LOGICA DEELNEMER (GENERATIE & HUIDIG JAAR)");
	wachthond($extdebug,3, "########################################################################");

	$curcv_deel_array = $array_contact['curcv_deel_array'] ?? [];
	$oldcv_deel_array = $array_contact['oldcv_deel_array'] ?? [];
	
	// Logica 2.1: Generatiecheck
	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.1 GEBRUIK EVENT CV DEEL VOOR REGISTRATIES NA 2007", "$displayname");
	wachthond($extdebug,3, "########################################################################");

	if ($birth_date > "2007-08-01") {
		$maxcv_deel_array = $evtcv_deel_array;
		wachthond($extdebug, 3, "Birthdate >2007 ($birth_date)", "(event_cv is leidend)");
	} else {
		$maxcv_deel_array = array_unique(array_merge($oldcv_deel_array, $curcv_deel_array, $evtcv_deel_array));
		wachthond($extdebug, 3, "Birthdate <2007 ($birth_date)", "(event_cv niet leidend)");
	}

	// Logica 2.3: Huidig jaar
	$ditevent_kampjaar = date("Y"); 
	if (($ditjaar_array['ditjaardeelyes'] ?? 0) == 1) {
		$maxcv_deel_array[] = $ditevent_kampjaar;
	} else {
		$maxcv_deel_array = array_diff($maxcv_deel_array, [$ditevent_kampjaar]);
	}
	
	sort($maxcv_deel_array);
	$maxcv_deel_nr = count($maxcv_deel_array);
	$curcv_deel_nr = count($curcv_deel_array);
	$deel_nr_diff  = abs($maxcv_deel_nr - $curcv_deel_nr);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.4 BEPAAL OF CURRENT CV MOET WORDEN OVERSCHREVEN");
	wachthond($extdebug,3, "########################################################################");

	$cv_deel_array_final = $curcv_deel_array; 

	if ($birth_date > "2007-08-01") {
		$cv_deel_array_final = $maxcv_deel_array;
	} elseif (($maxcv_deel_nr > $curcv_deel_nr) OR ($maxcv_deel_nr < $curcv_deel_nr AND $deel_nr_diff == 1)) {
		wachthond($extdebug,2, "DOE UPDATE DEEL CV 1 want maxcv_deel_nr ($maxcv_deel_nr) differs with $deel_nr_diff from curcv_deel_nr", "$curcv_deel_nr");
		$cv_deel_array_final = $maxcv_deel_array;
	} else {
		wachthond($extdebug,2, "GEEN UPDATE van DEEL CV want zou wijzigen met $deel_nr_diff t.o.v. curcv_deel_nr", "$curcv_deel_nr");
		$cv_deel_array_final = $curcv_deel_array;
	}

	wachthond($extdebug, 2, "########################################################################");
	wachthond($extdebug, 1, "### CV 3.X OPHALEN LEIDING DATA (LEID, STAF, BESTUUR & CANCEL)", "[LEIDING]");
	wachthond($extdebug, 2, "########################################################################");

	$params_combined_leid = [
		'checkPermissions' => FALSE,
		'debug'            => $apidebug,
		'select'           => [
			'event_id.start_date', 
			'status_id', 
			'role_id:name',
			'PART_LEID.Welk_kamp',
			'PART_LEID.Functie'
		],
		'where'            => [
			['contact_id',              '=',  $contact_id],
			['event_id.event_type_id',  'IN', $eventtypesleid],
			['status_id',               'IN', $all_relevant_status],
//			['role_id:name',            'IN', ['Hoofdleiding', 'Leiding', 'Leiding Topkamp']],
//			['is_test',                 'IN', [TRUE, FALSE]],
		],
	];

	wachthond($extdebug, 7, 'params_combined_leid', 			$params_combined_leid);
	$result_combined_leid = civicrm_api4('Participant','get', 	$params_combined_leid);
	wachthond($extdebug, 9, 'result_combined_leid', 			$result_combined_leid);

	$evtcv_leid_array      = []; 
	$cancv_leid_array      = []; 
	$evtcv_staf_array      = []; 
	$evtcv_best_array      = []; 

	foreach ($result_combined_leid as $part) {
		$year              = date('Y', strtotime($part['event_id.start_date']));
		$status            = (int)$part['status_id'];
		
		$part_welkkamp     = $part['PART_LEID.Welk_kamp']  ?? '';
		$part_functie      = $part['PART_LEID.Functie']    ?? '';

		// --- FILTER CRITERIA VOOR UITSLUITING ---
		// Bestuur, kampstaf en deelnemers tellen nooit mee voor de reguliere leidingjaren
		$is_staf           = (
			$part_welkkamp == 'bestuur'    	|| 
			$part_welkkamp == 'kampstaf'   	|| 
			$part_functie  == 'bestuurslid' || 
			$part_functie  == 'kampstaf'   	||
			$part_functie  == 'deelnemer'
		);

		// LOGICA: Alleen tellen bij positieve statussen (geen pending/waiting)
		if (in_array($status, $status_positive)) {
			
			// Voeg jaar toe aan leiding-array mits de persoon geen staf-rol/functie had
			if (!$is_staf) {
				$evtcv_leid_array[] = $year;
			}

			// Specifieke vulling voor staf/bestuur arrays op basis van welkkamp
			if ($part_welkkamp == 'kampstaf') {
				$evtcv_staf_array[] = $year;
			}
			if ($part_welkkamp == 'bestuur') {
				$evtcv_best_array[] = $year;
			}

		} elseif (in_array($status, $status_negative)) {
			// Annuleringen op basis van de negatieve status-groep
			$cancv_leid_array[] = $year;
		}
	}

	// Filter dubbele jaren en sorteer de resultaten
	$evtcv_leid_array = array_values(array_unique($evtcv_leid_array));
	$evtcv_staf_array = array_values(array_unique($evtcv_staf_array));
	$evtcv_best_array = array_values(array_unique($evtcv_best_array));
	sort($evtcv_leid_array);

	// --- VASTGESTELDE WAARDEN LOGGEN ---
	wachthond($extdebug, 2, "### RESULTATEN CV ANALYSE VOOR $displayname:");
	wachthond($extdebug, 2, "AANTAL LEIDING JAREN:", count($evtcv_leid_array));
	wachthond($extdebug, 2, "JAREN LEIDING:",     	implode(', ', $evtcv_leid_array) ?: 'Geen');
	wachthond($extdebug, 2, "JAREN KAMPSTAF:",    	implode(', ', $evtcv_staf_array) ?: 'Geen');
	wachthond($extdebug, 2, "JAREN BESTUUR:",       implode(', ', $evtcv_best_array) ?: 'Geen');
	wachthond($extdebug, 2, "AANTAL GEANNULEERD:",  count($cancv_leid_array));

	wachthond($extdebug, 1, core_microtimer("Sectie 3 (Leiding: alleen positief & role-check) afgehandeld"));

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.X LOGICA LEIDING (STAF-REDUCTIE & GENERATIE)");
	wachthond($extdebug,3, "########################################################################");

	$evtcv_leid_array = array_diff($evtcv_leid_array, $evtcv_staf_array);
	$curcv_leid_array = $array_contact['curcv_leid_array'] ?? [];
	$maxcv_leid_array = array_unique(array_merge($curcv_leid_array, $evtcv_leid_array));

	$eerste_leid_jaar = !empty($maxcv_leid_array) ? min($maxcv_leid_array) : 9999;
	if ($birth_date > "1995-08-01" || $eerste_leid_jaar >= 2013) {
		$maxcv_leid_array = $evtcv_leid_array;
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 5.1 BEREKENEN VERSCHILLEN (DIF)");
	wachthond($extdebug,3, "########################################################################");

	$eventverschildeel 	= count($evtcv_deel_array) - count($curcv_deel_array);
	$eventverschilleid 	= count($evtcv_leid_array) - count($curcv_leid_array);

	$not_in_db = array_diff($curcv_leid_array, array_merge($evtcv_leid_array, $evtcv_staf_array, $cancv_leid_array));
	foreach ($not_in_db as $missing_year) {
		wachthond($extdebug, 1, "FUNCTIONEEL: Historisch record nodig in DB voor jaar $missing_year");
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 6.0 FINALE BEREKENING & RETURN");
	wachthond($extdebug,3, "########################################################################");

	$maxcv_deel_array 	= array_values(array_unique(array_filter($cv_deel_array_final)));
	$maxcv_leid_array 	= array_values(array_unique(array_filter($maxcv_leid_array)));
	sort($maxcv_deel_array); 
	sort($maxcv_leid_array);

	// Totalen
	$keren_deel 		= count($maxcv_deel_array);
	$keren_leid 		= count($maxcv_leid_array);
	$totaal_mee 		= $keren_deel + $keren_leid;
    $keren_top  		= count($evtcv_deel_top_array);

	// Formatteer Strings (voor database velden, met CTRL-A separator)
	$cv_deel 			= !empty($maxcv_deel_array) ? "\x01" . implode("\x01", $maxcv_deel_array) . "\x01" : null;
	$cv_leid 			= !empty($maxcv_leid_array) ? "\x01" . implode("\x01", $maxcv_leid_array) . "\x01" : null;
	
    // Formatteer Database Strings (voor evtcv velden)
    $evtcv_deel 		= !empty($evtcv_deel_array) ? "\x01" . implode("\x01", $evtcv_deel_array) . "\x01" : null;
    $evtcv_leid 		= !empty($evtcv_leid_array) ? "\x01" . implode("\x01", $evtcv_leid_array) . "\x01" : null;

	// Leesbare Tekst
	$maxcv_deel_text 	= implode(", ", $maxcv_deel_array);
	$maxcv_leid_text 	= implode(", ", $maxcv_leid_array);

    // Map de interne arrays naar de gevraagde output namen
    // BELANGRIJK: We geven hier ARRAYS terug, zodat acl.php kan rekenen.
    $evtcv_meet_array 	= $evtcv_staf_array;
    $evtcv_toer_array 	= $evtcv_best_array;

    // Nummers
    $evtcv_deel_nr 		= count($evtcv_deel_array);
    $evtcv_leid_nr 		= count($evtcv_leid_array);

	// Uitersten
	$eerste_deel  		= $maxcv_deel_array[0] 			?? NULL;
	$laatste_deel 		= !empty($maxcv_deel_array) 	? end($maxcv_deel_array) 	 : NULL;
	$eerste_leid  		= $maxcv_leid_array[0] 			?? NULL;
	$laatste_leid 		= !empty($maxcv_leid_array) 	? end($maxcv_leid_array) 	 : NULL;
	$eerste_top   		= $evtcv_deel_top_array[0] 		?? NULL;
	$laatste_top  		= !empty($evtcv_deel_top_array) ? end($evtcv_deel_top_array) : NULL;

	$jaren_totaal 		= array_unique(array_merge($maxcv_deel_array, $maxcv_leid_array));
	sort($jaren_totaal);
	$eerste_keer  		= $jaren_totaal[0] ?? NULL;
	$laatste_keer 		= !empty($jaren_totaal) ? end($jaren_totaal) : NULL;

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 7.0 DETECTIE WIJZIGINGEN");
	wachthond($extdebug,3, "########################################################################");

	$extwrite_cv 		= 0;
	$db_cv_deel 		= "\x01" . implode("\x01", $curcv_deel_array) . "\x01";
	$db_cv_leid 		= "\x01" . implode("\x01", $curcv_leid_array) . "\x01";

	if ($cv_deel !== $db_cv_deel || $cv_leid !== $db_cv_leid) {
		$extwrite_cv 	= 1;
		wachthond($extdebug, 1, "WIJZIGING GEDETECTEERD: Nieuw CV wordt naar database geschreven.");
	} else {
		wachthond($extdebug, 1, "GEEN WIJZIGING: Database-update voor CV overgeslagen.");
	}

	$cv_array = array(
        'keren_deel'        => $keren_deel,
        'keren_leid'        => $keren_leid,
        'keren_top'         => $keren_top,
        'totaal_mee'        => $totaal_mee,

        'cv_deel'           => $cv_deel,
        'cv_leid'           => $cv_leid,

        'eerste_deel'       => $eerste_deel,
        'eerste_leid'       => $eerste_leid,
        'eerste_top'        => $eerste_top,        

        'laatste_deel'      => $laatste_deel,
        'laatste_leid'      => $laatste_leid,
        'laatste_top'       => $laatste_top,

        'eerste_keer'       => $eerste_keer,
        'laatste_keer'      => $laatste_keer,

        'cv_deel_text'      => $maxcv_deel_text,
        'cv_leid_text'      => $maxcv_leid_text,        

        'evtcv_deel'        => $evtcv_deel,
        'evtcv_leid'        => $evtcv_leid,

        'evtcv_deel_nr'     => $evtcv_deel_nr,
        'evtcv_leid_nr'     => $evtcv_leid_nr,

        'evtcv_deel_dif'    => $eventverschildeel,        
        'evtcv_leid_dif'    => $eventverschilleid,

        'evtcv_meet_array'  => $evtcv_meet_array,
        'evtcv_toer_array'  => $evtcv_toer_array,
  	);

	wachthond($extdebug, 3, "cv_array", 	$cv_array);

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### INTAKE CV 7.0 OPSLAAN DATA (CONTEXT: $context)");
    wachthond($extdebug, 2, "########################################################################");

    // A. INJECTIE MAPPING (Database Kolomnamen)
    // Nodig voor: intake_inject_params (Direct in de hook)
    $inject_map_cont = [
        // Algemeen
        'totaal_keren_mee_458'    => $totaal_mee,
        'eerste_keer_846'         => $eerste_keer,
        'laatste_keer_847'        => $laatste_keer,

        // Deelnemer
        'cv_deel_1435'            => $cv_deel,
        'keren_deel_1437'         => $keren_deel,
        'eerste_deel_842'         => $eerste_deel,
        'laatste_deel_843'        => $laatste_deel,
        'eventcv_deel_1209'       => $evtcv_deel,
        'eventtotaal_deel_1001'   => $evtcv_deel_nr,
        'eventverschil_deel_1110' => $eventverschildeel,

        // Topkamp
        'keren_topkamp_1027'      => $keren_top,
        'eerste_topkamp_1053'     => $eerste_top,
        'laatste_topkamp_1054'    => $laatste_top,

        // Leiding
        'cv_leid_1436'            => $cv_leid,
        'keren_leid_1438'         => $keren_leid,
        'eerste_leid_844'         => $eerste_leid,
        'laatste_leid_845'        => $laatste_leid,
        'eventcv_leid_1210'       => $evtcv_leid,
        'eventtotaal_leid_1002'   => $evtcv_leid_nr,
        'eventverschil_leid_1112' => $eventverschilleid,

        // Tekst
		'cv_deel_text__1486'      => $maxcv_deel_text,
        'cv_leid_text__1487'      => $maxcv_leid_text,
    ];

    // B. API DATA (Friendly Names)
    // Nodig voor: intake_api_update_wrapper (Update achteraf)
    $api_data_cont = [
        'Curriculum.Eerste_keer'        => $eerste_keer        ?? NULL,
        'Curriculum.Laatste_keer'       => $laatste_keer       ?? NULL,
        'Curriculum.Totaal_keren_mee'   => $totaal_mee         ?? NULL,

        'Curriculum.CV_Deel'            => $cv_deel            ?? NULL,
        'Curriculum.Keren_Deel'         => $keren_deel         ?? NULL,
        'Curriculum.Eerste_deel'        => $eerste_deel        ?? NULL,
        'Curriculum.Laatste_deel'       => $laatste_deel       ?? NULL,
        'Curriculum.EventCV_Deel'       => $evtcv_deel         ?? NULL,
        'Curriculum.EventTotaal_Deel'   => $evtcv_deel_nr      ?? NULL,
        'Curriculum.Eventverschil_Deel' => $eventverschildeel  ?? NULL,

        'Curriculum.Keren_Topkamp'      => $keren_top          ?? NULL,
        'Curriculum.Eerste_Topkamp'     => $eerste_top         ?? NULL,
        'Curriculum.Laatste_Topkamp'    => $laatste_top        ?? NULL,

        'Curriculum.CV_Leid'            => $cv_leid            ?? NULL,
        'Curriculum.Keren_Leid'         => $keren_leid         ?? NULL,
        'Curriculum.Eerste_leid'        => $eerste_leid        ?? NULL,
        'Curriculum.Laatste_leid'       => $laatste_leid       ?? NULL,
        'Curriculum.EventCV_Leid'       => $evtcv_leid         ?? NULL,
        'Curriculum.EventTotaal_Leid'   => $evtcv_leid_nr      ?? NULL,
        'Curriculum.Eventverschil_Leid' => $eventverschilleid  ?? NULL,

        'Curriculum.CV_deel_text_'      => $maxcv_deel_text    ?? NULL,
        'Curriculum.CV_leid_text_'      => $maxcv_leid_text    ?? NULL,
    ];

    // C. INITIALISATIE & SCAN (Nodig omdat params hier een complexe array is)
    // We vertalen de structuur [0] => ['column_name' => 'X'] naar ['X' => 0]
    $keys = [];
    if (is_array($params)) {
        foreach ($params as $index => $item) {
            if (isset($item['column_name']) && !empty($item['column_name'])) {
                $keys[$item['column_name']] = $index;
            }
        }
    }

	// D. VERWERKING: KEUZE STRATEGIE
    // Functioneel: Bepaal of we data direct in de lopende stroom aanpassen of via een aparte opslagactie.
    // Technisch  : Check op context 'pre'/'edit' EN aanwezigheid van keys.
    
    // Boolean flag voor leesbaarheid
    $is_hook_context    = ( $context == 'hook_pre' || $context == 'hook_edit' || $context == 'hook_cont' );
    $fields_are_present = ( ! empty($keys) );

    if ( $is_hook_context && $fields_are_present ) {

        // 1. VIA PARAMS INJECTIE
        // Functioneel: De velden staan op het scherm/in de submit. We passen de waarden 'in-flight' aan.
        // Dit is de snelste methode en voorkomt dubbele saves.
        wachthond($extdebug, 1, "### UPDATE STRATEGIE: PARAMS INJECTIE (Context: $context)", "[FLOW]");
        
        intake_inject_params($params, $keys, $inject_map_cont, "CV_HOOK");

    } else {

        // 2. VIA API UPDATE (FALLBACK / DIRECT)
        // Functioneel: De velden staan NIET in de submit (hidden fields) of we worden aangeroepen vanuit cron/script.
        // We forceren een update via de API.
        wachthond($extdebug, 1, "### UPDATE STRATEGIE: API CALL (Context: $context)",        "[FLOW]");

        if ( $contact_id ) {
            intake_api_update_wrapper('Contact', $contact_id, $api_data_cont, $extdebug);
        } else {
            wachthond($extdebug, 1, "!!! LET OP: Geen Contact ID beschikbaar voor API update !!!", "[ERROR]");
        }
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### INTAKE [PRE] DEFINITIEVE WAARDEN PARAMS VOOR $displayname","[PARAMS]");
    wachthond($extdebug,2, "########################################################################");

    drupal_timestamp_sweep($params);

    wachthond($extdebug, 3, "params", $params);

	wachthond($extdebug, 1, "### CV ANALYSE VOLTOOID: " . core_microtimer("Einde"));
	wachthond($extdebug, 3, "########################################################################");

	// --- SLOT ERAF ---
    // Dit blok wordt ALTIJD uitgevoerd, ook na een return of error.
    $processing_cv_configure = FALSE;

	return $cv_array;
}

function cv_civicrm_install()   { return _cv_civix_civicrm_install();   }
function cv_civicrm_uninstall() { return _cv_civix_civicrm_uninstall(); }
function cv_civicrm_enable()    { return _cv_civix_civicrm_enable();    }
function cv_civicrm_disable()   { return _cv_civix_civicrm_disable();   }