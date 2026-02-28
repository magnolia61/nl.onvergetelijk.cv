<?php

require_once 'cv.civix.php';


/**
 * This example compares the submitted value of a field with its current value.
 *
 * @param string $op
 *   The type of operation being performed.
 * @param int $groupID
 *   The custom group ID.
 * @param int $entityID
 *   The entityID of the row in the custom table.
 * @param array $params
 *   The parameters that were sent into the calling function.
 */

function cv_civicrm_configure($contactid, $array_contact = NULL, $ditjaar_array = NULL) {

	if (empty($contactid)) {
		return;
	} else {
		$contact_id = $contactid;
	}

	$array_contact = $array_contact ?? []; // Initialize as empty array if null
	$ditjaar_array = $ditjaar_array ?? []; // Initialize as empty array if null	

	$extdebug			= 0; 	// 	1 = basic // 2 = verbose // 3 = params / 4 = results
	$apidebug			= FALSE;

	// We halen de config op uit base.php
    $eventtypes 		= get_event_types(); 

    // 1. Map de waarden naar de variabelen die je queries verderop nodig hebben
    $eventtypesdeel     = $eventtypes['deel'];      // Wordt gebruikt in 'params_part_deel'
    $eventtypesdeeltop  = $eventtypes['deeltop'];   // Wordt gebruikt in 'params_part_deel_top'
    $eventtypesleid     = $eventtypes['leid'];      // Wordt gebruikt in 'params_part_leid'
    $eventtypesmeet     = $eventtypes['meet'];      // Wordt gebruikt in 'params_part_meet'
    $eventtypestoer     = $eventtypes['toer'];      // Wordt gebruikt in 'params_part_toer'

    // 2. Haal de gecombineerde lijsten eruit die we nodig hebben voor logica
    $eventtypesdeelall  = $eventtypes['deel_all'];
    $eventtypesleidall  = $eventtypes['leid_all'];

	// Initialiseer variabelen die later in $params_contact worden gebruikt
    $eventverschildeel 		= 0;
    $eventverschilleid 		= 0;
    $evtcv_deel_dif    		= 0;
    $evtcv_leid_dif    		= 0;
	$eerste_keer   			= '';
	$laatste_keer  			= '';
    $totaal_mee        		= 0;
    $cv_deel           		= '';
    $cv_leid           		= '';
    $keren_deel        		= 0;
    $keren_leid        		= 0;
    $keren_top         		= 0;
	$eerste_top  			= '';
	$laatste_top 			= '';
    $eerste_deel    		= '';
    $eerstexdeel    		= '';
    $laatstexdeel   		= '';
    $eerste_leid    		= '';
    $eerstexleid    		= '';
    $laatstexleid   		= '';	

	wachthond($extdebug,1, "########################################################################");
	wachthond($extdebug,1, "### CV 1.X START REPAIR CURRICULUM (KAMPCV) DEEL & LEID",    $displayname);
	wachthond($extdebug,1, "########################################################################");

    $status_positive    	= Civi::cache()->get('cache_status_positive');
    $status_pending     	= Civi::cache()->get('cache_status_pending');
    $status_waiting     	= Civi::cache()->get('cache_status_waiting');
    $status_negative    	= Civi::cache()->get('cache_status_negative');

    wachthond($extdebug,4, 'statusids_positive',  $status_positive);
    wachthond($extdebug,4, 'statusids_pending',   $status_pending);
    wachthond($extdebug,4, 'statusids_waiting',   $status_waiting);
    wachthond($extdebug,4, 'statusids_negative',  $status_negative);

    $contact_foto           = $array_contact['contact_foto']                ?? NULL;
    $birth_date             = $array_contact['birth_date']                  ?? NULL;
    $geslacht               = $array_contact['geslacht']                    ?? NULL;
    $first_name             = $array_contact['first_name']                  ?? NULL;
    $middle_name            = $array_contact['middle_name']                 ?? NULL;
    $last_name              = $array_contact['last_name']                   ?? NULL;    
    $nick_name              = $array_contact['nick_name']                   ?? NULL;
    $displayname            = $array_contact['displayname']                 ?? NULL;
    $crm_drupalnaam         = $array_contact['crm_drupalnaam']              ?? NULL; // drupal username
    $crm_externalid         = $array_contact['crm_externalid']              ?? NULL; // drupal cmsid

    $laatste_keer         	= $array_contact['laatste_keer']             	?? ''; 	 // M61: tbv jaar 'mee komend jaar'
    $curcv_deel_array       = $array_contact['curcv_deel_array']            ?? NULL; // welke jaren deel
    $curcv_leid_array       = $array_contact['curcv_leid_array']            ?? NULL; // welke jaren leid 
    $curcv_keer_deel        = $array_contact['curcv_keer_deel']             ?? NULL; // keren deel
    $curcv_keer_leid        = $array_contact['curcv_keer_leid']             ?? NULL; // keren leid        

    wachthond($extdebug,3, 'contact_id',        $contact_id);
    wachthond($extdebug,3, 'birth_date',        $birth_date);
    wachthond($extdebug,3, 'first_name',        $first_name);
    wachthond($extdebug,3, 'middle_name',       $middle_name);
    wachthond($extdebug,3, 'last_name',         $last_name);
    wachthond($extdebug,3, 'nick_name',         $nick_name);
    wachthond($extdebug,3, 'displayname',       $displayname);
    wachthond($extdebug,3, 'crm_drupalnaam',    $crm_drupalnaam);
    wachthond($extdebug,3, 'crm_externalid',    $crm_externalid);

    wachthond($extdebug,3, 'laatste_keer',       $laatste_keer);
    wachthond($extdebug,3, 'curcv_deel_array',  $curcv_deel_array);
    wachthond($extdebug,3, 'curcv_leid_array',  $curcv_leid_array);
    wachthond($extdebug,3, 'curcv_keer_deel',   $curcv_keer_deel);
    wachthond($extdebug,3, 'curcv_keer_leid',   $curcv_keer_leid);

    $ditjaardeelyes         =   $ditjaar_array['ditjaardeelyes'];
    $ditjaardeelnot         =   $ditjaar_array['ditjaardeelnot'];
    $ditjaardeelmss         =   $ditjaar_array['ditjaardeelmss'];
    $ditjaardeelstf         =   $ditjaar_array['ditjaardeelstf'];
    $ditjaardeeltst         =   $ditjaar_array['ditjaardeeltst'];
    $ditjaardeeltxt         =   $ditjaar_array['ditjaardeeltxt'];

    $ditjaarleidyes         =   $ditjaar_array['ditjaarleidyes'];
    $ditjaarleidnot         =   $ditjaar_array['ditjaarleidnot'];
    $ditjaarleidmss         =   $ditjaar_array['ditjaarleidmss'];
    $ditjaarleidstf         =   $ditjaar_array['ditjaarleidstf'];
    $ditjaarleidtst         =   $ditjaar_array['ditjaarleidtst'];
    $ditjaarleidtxt         =   $ditjaar_array['ditjaarleidtxt'];

    wachthond($extdebug,3, 'ditjaardeelyes',    $ditjaardeelyes);
    wachthond($extdebug,3, 'ditjaardeelnot', 	$ditjaardeelnot);
    wachthond($extdebug,3, 'ditjaardeelmss',    $ditjaardeelmss);
    wachthond($extdebug,4, 'ditjaarleidstf', 	$ditjaarleidstf);    
    wachthond($extdebug,3, 'ditjaardeeltst', 	$ditjaardeeltst);
    wachthond($extdebug,3, 'ditjaardeeltxt', 	$ditjaardeeltxt); 

    wachthond($extdebug,3, 'ditjaarleidyes',    $ditjaarleidyes);
    wachthond($extdebug,3, 'ditjaarleidmss',    $ditjaarleidmss);   
    wachthond($extdebug,3, 'ditjaarleidnot', 	$ditjaarleidnot);
    wachthond($extdebug,3, 'ditjaarleidtst', 	$ditjaarleidtst);
    wachthond($extdebug,3, 'ditjaarleidstf', 	$ditjaarleidstf);
    wachthond($extdebug,3, 'ditjaarleidtxt', 	$ditjaarleidtxt);    

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 1.1 BEREKEN X MEE OBV POSITIEVE REGISTRATIES", 			 "[DEEL]");
	wachthond($extdebug,3, "########################################################################");

	$params_part_deel = [
		'select' => [
			'contact_id.display_name', 'event_id.start_date',
		],
		'where' => [
			['event_id.event_type_id', 	'IN', $eventtypesdeel],
			['status_id', 				'IN', $status_positive],
			['is_test',   				'IN', [TRUE, FALSE]],
			['contact_id', 				'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
	];
	wachthond($extdebug,7, 'params_part_deel', $params_part_deel);
	$result_part_deel = civicrm_api4('Participant', 'get', $params_part_deel);
	wachthond($extdebug,9, 'result_part_deel', $result_part_deel);

	$displayname = $result_part_deel[0]['contact_id.display_name'] ?? '';

	foreach ($result_part_deel as $participant_deel) {
		$line_deel = $participant_deel['event_id.start_date'] ?? NULL;
		wachthond($extdebug,4, "line_deel", $line_deel);
		$evtcv_deel_array[] = date('Y', strtotime($line_deel));
		wachthond($extdebug,4, "participant_deel", $participant_deel);
	}

	$curcv_deel = format_civicrm_string($curcv_deel_array);
	$evtcv_deel = format_civicrm_string($evtcv_deel_array);
	$tagcv_deel = format_civicrm_string($tagcv_deel_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 1.2 BEREKEN X ANN OBV GEANNULEERDE REGISTRATIES",         "[DEEL]");
	wachthond($extdebug,3, "########################################################################");

	$cancv_deel_array 			= [];
	$params_part_deel_canceled 	= [
		'select' => [
			'contact_id.display_name', 'event_id.start_date',
		],
		'where' => [
			['event_id.event_type_id',  'IN', $eventtypesdeel],
			['status_id', 				'IN', [4]], 		 // gebruik GEANNULEERDE event registraties
			['is_test', 				'IN', [TRUE, FALSE]], 
			['contact_id', 				'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,  				
	];
	wachthond($extdebug,7, 'params_part_deel_canceled', 			$params_part_deel_canceled);
	$result_part_deel_canceled = civicrm_api4('Participant','get', 	$params_part_deel_canceled);
	wachthond($extdebug,9, 'result_part_deel_canceled', 			$result_part_deel_canceled);

	foreach ($result_part_deel_canceled as $participant_deel_canceled) {
		$line_deel_canceled = $participant_deel_canceled['event_id.start_date'] ?? NULL;
		wachthond($extdebug,3, "line_deel_canceled", 		$line_deel_canceled);
		$cancv_deel_array[] = date('Y', strtotime($line_deel_canceled));
		wachthond($extdebug,3, "participant_deel_canceled", $participant_deel_canceled);
	}

	$cancv_deel = format_civicrm_string($cancv_deel_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 1.3 BEREKEN X MEE TOP OBV POSITIEVE REGISTRATIES",        "[DEEL]");
	wachthond($extdebug,3, "########################################################################");

	$params_part_deel_top = [
		'select' => [
			'contact_id.display_name', 'event_id.start_date',
		],
		'where' => [
			['event_id.event_type_id',	'IN', $eventtypesdeeltop],
			['status_id', 				'IN', $status_positive],
			['is_test', 				'IN', [TRUE, FALSE]], 
			['contact_id', 				'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
	];
	wachthond($extdebug,7, 'params_part_deel_top', 				$params_part_deel_top);
	$result_part_deel_top = civicrm_api4('Participant', 'get', 	$params_part_deel_top);
	wachthond($extdebug,9, 'result_part_deel_top', 				$result_part_deel_top);

	foreach ($result_part_deel_top as $participant_deel_top) {
		$line_deel_top = $participant_deel_top['event_id.start_date'] ?? NULL;

		wachthond($extdebug,4, 'line_deel_top', $line_deel_top);
		$evtcv_deel_top_array[] = date('Y', strtotime($line_deel_top));
		wachthond($extdebug,4, 'participant_deel_top', $participant_deel_top);
	}

	$curcv_deel_top = format_civicrm_string($evtcv_deel_top_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.0 SYNCRONISE TAGS DEEL WITH CV WHEN CV IS EMPTY",   $displayname);
	wachthond($extdebug,3, "########################################################################");

	if ($exttag == 1) {

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
				['id', 				'=', 	$contact_id], 
				['tag.parent_id', 	'=', 	37], 
				['tag.name', 		'LIKE', 'D%'],
			],
		];

		wachthond($extdebug,7, 'params_tags_deel', 			$params_tags_deel);
		$result_tags_deel = civicrm_api4('Contact', 'get', 	$params_tags_deel);
		wachthond($extdebug,9, 'result_tags_deel', 			$result_tags_deel);
		$tagnr_deel 		= $result_tags_deel->countMatched();
		$tagcv_deel_array	= $result_tags_deel->column('tag.description');  // maakt een array met alleen de velden voor id
			$tagcv_deel_array 	= array_unique($tagcv_deel_array);
			asort($tagcv_deel_array);
			$tagcv_deel 		= implode('', $tagcv_deel_array);
		#if ($tagcv_deel == '') {$tagcv_deel = NULL; }

		wachthond($extdebug,3, 'tagcv_deel_array', 	$tagcv_deel_array);
		wachthond($extdebug,3, 'tagnr_deel', 		$tagnr_deel);
		wachthond($extdebug,3, 'tagcv_deel', 		$tagcv_deel);

		$params_tags_leid = [
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
				['id', 				'=', 	$contact_id], 
				['tag.parent_id', 	'=', 	27], 
				['tag.name', 		'LIKE', 'L%'],
			],
		];

		wachthond($extdebug,7, 'params_tags_leid', 			$params_tags_leid);
		$result_tags_leid = civicrm_api4('Contact', 'get', 	$params_tags_leid);
		wachthond($extdebug,9, 'result_tags_leid', 			$result_tags_leid);

		$tagnr_leid 		= $result_tags_leid->countMatched();
		$tagcv_leid_array	= $result_tags_leid->column('tag.description');  // maakt een array met alleen de velden voor id

		$tagcv_leid = format_civicrm_string($tagcv_leid_array);

		wachthond($extdebug,3, 'tagnr_leid', 		$tagnr_leid);
		wachthond($extdebug,3, 'tagcv_leid', 		$tagcv_leid);
	}

	###########################################################################################
    // MAKE SURE EVEN EMPTY ARRAYS ARE ARRAYS (PHP 8 Style)
    ###########################################################################################

    $oldcv_deel_array     ??= [];
    $oldcv_deel_top_array ??= [];
    $curcv_deel_array     ??= [];
    $curcv_deel_top_array ??= [];
    $evtcv_deel_array     ??= [];
    $evtcv_deel_top_array ??= [];
    $tagcv_deel_array     ??= [];
    $tagcv_deel_top_array ??= [];

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.1 GEBRUIK EVENT CV DEEL VOOR REGISTRATIES NA 2007", "$displayname");
	wachthond($extdebug,3, "########################################################################");

	if ($birth_date > "2007-08-1") {

		wachthond($extdebug,3, 'Birthdate >07', $birth_date);
		// UPDATE DEEL CV 3 want birth_date is bigger than 2006-08-01 en deze deelnemer kan alleen civicrm hebben gebruik voor registratie
		$maxcv_deel_array 		= $evtcv_deel_array;
		$maxcv_deel_top_array 	= $evtcv_deel_top_array;
		// FIX: Maak hier ook direct de strings van
    	$maxcv_deel     		= format_civicrm_string($maxcv_deel_array);
    	$maxcv_deel_top 		= format_civicrm_string($maxcv_deel_top_array);		

	} else {

		wachthond($extdebug,3, 'Birthdate <07', $birth_date);

		// Nu veilig omdat we hierboven [] hebben gegarandeerd
    	$maxcv_deel_array 		= array_unique(array_merge($oldcv_deel_array, $curcv_deel_array, $evtcv_deel_array, $tagcv_deel_array));
		$maxcv_deel_top_array 	= array_unique(array_merge($oldcv_deel_top_array, $curcv_deel_top_array, $evtcv_deel_top_array, $tagcv_deel_top_array));

	}

	$oldcv_deel_array 	= array_unique($oldcv_deel_array);
	$curcv_deel_array 	= array_unique($curcv_deel_array);
	$evtcv_deel_array 	= array_unique($evtcv_deel_array);
	$tagcv_deel_array 	= array_unique($tagcv_deel_array);
	$maxcv_deel_array 	= array_unique($maxcv_deel_array);

	$topcv_deel_array 	= $evtcv_deel_top_array;		// M61: KIES BIJ TOPKAMP ALTIJD VOOR EVTCV
	$topcv_deel_array 	= array_unique($topcv_deel_array);

	wachthond($extdebug,4, 'oldcv_deel_0_array', 		$oldcv_deel_array);
	wachthond($extdebug,4, 'curcv_deel_0_array', 		$curcv_deel_array);
	wachthond($extdebug,4, 'evtcv_deel_0_array', 		$evtcv_deel_array);
	wachthond($extdebug,4, 'tagcv_deel_0_array', 		$tagcv_deel_array);
	wachthond($extdebug,4, 'maxcv_deel_0_array', 		$maxcv_deel_array);

	wachthond($extdebug,4, 'curcv_deel_top_0_array',	$curcv_deel_top_array);
	wachthond($extdebug,4, 'evtcv_deel_top_0_array', 	$evtcv_deel_top_array);
	wachthond($extdebug,4, 'tagcv_deel_top_0_array', 	$tagcv_deel_top_array);
	wachthond($extdebug,4, 'maxcv_deel_top_0_array', 	$maxcv_deel_top_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.2 CLEANUP ARRAYS DEEL & SORTEER", 				     $displayname);
	wachthond($extdebug,3, "########################################################################");

	$avoid_year_array 	= array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,
							    1970,1971,1972,1973,1974,1975,1976,1977,1978);

	// Schoonmaken en formatteren van alle CV-velden (Deelnemer)
	// Gebruik de helper voor alle formatteer-acties (vervangt handmatige separators en implodes)
    $oldcv_deel     = format_civicrm_string(array_diff((array)$oldcv_deel_array, $avoid_year_array));
    $curcv_deel     = format_civicrm_string(array_diff((array)$curcv_deel_array, $avoid_year_array));
    $evtcv_deel     = format_civicrm_string(array_diff((array)$evtcv_deel_array, $avoid_year_array));
    $maxcv_deel     = format_civicrm_string(array_diff((array)$maxcv_deel_array, $avoid_year_array));
    $maxcv_deel_top = format_civicrm_string(array_diff((array)$maxcv_deel_top_array, $avoid_year_array));    

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.3 BEPAAL OF HUIDIG JAAR ER BIJ OF AF MOET", 			 "[DEEL]");
	wachthond($extdebug,3, "########################################################################");

	if ($diteventdeelyes == 1) {

		// VOEG HUIDIG EVENTJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
		array_push($maxcv_deel_array, $ditevent_kampjaar);
		wachthond($extdebug,3, "maxcv_deel (+ $ditevent_kampjaar] want dit event telt ook mee)", $maxcv_deel);
		$maxcv_deel_array = array_unique($maxcv_deel_array);

	} elseif ($eventjaardeelmss == 1 OR $eventjaardeelnot == 1 AND $ditevent_kampjaar) {

		// INDIEN DIT EVENTJAAR GEEN ENKELE POSITIEVE REGISTRATIE DAN DIT EVENTJAAR VERWIJDEREN UIT CV
		$maxcv_deel_array = array_diff($maxcv_deel_array, array($ditevent_kampjaar));
		$evtcv_deel_array = array_diff($evtcv_deel_array, array($ditevent_kampjaar)); 
		wachthond($extdebug,3, "maxcv_deel (- $ditevent_kampjaar] want dit event telt niet mee)", $maxcv_deel);
	}
		
	$oldcv_deel_nr 	= count(array_filter($oldcv_deel_array));
	$curcv_deel_nr 	= count(array_filter($curcv_deel_array));
	$evtcv_deel_nr	= count(array_filter($evtcv_deel_array));
	$tagcv_deel_nr	= count(array_filter($tagcv_deel_array));
	$maxcv_deel_nr	= count(array_filter($maxcv_deel_array));
	$deel_nr_diff  	= abs($maxcv_deel_nr - $curcv_deel_nr);

	wachthond($extdebug,3, 'avoid_year_array', 	$avoid_year_array);
	wachthond($extdebug,3, 'oldcv_deel_array', 	$oldcv_deel_array);
	wachthond($extdebug,3, 'curcv_deel_array', 	$curcv_deel_array);
	wachthond($extdebug,3, 'evtcv_deel_array', 	$evtcv_deel_array);
	wachthond($extdebug,3, 'tagcv_deel_array', 	$tagcv_deel_array);
	wachthond($extdebug,3, 'maxcv_deel_array', 	$maxcv_deel_array);

	wachthond($extdebug,3, 'oldcv_deel', 		$oldcv_deel);
	wachthond($extdebug,3, 'curcv_deel', 		$curcv_deel);
	wachthond($extdebug,3, 'evtcv_deel', 		$evtcv_deel);
	wachthond($extdebug,3, 'cancv_deel', 		$cancv_deel);
	wachthond($extdebug,3, 'tagcv_deel', 		$tagcv_deel);
	wachthond($extdebug,2, 'maxcv_deel', 		$maxcv_deel);

	wachthond($extdebug,3, 'oldcv_deel_nr', 	$oldcv_deel_nr);
	wachthond($extdebug,3, 'curcv_deel_nr', 	$curcv_deel_nr);
	wachthond($extdebug,3, 'evtcv_deel_nr', 	$evtcv_deel_nr);
	wachthond($extdebug,3, 'tagcv_deel_nr', 	$tagcv_deel_nr);
	wachthond($extdebug,2, 'maxcv_deel_nr', 	$maxcv_deel_nr);
	wachthond($extdebug,3, 'deel_nr_diff', 		$deel_nr_diff);

	if ($maxcv_deel_nr > 0 AND $deel_nr_diff > 0) {
		$eerste_deel 	= min(array_filter($maxcv_deel_array));
	} else {
		$eerste_deel 	= current($maxcv_deel_array);
	}
	wachthond($extdebug,3, 'eerste_deel', 	$eerste_deel);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 2.4 BEPAAL OF CURRENT CV MOET WORDEN OVERSCHREVEN");
	wachthond($extdebug,3, "########################################################################");

	if (($maxcv_deel_nr > $curcv_deel_nr) OR ($maxcv_deel_nr < $curcv_deel_nr AND $deel_nr_diff == 1)) {

		wachthond($extdebug,2, "DOE UPDATE DEEL CV 1 want maxcv_deel_nr ($maxcv_deel_nr) differs with $deel_nr_diff from curcv_deel_nr", "$curcv_deel_nr");

		if ($maxcv_deel_nr == 0) { $maxcv_deel = NULL; } // gevaarlijk maar wel terecht hier ingebed

		$cv_deel 		= $maxcv_deel;
		$keren_deel 	= $maxcv_deel_nr;

	} elseif ($deel_nr_diff != 1) {

		$cv_deel 		= $curcv_deel;
		$keren_deel 	= $curcv_deel_nr;

		wachthond($extdebug,2, "GEEN UPDATE van DEEL CV want zou wijzigen met $deel_nr_diff t.o.v. curcv_deel_nr", "$curcv_deel_nr");
	}
/*
	// M61: #2 GEVAARLIJKE REGEL MAAR FIJN MET HET NODIGE DENKWERK
	// VERANDER CURCV ALS DIT JAAR MEE + DIFF = 1 EN (TODO) EEN OF ANDERE STAFFEL
	if ($evtcv_deel_nr < $curcv_deel_nr AND $ditjaardeelyes == 1 AND $evtcv_deel_nr < 5 AND $curcv_deel_nr < 6 AND $maxcv_deel_nr < 7 AND $deel_nr_diff <= 1 AND $eerste_deel > 2015) {
		if ($extdebug >= 2) { watchdog('php','<pre>UPDATE DEEL CV 2 want evtcv_deel_nr: '.print_r($evtcv_deel_nr,TRUE) . ' differs with ['.$deel_nr_diff.'] from curcv_deel_nr: '.print_r($curcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);}
		if ($maxcv_deel_nr == 0) { $maxcv_deel = NULL; } // gevaarlijk maar wel terecht hier ingebed (M61: TODO: klopt dit?)
		$cv_deel 		= $evtcv_deel;
	}

	// M61: #3 GEVAARLIJKE REGEL MAAR FIJN MET HET NODIGE DENKWERK
	// VERANDER CURCV ALS DIT JAAR MEE + DIFF = 1 EN EERSTE KEER MEE > 2015
	if ($evtcv_deel_nr == 1 AND $ditjaardeelyes == 1 AND $deel_nr_diff = 1 AND $eerste_deel > 2015) {
		if ($extdebug >= 2) { watchdog('php','<pre>UPDATE DEEL CV 3 want evtcv_deel_nr: '.print_r($evtcv_deel_nr,TRUE) . ' differs with ['.$deel_nr_diff.'] from curcv_deel_nr: '.print_r($curcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);}	
		if ($maxcv_deel_nr == 0) {
			$maxcv_deel = NULL;	// gevaarlijk maar wel terecht hier ingebed
		}
		$cv_deel 		= $evtcv_deel;
	}
*/
	// M61: bij deelnemers geboren na 2007-08-1 is de event_cv de juiste en kan de max_cv overschrijven
	if ($birth_date > "2007-08-1") {

		wachthond($extdebug,3, "Birthdate >2007 ($birth_date)", "(event_cv is leidend)");

		$cv_deel 		= $evtcv_deel;
		$keren_deel 	= $evtcv_deel_nr;

	} else {

		wachthond($extdebug,3, "Birthdate <2007 ($birth_date)", "(event_cv niet leidend)");

	}

	// M61: tbv checks
	wachthond($extdebug,2, 'cv_deel', 		$cv_deel);
	wachthond($extdebug,2, 'kerendeel', 	$keren_deel);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 3.1 BEREKEN X MEE OBV POSITIEVE REGISTRATIES", 			 "[LEID]");
	wachthond($extdebug,3, "########################################################################");

	$params_part_leid = [
		'select' => [
			'contact_id.display_name', 'event_id.start_date',
		],
		'where' => [
			['event_id.event_type_id',  'IN', $eventtypesleid],
			['status_id', 				'IN', $status_positive],
			['is_test', 				'IN', [TRUE, FALSE]],
			['contact_id', 				'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
	];		
	wachthond($extdebug,7, 'params_part_leid', 				$params_part_leid);
	$result_part_leid = civicrm_api4('Participant', 'get', 	$params_part_leid);
	wachthond($extdebug,9, 'result_part_leid', 				$result_part_leid);
//	$count_part_leid  = $result_part_leid->countMatched();	

	foreach ($result_part_leid as $participant_leid) {
		$line_leid = $participant_leid['event_id.start_date'] ?? NULL;
		wachthond($extdebug,5, "line_leid", 		$line_leid);
		$evtcv_leid_array[] = date('Y', strtotime($line_leid));
		wachthond($extdebug,5, "participant_leid", 	$participant_leid);
	}

	wachthond($extdebug,3, "count_part_leid", 	$count_part_leid);
	wachthond($extdebug,3, "evtcv_leid_array", 	$evtcv_leid_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 3.2 BEREKEN X ANN OBV GEANNULEERDE REGISTRATIES",         "[LEID]");
	wachthond($extdebug,3, "########################################################################");

	$cancv_leid_array = [];
	$params_part_leid_canceled = [
		'select' => [
			'contact_id.display_name', 'event_id.start_date',
		],
		'where' => [
			['event_id.event_type_id', 	'IN', $eventtypesleid],
			['status_id', 				'IN', [4]], 		 // gebruik GEANNULEERDE event registraties
			['is_test', 				'IN', [TRUE, FALSE]], 
			['contact_id', 				'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
	];		
	wachthond($extdebug,7, 'params_part_leid_canceled', 			$params_part_leid_canceled);
	$result_part_leid_canceled = civicrm_api4('Participant', 'get', $params_part_leid_canceled);
	wachthond($extdebug,9, 'result_part_leid_canceled', 			$result_part_leid_canceled);

	foreach ($result_part_leid_canceled as $participant_leid_canceled) {
		$line_leid_canceled = $participant_leid_canceled['event_id.start_date'] ?? NULL;
		wachthond($extdebug,3, "line_leid_canceled", $line_leid_canceled);
		$cancv_leid_array[] = date('Y', strtotime($line_leid_canceled));
		wachthond($extdebug,2, "participant_leid_canceled", $participant_leid_canceled);
	}

	if ($cancv_leid_array) 	{
		arsort($cancv_leid_array);
		$cancv_leid = implode('', $cancv_leid_array);
		wachthond($extdebug,3, "cancv_leid_array", $cancv_leid_array);
	} else {
		$cancv_leid_array 	= [];
		$cancv_leid 		= NULL;
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 3.3 BEREKEN X MEE OBV POSITIEVE REGISTRATIES", 	     "[KAMPSTAF]");
	wachthond($extdebug,3, "########################################################################");

	$evtcv_staf_array 			= [];
	$params_part_leid_stafjaar 	= [
		'select' => [
			'contact_id.display_name', 'event_id.start_date',
		],
		'where' => [
			['event_id.event_type_id',  'IN', $eventtypesleid],
			['PART_LEID.Functie', 		'IN', ['bestuurslid', 'kampstaf']],
			['status_id', 				'IN', $status_positive],
			['is_test', 				'IN', [TRUE, FALSE]], 
			['contact_id', 				'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
	];		
	wachthond($extdebug,7, 'params_part_leid_stafjaar', 			$params_part_leid_stafjaar);
	$result_part_leid_stafjaar = civicrm_api4('Participant', 'get', $params_part_leid_stafjaar);
	wachthond($extdebug,9, 'result_part_leid_stafjaar', 			$result_part_leid_stafjaar);

	foreach ($result_part_leid_stafjaar as $participant_leid_stafjaar) {
		$line_leid_stafjaar = $participant_leid_stafjaar['event_id.start_date'] ?? NULL;
		wachthond($extdebug,3, "line_leid_stafjaar", $line_leid_stafjaar);
		$evtcv_staf_array[] = date('Y', strtotime($line_leid_stafjaar));
		wachthond($extdebug,2, "participant_leid_stafjaar", $participant_leid_stafjaar);
	}

	if ($evtcv_staf_array) 	{
		arsort($evtcv_staf_array);
		$evtcv_staf = implode('', $evtcv_staf_array);
		wachthond($extdebug,3, "evtcv_staf_array", $evtcv_staf_array);
	} else {
		$evtcv_staf_array 	= [];
		$evtcv_staf 		= NULL;
	} 			

    wachthond($extdebug,3, "########################################################################");
    wachthond($extdebug,1, "### CV 3.4 BEREKEN X MEE OBV POSITIEVE REGISTRATIES",    "[TRAININGSDAG]");
    wachthond($extdebug,3, "########################################################################");

    $params_part_toer = [
        'select' => [
            'row_count', 
            'event_id',
        ],
        'where' => [
            ['event_id.event_type_id', 	'IN', $eventtypestoer],
            ['event_id.start_date',    	'>',  $today_fiscalyear_start],
            ['is_test',              	'IN', [TRUE, FALSE]],
            ['contact_id',             	'=',  $contact_id],
        ],
        'checkPermissions' => FALSE,
        'debug' => $apidebug,
    ];
    wachthond($extdebug,7, 'params_part_toer',              $params_part_toer);
    $result_part_toer = civicrm_api4('Participant', 'get',  $params_part_toer);
    wachthond($extdebug,9, 'result_part_toer',              $result_part_toer);

    $count_part_toer    = $result_part_toer->countMatched();
    $evtcv_toer_array   = $result_part_toer->column('event_id');  // maakt een array met alleen de velden voor id
    asort($evtcv_toer_array); // sort by value

    wachthond($extdebug,3, "count_part_toer",   $count_part_toer);
    wachthond($extdebug,3, "evtcv_toer_array",  $evtcv_toer_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 3.5 BEREKEN X MEE OBV POSITIEVE REGISTRATIES", 	         "[MEET]");
	wachthond($extdebug,3, "########################################################################");

	$params_part_meet = [
		'select' => [
			'row_count', 
			'event_id',
		],
		'where' => [
			['event_id.event_type_id', 	'IN', $eventtypesmeet],
			['event_id.start_date',	   	'>',  $today_fiscalyear_start],
            ['is_test',                 'IN', [TRUE, FALSE]],
			['contact_id', 		 	   	'=',  $contact_id],
		],
		'checkPermissions' => FALSE,
		'debug' => $apidebug,
	];
	wachthond($extdebug,7, 'params_part_meet', 				$params_part_meet);
	$result_part_meet = civicrm_api4('Participant', 'get', 	$params_part_meet);
	wachthond($extdebug,9, 'result_part_meet', 				$result_part_meet);

	$count_part_meet	= $result_part_meet->countMatched();
	$evtcv_meet_array	= $result_part_meet->column('event_id');  // maakt een array met alleen de velden voor id
	asort($evtcv_meet_array); // sort by value

    wachthond($extdebug,3, "count_part_meet",   $count_part_meet);
	wachthond($extdebug,3, "evtcv_meet_array", 	$evtcv_meet_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.1 VERMINDER DE LEIDING ARRAY MET DE JAREN ALS STAF");
	wachthond($extdebug,3, "########################################################################");

	// Zorg dat we altijd arrays hebben om mee te rekenen (voorkomt PHP 8 crashes in array_diff)
	$evtcv_leid_array = (array)($evtcv_leid_array ?? []);
	$evtcv_staf_array = (array)($evtcv_staf_array ?? []);

	if (!empty($evtcv_staf_array)) {
		wachthond($extdebug, 3, "evtcv_leid_array (berekend)", $evtcv_leid_array);
		// Trek de stafjaren af van de leidingjaren
		$evtcv_leid_array = array_diff($evtcv_leid_array, $evtcv_staf_array);
		wachthond($extdebug, 3, "evtcv_leid_array (minus stafjaren)", $evtcv_leid_array);
	}

	// Initialiseer alle overige arrays als ze leeg zijn (nodig voor de latere array_merge)
	$oldcv_leid_array  	= (array)($oldcv_leid_array ?? []);
	$curcv_leid_array  	= (array)($curcv_leid_array ?? []);
	$evtcv_toer_array  	= (array)($evtcv_toer_array ?? []);
	$evtcv_meet_array  	= (array)($evtcv_meet_array ?? []);
	$tagcv_leid_array  	= (array)($tagcv_leid_array ?? []);

	// Formatteer de strings direct via de helper (vervangt alle handmatige implode/if-else blokken)
	$oldcv_leid 		= format_civicrm_string($oldcv_leid_array);
	$curcv_leid 		= format_civicrm_string($curcv_leid_array);
	$evtcv_leid 		= format_civicrm_string($evtcv_leid_array);
	$evtcv_staf 		= format_civicrm_string($evtcv_staf_array);
	$tagcv_leid 		= format_civicrm_string($tagcv_leid_array);

	// Bereken de verzamel-array
	$maxcv_leid_array 	= array_merge($oldcv_leid_array, $curcv_leid_array, $evtcv_leid_array, $tagcv_leid_array);
	$maxcv_leid_array 	= array_diff($maxcv_leid_array, $avoid_year_array);

	// Gebruik array_values om zeker te zijn van een schone array na diff/unique
	$maxcv_leid_array 	= array_values(array_unique(array_filter($maxcv_leid_array)));
	$maxcv_leid_nr	  	= count($maxcv_leid_array);

	if ($maxcv_leid_nr > 0) { 
		$eerste_leid 	= min($maxcv_leid_array); 
	} else { 
		$eerste_leid 	= NULL; 
	}

	wachthond($extdebug, 5, "maxcv_leid_A_array", $maxcv_leid_array);

	// Bepaal welke bron leidend is voor de CV
	if ($birth_date > "1995-08-1" OR ($eerste_leid && $eerste_leid >= 2013)) {
		wachthond($extdebug, 5, "1e leid >= 13 of jongere generatie", $eerste_leid);
		$maxcv_leid_array 	= $evtcv_leid_array;
		// FIX: Genereer hier direct de string voor de database
		$maxcv_leid 		= format_civicrm_string($maxcv_leid_array);
	} else {
		wachthond($extdebug, 5, "1e leid <= 13 of oudere generatie", $eerste_leid);
		$maxcv_leid_array = array_values(array_unique(array_merge($curcv_leid_array, $evtcv_leid_array, $tagcv_leid_array)));
	}

	wachthond($extdebug, 3, "curcv_leid_0_array", $curcv_leid_array);
	wachthond($extdebug, 3, "evtcv_leid_0_array", $evtcv_leid_array);
	wachthond($extdebug, 3, "tagcv_leid_0_array", $tagcv_leid_array);
	wachthond($extdebug, 3, "maxcv_leid_0_array", $maxcv_leid_array);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.2 BEPAAL OF HUIDIG JAAR ER BIJ OF AF MOET", 			 "[LEID]");
	wachthond($extdebug,3, "########################################################################");

	if ($diteventleidyes == 1) {

		// VOEG HUIDIG EVENTJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
		array_push($maxcv_leid_array, $ditevent_kampjaar);
		wachthond($extdebug,3, "maxcv_leid (+ $ditevent_kampjaar] want dit event telt ook mee)", $maxcv_leid_array);

		$maxcv_leid_array = array_unique($maxcv_leid_array);

	} elseif ($eventjaarleidmss == 1 OR $eventjaarleidnot == 1) {

		// INDIEN DIT EVENTJAAR GEEN ENKELE POSITIEVE REGISTRATIE DAN DIT EVENTJAAR VERWIJDEREN UIT CV
		$maxcv_leid_array = array_diff($maxcv_leid_array, array($ditevent_kampjaar));
		$evtcv_leid_array = array_diff($evtcv_leid_array, array($ditevent_kampjaar)); 
		wachthond($extdebug,3, "maxcv_leid (- $ditevent_kampjaar] want dit event telt niet mee)", $maxcv_leid_array);

	}

	$avoid_year_array_leid = array(1970,1971,1972,1973,1974,1975,1976,1977,1978);

    $oldcv_leid = format_civicrm_string(array_diff((array)$oldcv_leid_array, $avoid_year_array_leid));
    $curcv_leid = format_civicrm_string(array_diff((array)$curcv_leid_array, $avoid_year_array_leid));
    $evtcv_leid = format_civicrm_string(array_diff((array)$evtcv_leid_array, $avoid_year_array_leid));
    $evtcv_staf = format_civicrm_string(array_diff((array)$evtcv_staf_array, $avoid_year_array_leid));
    $evtcv_meet = format_civicrm_string(array_diff((array)$evtcv_meet_array, $avoid_year_array_leid));
    $maxcv_leid = format_civicrm_string(array_diff((array)$maxcv_leid_array, $avoid_year_array_leid));

	wachthond($extdebug,3, "maxcv_leid_array", 	$maxcv_leid_array);
	wachthond($extdebug,3, "maxcv_leid", 		$maxcv_leid);
		
	$oldcv_leid_nr 	= count(array_filter($oldcv_leid_array));
	$curcv_leid_nr 	= count(array_filter($curcv_leid_array));
	$evtcv_leid_nr	= count(array_filter($evtcv_leid_array));
	$evtcv_staf_nr	= count(array_filter($evtcv_staf_array));
	$evtcv_meet_nr	= count(array_filter($evtcv_meet_array));
	$tagcv_leid_nr	= count(array_filter($tagcv_leid_array));
	$maxcv_leid_nr	= count(array_filter($maxcv_leid_array));
	$leid_nr_diff  	= abs($maxcv_leid_nr - $curcv_leid_nr);

	wachthond($extdebug,3, 'avoid_year_array', 	$avoid_year_array);
	wachthond($extdebug,3, 'oldcv_leid_array', 	$oldcv_leid_array);
	wachthond($extdebug,3, 'curcv_leid_array', 	$curcv_leid_array);
	wachthond($extdebug,3, 'evtcv_leid_array', 	$evtcv_leid_array);
	wachthond($extdebug,3, 'evtcv_staf_array', 	$evtcv_staf_array);
	wachthond($extdebug,3, 'evtcv_meet_array', 	$evtcv_meet_array);
	wachthond($extdebug,3, 'tagcv_leid_array', 	$tagcv_leid_array);
	wachthond($extdebug,3, 'maxcv_leid_array', 	$maxcv_leid_array);

	wachthond($extdebug,3, 'oldcv_leid', 		$oldcv_leid);
	wachthond($extdebug,3, 'curcv_leid', 		$curcv_leid);
	wachthond($extdebug,3, 'evtcv_leid', 		$evtcv_leid);
	wachthond($extdebug,3, 'evtcv_staf', 		$evtcv_staf);
	wachthond($extdebug,3, 'evtcv_meet', 		$evtcv_meet);
	wachthond($extdebug,3, 'cancv_leid', 		$cancv_leid);
	wachthond($extdebug,3, 'evtcv_staf', 		$evtcv_staf);
	wachthond($extdebug,3, 'tagcv_leid', 		$tagcv_leid);
	wachthond($extdebug,2, 'maxcv_leid', 		$maxcv_leid);

	wachthond($extdebug,3, 'oldcv_leid_nr', 	$oldcv_leid_nr);
	wachthond($extdebug,3, 'curcv_leid_nr', 	$curcv_leid_nr);
	wachthond($extdebug,3, 'evtcv_leid_nr', 	$evtcv_leid_nr);
	wachthond($extdebug,3, 'evtcv_staf_nr', 	$evtcv_staf_nr);
	wachthond($extdebug,3, 'evtcv_meet_nr', 	$evtcv_meet_nr);
	wachthond($extdebug,3, 'tagcv_leid_nr', 	$tagcv_leid_nr);
	wachthond($extdebug,2, 'maxcv_leid_nr', 	$maxcv_leid_nr);

	wachthond($extdebug,3, 'leid_nr_diff', 		$leid_nr_diff);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.3 BEPAAL NETTO CV", 							   "$displayname");
	wachthond($extdebug,3, "########################################################################");

	// M61: GEVAARLIJKE REGEL MAAR FIJN MET HET NODIGE DENKWERK
	// VERANDER CURCV ALLEEN ALS ER BIJKOMT, VERMINDER ALLEEN ALS DE NIEUWE WAARDE SLECHTS 1 JAAR AFWIJKT			
	if (($maxcv_leid_nr > $curcv_leid_nr) OR ($maxcv_leid_nr < $curcv_leid_nr AND $leid_nr_diff == 1)) {

		wachthond($extdebug,2, "UPDATE LEID CV want maxcv_leid_nr ($maxcv_leid_nr) differs with $leid_nr_diff from curcv_leid_nr ($curcv_leid_nr)", $maxcv_leid);

		if ($maxcv_leid_nr == 0) { $maxcv_leid = NULL; } // gevaarlijk maar wel terecht hier ingebed
		$cv_leid 		= $maxcv_leid;
		$keren_leid 	= $maxcv_leid_nr;

	} elseif ($leid_nr_diff != 1) {

		$cv_leid 		= $curcv_leid;
		$keren_leid 	= $curcv_leid_nr;

		wachthond($extdebug,2, "CANCEL UPDATE LEID CV want zou wijzigen met $leid_nr_diff", "[t.o.v. curcv_leid_nr ($curcv_leid_nr)]");
	}

	if ($maxcv_leid_nr > 0) { $eerste_leid  = min(array_filter($maxcv_leid_array)); } else { $eerste_leid = current($maxcv_leid_array); }

	wachthond($extdebug,3, "maxcv_leid_B_array", $maxcv_leid_array);

	// M61: bij leiding met eerstejaar leid >=2013 is de event_cv de juiste en kan de max_cv overschrijven
//	if ($eerste_leid >= 2013) {
	// M61: bij leiding geboren na 1995-08-1 is de event_cv de juiste (eerste_leid > 2013) en kan de max_cv overschrijven
	if ($birth_date > "1995-08-1" OR $eerste_leid >= 2013) {

		wachthond($extdebug,3, "1e leid >= 2013", $eerste_leid);
		$cv_leid 		= $evtcv_leid;
		$keren_leid 	= $evtcv_leid_nr;

	} else {
		wachthond($extdebug,3, "1e leid <= 2013", $eerste_leid);
	}

	#asort($cv_leid);
	wachthond($extdebug,2, "cv_leid", $cv_leid);
	wachthond($extdebug,2, "kerenleid", $keren_leid);

	###########################################################################################
	// poging om totaal aantal keren mee als deelnemer van het Topkamp te berekenen obv event registraties
	###########################################################################################

	$params_countpart_top = [
		'status_id',  'IN', $status_positive,
			'role_id' 	 	=> "Deelnemer Topkamp",
			'contact_id' 	=> $contact_id,
			#'fee_amount' => ['>' => 1],
	];
	wachthond($extdebug,7, 'params_countpart_top', 					$params_countpart_top);
	$result_countpart_top = civicrm_api3('Participant', 'getcount', $params_countpart_top);
	wachthond($extdebug,9, 'result_countpart_top', 					$result_countpart_top);

	$evtcv_top_nr = $result_countpart_top;
	wachthond($extdebug,3, "evtcv_top_nr", $evtcv_top_nr);

	$keren_top = $evtcv_top_nr;

	// M61: Hier zou eigenlijk nog iets moeten voor deelname topkamp < 2013 (dus zonder event registratie)

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.3 a BEPAAL EERSTE EN LAATSTE JAAR",    		         "[DEEL]");
	wachthond($extdebug,3, "########################################################################");

	if (!empty($maxcv_deel_array)) {

		$eerste_keer    = '';
	    $laatste_keer   = '';

	    $eerste_deel    = '';
	    $eerstexdeel    = '';
	    $laatstexdeel   = '';

	    $eerste_top     = '';
	    $laatste_top    = '';

	    $eerste_leid    = '';
	    $eerstexleid    = '';
	    $laatstexleid   = '';

		if ($maxcv_deel_nr > 0) {
			$eerste_deel  	= min(array_filter($maxcv_deel_array))  		?? '';
			$laatste_deel 	= max(array_filter($maxcv_deel_array))  		?? '';
		}
		if ($maxcv_deel_nr == 1) {
			$eerstexdeel	= 'eerstex';
			$eerste_deel   	= current($maxcv_deel_array) 					?? '';
        	$laatste_deel  	= current($maxcv_deel_array) 					?? '';		
		}
			wachthond($extdebug,2, "eerstexdeel", 	$eerste_deel);
			wachthond($extdebug,2, "eerste_deel", 	$eerste_deel);
			wachthond($extdebug,2, "laatste_deel", 	$laatste_deel);
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.3 b BEPAAL EERSTE EN LAATSTE JAAR",    		          "[TOP]");
	wachthond($extdebug,3, "########################################################################");

	if (!empty($topcv_deel_array)) {

		if ($maxcv_deel_nr > 0) {
			$eerste_top  	= min(array_filter($topcv_deel_array)) 			?? '';
			$laatste_top 	= max(array_filter($topcv_deel_array)) 			?? '';
			wachthond($extdebug,2, "eerste_top", $eerste_top);
			wachthond($extdebug,2, "laatste_top",$laatste_top);
		}
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.3 c BEPAAL EERSTE EN LAATSTE JAAR",    		         "[LEID]");
	wachthond($extdebug,3, "########################################################################");

	if (!empty($maxcv_leid_array)) {
		if ($maxcv_leid_nr > 0) {
			$eerste_leid  	= min(array_filter($maxcv_leid_array))  		?? '';
			$laatste_leid 	= max(array_filter($maxcv_leid_array))  		?? '';
		}
		if ($maxcv_leid_nr == 1) {
			$eerstexleid 	= 'eerstex';
			$eerste_leid	= current($maxcv_leid_array)					?? '';
			$laatste_leid	= current($maxcv_leid_array)					?? '';
		}
			wachthond($extdebug,2, "eerste_leid", 	$eerste_leid);
			wachthond($extdebug,2, "laatste_leid", 	$laatste_leid);
			wachthond($extdebug,3, "eerstexleid", 	$eerstexleid);
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.3 d BEPAAL EERSTE EN LAATSTE JAAR",    		       "[TOTAAL]");
	wachthond($extdebug,3, "########################################################################");

	$totaal_mee   = $keren_deel + $keren_leid;
	$eerste_keer  = $maxcv_deel_nr > 0 ? $eerste_deel  : $eerste_leid;
	$laatste_keer = $maxcv_leid_nr > 0 ? $laatste_leid : $laatste_deel;

	wachthond($extdebug,2, "eerste_keer", 		$eerste_keer);
	wachthond($extdebug,2, "laatste_keer", 		$laatste_keer);

	$maxcv_deel_text = implode(', ', array_filter($maxcv_deel_array));
	$maxcv_leid_text = implode(', ', array_filter($maxcv_leid_array));

	wachthond($extdebug,2, "maxcv_deel_text",	$maxcv_deel_text);
	wachthond($extdebug,2, "maxcv_leid_text", 	$maxcv_leid_text);

	if ($extcv == 1) {
		$eventverschildeel	= $evtcv_deel_nr - 	$curcv_deel_nr;
		$eventverschilleid	= $evtcv_leid_nr - 	$curcv_leid_nr;
	}
	if ($exttag == 1) {
		$tagverschildeel	= $tagnr_deel 	 - 	$curcv_deel_nr;
		$tagverschilleid	= $tagnr_leid 	 - 	$curcv_leid_nr;
	}

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 4.4 IS DIT HET 1E JAAR ALS DEEL OF LEID?", "$displayname");
	wachthond($extdebug,3, "########################################################################");

	$ditevent_part_1stdeel 	= NULL;
	$ditevent_part_1stleid 	= NULL;

	if ($maxcv_deel_nr == 1 AND $ditjaardeelyes == 1) {
		$ditevent_part_1stdeel 	= 'eerstex';
	}
	if ($maxcv_leid_nr == 1 AND $ditjaarleidyes == 1) {
		$ditevent_part_1stleid 	= 'eerstex';
		}

	wachthond($extdebug,3, "1 part_ditjaar1stdeel", 	$ditevent_part_1stdeel);
	wachthond($extdebug,3, "1 part_ditjaar1stleid", 	$ditevent_part_1stleid);

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 5.1 REGISTER DEZE LEIDER VOOR EVENTS IN THE PAST",  "$displayname");
	wachthond($extdebug,3, "########################################################################");

	if ($curcv_leid_nr > 0) {

		# GEBRUIK DE GEANNULEERDE KAMPJAREN ALS LEIDING OOK VOOR DEZE BEREKENING
		$evtcv_leid_totaal_array 	= array_merge((array)$evtcv_leid_array, (array)$evtcv_staf_array, (array)$cancv_leid_array);
		// notcv_leid_array wordt gebruikt in een foreach, dus zorg dat dit altijd een schone array is:
		$notcv_leid_array 			= array_diff((array)$curcv_leid_array, $evtcv_leid_totaal_array);

		wachthond($extdebug,3, "maxcv_leid_array", 			$maxcv_leid_array);
		wachthond($extdebug,3, "evtcv_leid_totaal_array", 	$evtcv_leid_totaal_array);
		wachthond($extdebug,3, "notcv_leid_array", 			$notcv_leid_array);
		wachthond($extdebug,3, "########################################################################");
		wachthond($extdebug,3, "evtcv_leid_array", 			$evtcv_leid_array);
		wachthond($extdebug,3, "evtcv_staf_array", 			$evtcv_staf_array);
		wachthond($extdebug,3, "cancv_leid_array", 			$cancv_leid_array);
		wachthond($extdebug,3, "notcv_leid_array", 			$notcv_leid_array);
		wachthond($extdebug,4, "########################################################################");

		foreach ($notcv_leid_array as $jaarleid) {
			$evt_startdate 	= date("1-1-$jaarleid");
			$evt_enddate 	= date("31-12-$jaarleid");

			$params_event_not = [
					'checkPermissions' 	=> FALSE,
				'debug' 			=> $apidebug,
					'select' => [
						'id', 'event_type_id', 'title', 'row_count',
					],
					'where' => [
						['event_type_id', 	'IN', 		$eventtypesleid],
						['title', 			'NOT LIKE', '%TEST%'],
						['start_date', 		'>', 		$evt_startdate],
						['start_date', 		'<', 		$evt_enddate],
						['start_date', 		'<', 		$today_datetime],	// only events in the past
					],
			];

			wachthond($extdebug,3, 'params_event_not', 			$params_event_not);
			$result_event_not = civicrm_api4('Event', 'get', 	$params_event_not);
			wachthond($extdebug,3, 'result_event_not', 			$result_event_not);

			$old_event_id = $result_event_not[0]['id'] ?? NULL;

			wachthond($extdebug,3, "Deze persoon geregistreerd voor kampjaar $jaarleid", "[eventid: $old_event_id]");

			$params_part_ditevent_create = [
				'checkPermissions' 	=> FALSE,
				'debug'  			=> $apidebug,
				'values' => [
					'contact_id' 	=> $contact_id, 
					'event_id' 		=> $old_event_id,
					'register_date' => $evt_startdate,
					'status_id'		=> 2,
					'role_id' 		=> [6],	// rol = leiding
					'PART_LEID.Functie:name' => 'kampleiding',	// M61: later updaten naar bv. hoofdleiding of groepsleiding
				],
			];
			wachthond($extdebug,7, 'params_participant_create', $params_part_ditevent_create);
			if ($regpast == 1 AND $old_event_id > 0 AND (!in_array($old_event_id, $kampids_all))) {
					// M61: maak alles behalve huidige fiscale jaar
//					$participant_create = civicrm_api4('Participant', 'create', $params_part_ditevent_create);
			}
			wachthond($extdebug,9, 'result_participant_create', $participant_create);
		}
	}

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,1, "### 6.0 UPDATE CONTACT",                                     $displayname);
    wachthond($extdebug,2, "########################################################################");

    // 1. Initialiseer de basis params
    $params_contact = [
        'reload'           => TRUE,
        'checkPermissions' => FALSE,
        'where'            => [['id', '=', (int)$contact_id]],
        'values'           => ['display_name' => $displayname],
    ];

	// 2. De Mapping: 'CiviCRM_Veldnaam' => 'Naam_van_lokale_variabele'
    // Let op: De rechterkant is de NAAM van de variabele (zonder $)
    $mapping = [
        'Curriculum.Eerste_keer'        => 'eerste_keer',
        'Curriculum.Laatste_keer'       => 'laatste_keer',
        'Curriculum.Totaal_keren_mee'   => 'totaal_mee',
        'Curriculum.CV_Deel'            => 'cv_deel',
        'Curriculum.Keren_Deel'         => 'keren_deel',
        'Curriculum.Eerste_deel'        => 'eerste_deel',
        'Curriculum.Laatste_deel'       => 'laatste_deel',
        'Curriculum.EventCV_Deel'       => 'evtcv_deel',
        'Curriculum.EventTotaal_Deel'   => 'evtcv_deel_nr',
        'Curriculum.Eventverschil_Deel' => 'evtcv_deel_dif',
        'Curriculum.Keren_Topkamp'      => 'keren_top',
        'Curriculum.Eerste_Topkamp'     => 'eerste_top',
        'Curriculum.Laatste_Topkamp'    => 'laatste_top',
        'Curriculum.CV_Leid'            => 'cv_leid',
        'Curriculum.Keren_Leid'         => 'keren_leid',
        'Curriculum.Eerste_leid'        => 'eerste_leid',
        'Curriculum.Laatste_leid'       => 'laatste_leid',
        'Curriculum.EventCV_Leid'       => 'evtcv_leid',
        'Curriculum.EventTotaal_Leid'   => 'evtcv_leid_nr',
        'Curriculum.Eventverschil_Leid' => 'evtcv_leid_dif',
        'Curriculum.CV_deel_text_'      => 'maxcv_deel_text',
        'Curriculum.CV_leid_text_'      => 'maxcv_leid_text',
    ];

    // 3. De Magische Loop
    foreach ($mapping as $civiField => $varName) {
        
        // Controleer of de variabele bestaat in de huidige scope
        if (!isset($$varName)) {
            wachthond($extdebug, 1, "WAARSCHUWING: Variabele $$varName bestaat niet voor veld $civiField");
            continue;
        }

        $rawVal = $$varName; 

        // Haal door de Smart Helper
        $cleanVal = format_civicrm_smart($rawVal, $civiField);

        // LOG de transformatie voor debuggen
        wachthond($extdebug, 3, "Smart Processing: $civiField", "Raw: " . (is_array($rawVal) ? 'ARRAY' : $rawVal) . " | Clean: $cleanVal");

        // Overschrijf de variabele zelf (voor de return array in Sectie 7.0)
        $$varName = $cleanVal;

        // Voeg toe aan API params als er inhoud is
        if (!empty($cleanVal) || $cleanVal === 0 || $cleanVal === '0') {
            $params_contact['values'][$civiField] = $cleanVal;
        }
    }

    // 3. Finale check van de update parameters
  	wachthond($extdebug,3, 'params_contact',            	$params_contact);
    wachthond($extdebug,2, "FINALE API VALUES", $params_contact['values']);

    // 4. Voer de update uit
    if ($contact_id > 0 && count($params_contact['values']) > 1) { // Meer dan alleen display_name
        try {
            civicrm_api4('Contact', 'update', $params_contact);
            wachthond($extdebug, 1, "CV Update succesvol uitgevoerd voor ID $contact_id");
        } catch (\Exception $e) {
            wachthond(1, "FOUT bij CV Update: " . $e->getMessage());
        }
    }

	wachthond($extdebug,3, "########################################################################");
	wachthond($extdebug,1, "### CV 7.0 RETURN VALUES",  							     $displayname);
	wachthond($extdebug,3, "########################################################################");

    $cv_array = array(
        'keren_deel'        => $keren_deel,
        'keren_leid'        => $keren_leid,
        'keren_top'        	=> $keren_top,
        'totaal_mee'        => $totaal_mee,

        'cv_deel'           => $cv_deel,
        'cv_leid'           => $cv_leid,

        'eerste_deel'       => $eerste_deel,
        'eerste_leid'       => $eerste_leid,
        'eerste_top'       	=> $eerste_top,        

        'laatste_deel'      => $laatste_deel,
        'laatste_leid'      => $laatste_leid,
		'laatste_top'      	=> $laatste_top,

        'eerste_keer'       => $eerste_keer,
        'laatste_keer'      => $laatste_keer,

        'cv_deel_text'      => $maxcv_deel_text,
        'cv_leid_text'      => $maxcv_leid_text,        

        'evtcv_deel'		=> $evtcv_deel,
        'evtcv_leid'		=> $evtcv_leid,

        'evtcv_deel_nr'		=> $evtcv_deel_nr,
        'evtcv_leid_nr'		=> $evtcv_leid_nr,

        'evtcv_deel_dif'	=> $eventverschildeel,        
        'evtcv_leid_dif'	=> $eventverschilleid,

		'evtcv_meet_array'	=> $evtcv_meet_array,
		'evtcv_toer_array'	=> $evtcv_toer_array,
    );

    wachthond($extdebug,3, 'RETURN cv_array', $cv_array);

	wachthond($extdebug,1, "########################################################################");
	wachthond($extdebug,1, "### CV 1.X EINDE REPAIR CURRICULUM (KAMPCV) DEEL & LEID", "[groupID: $groupID] [op: $op]");
	wachthond($extdebug,1, "########################################################################");

	return $cv_array;

}