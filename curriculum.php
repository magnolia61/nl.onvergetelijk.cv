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

/*
function curriculum_civicrm_buildForm($formName, &$form) {
  // note that form was passed by reference
  if ($extdebug == 1) { watchdog('php', '<pre>emptySeats1:' . print_r($emptySeats, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  $form->assign('$emptySeats', 77);
  if ($extdebug == 1) { watchdog('php', '<pre>emptySeats2:' . print_r($emptySeats, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  if ($extdebug == 1) { watchdog('php', '<pre>formName:' . print_r($formName, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 }
*/

function curriculum_civicrm_custom($op, $groupID, $entityID, &$params) {

	$extdebug	= 1;
	$extcv 		= 1;
	$extdjcont 	= 1;
	$extdjpart	= 1;
	$exttag		= 0;
	$extvog		= 1;

	//if (!in_array($groupID, array("103", "139", "190", "181"))) {
	if (!in_array($groupID, array("139","190"))) { // ALLEEN PART PROFILES
		// 103	TAB  CURRICULUM
		// 139	PART DEEL
		// 190	PART LEID
		// (140	PART LEID VOG)
		// 181	TAB  INTAKE
		#if ($extdebug == 1) { watchdog('php', '<pre>--- SKIP EXTENSION CV (not in proper group) [groupID: '.$groupID.'] [op: '.$op.']---</pre>', null, WATCHDOG_DEBUG); }
		return; //   if not, get out of here
	}

	if (in_array($groupID, array("103", "139", "190", "181"))) {

    	if ($extdebug == 1) { watchdog('php', '<pre>*** START EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG); }

		if ($op != 'create' && $op != 'edit') { //    did we just create or edit a custom object?
    		if ($extdebug == 1) { watchdog('php', '<pre>EXIT: op = create OR op is not edit</pre>', NULL, WATCHDOG_DEBUG); }
			return; //    if not, get out of here
		}
    	$diffyears		= 0;
    	$eventtype 		= 0;
		$tgdeel 		= 0;
		$cvdeel 		= 0;
   		$tgleid 		= 0;
		$cvleid 		= 0;
		$welkkamplang 	= 0;
		$welkkampkort 	= 0;
		$contact_id 	= NULL;
		$event_type_id 	= 0;
		$ditjaardeel 	= 0;
		$ditjaarleid 	= 0;
		$arraydeel 		= array();
		$arrayleid 		= array();
		$ditkaljaar 	= date("Y");
		$eventypesdeel 	= array(11, 12, 13, 14, 21, 22, 23, 24, 33);	//	EVENT_TYPE_ID'S VAN DE KAMPEN VAN DIT JAAR
		$eventypesleid 	= array(0 => 1);								//	EVENT_TYPE_ID VAN HET LEIDING EVENT VAN DIT JAAR
		$vognodig 		= NULL;

		#####################################################################################################
		# VIND ALLE EVENT LEIDING & DEELNEMERS VOOR DIT JAAR
    	if ($extdebug == 1) { watchdog('php', '<pre>VIND ALLE EVENT LEIDING & DEELNEMERS VOOR DIT JAAR [groupID: '.$groupID.'] [op: '.$op.']</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		// find event_id's of camps of current year (find them by event_type_id)
		$config = CRM_Core_Config::singleton( );
		$fiscalYearStart = $config->fiscalYearStart;
		$todaydatetime = date("Y-m-d H:i:s");
		#if ($extdebug == 1) { watchdog('php', '<pre>fiscalYearStart:' . print_r($fiscalYearStart, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>today:' . print_r($today, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$fiscalyear_start = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y");
		$fiscalyear_end   = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y");

    	#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start 0:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	if (strtotime($fiscalyear_start) > strtotime($todaydatetime)) {
  			$fiscalyear_start = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("-1 year"));
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start -1:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  		}
		$grensvognoggoed = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("-2 year")); // VOG noggoed als datum binnen priveous 2 fiscal year valt
  		#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end 0:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if (strtotime($fiscalyear_end)   < strtotime($todaydatetime)) {
  			$fiscalyear_end   = date($fiscalyear_end, strtotime("+1 year"));
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end +1:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		}
		if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   	    $result = civicrm_api3('Event', 'get', array(	// vind info over events leiding
   			'sequential' => 1,
      		'return' => array("id","title"),
			'event_type_id' => array('IN' => $eventypesleid),
			'start_date' => array('>' => $fiscalyear_start),
			'end_date' => array('<' => $fiscalyear_end),
    	));
    	$kampidsleid[] = $result['values'][0]['id'];
   		$result = civicrm_api3('Event', 'get', array(	// vind info over events deelnemers
   			'sequential' => 1,
      		'return' => array("id","title"),
			'event_type_id' => array('IN' => $eventypesdeel),
			'start_date' => array('>' => $fiscalyear_start),
			'end_date' => array('<' => $fiscalyear_end),
    	));
		$kampidsdeel = array();
    	$kampidsdeelcount = $result['count']-1;
  		for ($i = 0; $i <= $kampidsdeelcount; $i++) {
      		$kamp_id = $result['values'][$i]['id'];
      		$kampidsdeel[] = $kamp_id;
  		}
  		ksort($kampidsdeel);
  		ksort($kampidsleid);
  		$kampids_all = $kampidsdeel;
  		array_push($kampids_all, $kampidsleid[0]);

		#if ($extdebug == 1) { watchdog('php', '<pre>kampidsdeelcount:' . print_r($kampidsdeelcount, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>kampidsdeel:' . print_r($kampidsdeel, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>kampidsleid:' . print_r($kampidsleid, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>kampids_all:' . print_r($kampids_all, true) . '</pre>', null, WATCHDOG_DEBUG); }

		#####################################################################################################
		# GET PARTICIPANT INFO FOR ALL OPERATIONS
    	if ($extdebug == 1) { watchdog('php', '<pre>GET PARTICIPANT INFO FOR ALL OPERATIONS [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

   		if (in_array($groupID, array("139", "190"))) {	// PART DEEL + PART LEID
			$where = 'id';
		}
		if (in_array($groupID, array("103", "181"))) {	// TAB CV + TAB INTAKE
			$where = 'contact_id';
		}
    	$params_partinfo = array(
      		'sequential' => 1,
      		'return' => array("id", "contact_id", "first_name", "event_id", "start_date", "custom_592", "custom_649", "custom_567", "custom_568","custom_56","custom_68","custom_603","custom_602","custom_599","custom_600","custom_959","custom_647","custom_474","custom_663", "display_name","custom_376","custom_73","custom_74"),
      		'status_id' => array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Pending from waitlist", "Partially paid", "Pending refund"),
      		$where => $entityID,
      		'event_id' => array('IN' => $kampids_all),	// gebruik de gevonden event_id's van de kampen van dit jaar
    	);
   		#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo:' . print_r($params_partinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  		$result = civicrm_api3('Participant', 'get', $params_partinfo);

   		#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$part_id 			= $result['values'][0]['id'];
		$contact_id			= $result['values'][0]['contact_id'];
		$first_name			= $result['values'][0]['first_name'];
   		$part_eventid 		= $result['values'][0]['event_id'];
   		$part_welkkamp 		= $result['values'][0]['custom_567'];
   		$part_functie 		= $result['values'][0]['custom_568'];

   		$datum_belangstelling 	= $result['values'][0]['custom_647'];
		$datum_drijf_ingevuld	= $result['values'][0]['custom_474'];
		$datum_drijf_gechecked	= $result['values'][0]['custom_663'];

   		$vogrecent 			= $result['values'][0]['custom_56'];
   		$vogkenmerk 		= $result['values'][0]['custom_68'];
   		$part_vogdatum		= $result['values'][0]['custom_603'];
   		$part_vogkenmerk 	= $result['values'][0]['custom_602'];
   		$part_vogverzocht 	= $result['values'][0]['custom_599'];
		$part_vogingediend	= $result['values'][0]['custom_600'];
		$part_vogontvangst	= $result['values'][0]['custom_959'];

   		$displayname 		= $result['values'][0]['display_name'];	// displayname van contact
		$arraydeel	 		= $result['values'][0]['custom_376'];	// welke jaren deel
		$arrayleid	 		= $result['values'][0]['custom_73'];	// welke jaren leid
		$hoevaakleid		= $result['values'][0]['custom_74'];	// hoe vaak leid

   		$result = civicrm_api3('Event', 'get', array(
    		'sequential' => 1,
      		'return' => array("event_type_id"),
			'event_id' => $part_eventid, 							// eventid of specific kamp
    	));
 	    $part_eventtypeid 	= $result['values'][0]['event_type_id'];

   		if ($extdebug == 1) { watchdog('php', '<pre>datum_belangstelling:'. print_r($datum_belangstelling, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_gechecked:'. print_r($datum_drijf_gechecked, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		#if ($extdebug == 1) { watchdog('php', '<pre>displayname:'. print_r($displayname, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>first_name:' . print_r($first_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:' . print_r($part_eventid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid:' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel0:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid0:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

   		#$part_eerstexdeel 	= $result['values'][0]['custom_592']['eerstekeer'];
   		#$part_eerstexleid 	= $result['values'][0]['custom_649']['eerstekeer'];
   		#if (in_array("eerstekeer", $part_eerstexdeel)) 	{ $eerstexdeel = 'eerstekeer'; }
   		#if (in_array("eerstekeer", $part_eerstexleid)) 	{ $eerstexleid = 'eerstekeer'; }
   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eerstexdeel:' . print_r($part_eerstexdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eerstexleid:' . print_r($part_eerstexleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		if ($datum_belangstelling AND empty($datum_drijf_ingevuld)) {
			$datum_drijf_ingevuld = $datum_belangstelling;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld_van_belangstelling:'. print_r($datum_drijfveren, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}
		if ($datum_drijf_gechecked AND empty($datum_drijf_ingevuld)) {
			$datum_drijf_ingevuld = $datum_belangstelling;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld_van_drijf_gechecked:'. print_r($datum_drijfveren, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}	

  		if (!in_array($part_eventid, $kampids_all)) {
    		if ($extdebug == 1) { watchdog('php', '<pre>EXIT: NOT A PARTICIPANT OF CAMPS THIS YEAR</pre>', NULL, WATCHDOG_DEBUG); }
  			return; //    if not, get out of here
		}
	}

	if (in_array($groupID, array("139", "190"))) { 	// PART DEEL + PART LEID
		$entity_id = $contact_id;
	}
	if (in_array($groupID, array("103", "181"))) {	// TAB CURICULUM + TAB INTAKE
		$entity_id = $entityID;
	}
	if (in_array($groupID, array("139", "190"))) { 	// PART DEEL + PART LEID + PART LEID VOG
		#if ($extdebug == 1) { watchdog('php', '<pre>kamp:' . print_r($part_welkkamp, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>functie:' . print_r($part_functie, true) . '</pre>', null, WATCHDOG_DEBUG); }
	}
	if (in_array($groupID, array("103", "139", "190", "181"))) {
		if ($extdebug == 1) { watchdog('php', '<pre>contact_id:' . print_r($contact_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>entityID:' . print_r($entityID, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>entity_id:' . print_r($entity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# CHECK OF DEZE PERSOON DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING
    	if ($extdebug == 1) { watchdog('php', '<pre>CHECK OF '.$displayname.' DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		if (in_array($part_eventid, $kampidsdeel)) {
			if ($extdebug == 1) { watchdog('php', '<pre>FOUND EVENT_id ('.$part_eventid.') IN GENERATED ARRAY! - DITJAAR MEE ALS DEEL MET '.$part_welkkamp.'</pre>', NULL, WATCHDOG_DEBUG); }
			$ditjaardeel = 1;
		} else { $ditjaardeel = 0;}
		if (in_array($part_eventid, $kampidsleid)) {
			if ($extdebug == 1) { watchdog('php', '<pre>FOUND EVENT_id ('.$part_eventid.') IN GENERATED ARRAY! - DITJAAR MEE ALS '.$part_functie.' OP '.$part_welkkamp.'</pre>', NULL, WATCHDOG_DEBUG); }
			$ditjaarleid = 1;
		} else { $ditjaarleid = 0;}
		#if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeel:' . print_r($ditjaardeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleid:' . print_r($ditjaarleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# GET EVENT INFO TO RETREIVE HOOFDLEIDING
    	if ($extdebug == 1) { watchdog('php', '<pre>GET EVENT INFO TO RETREIVE HOOFDLEIDING [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		// zoek de hoofdleidingen van het kamp waar deze deelnemer of leider meegaat
  		if (in_array($part_eventtypeid, $eventypesdeel)) {		// EVENTTYPE = DEEL (afkorting kamp staat in initial_amount_label)
    		$result = civicrm_api3('Event', 'get', array(
      			'sequential'	=> 1,
      			'return'		=> array("id","start_date","custom_681", "custom_682", "event_type_id", "initial_amount_label"),
				'id'			=> $part_eventid, 				// eventid of specific kamp
    		));
    		$part_welkkamp = $result['values'][0]['initial_amount_label'];
    	}
  		if (in_array($part_eventtypeid, $eventypesleid)) {		// EVENTTYPE = LEID (zoek kamp waar leiding zich voor opgaf)
    		$result = civicrm_api3('Event', 'get', array(
      			'sequential'	=> 1,
      			'return'		=> array("id","start_date","custom_681", "custom_682", "event_type_id"),
				'id'			=> array('IN' => $kampidsdeel),	// gebruik de gevonden event_id's van de kampen van dit jaar
				'initial_amount_label' => $part_welkkamp,		// using this field as workaround instead of part custom field
    		));
    	}
    	$event_id 			= $result['values'][0]['id'];
    	$event_type_id 		= $result['values'][0]['event_type_id'];
		$event_startdate 	= $result['values'][0]['start_date'];

   		$event_hoofdleiding2_id	= $result['values'][0]['custom_682_id'];
    	if ($result['values'][0]['custom_681_id'])	{
    		$event_hoofdleiding1_id = $result['values'][0]['custom_681_id'];
    		$result = civicrm_api3('Contact', 'get', array(
      		'sequential' => 1,
      		'return' => array("display_name", "first_name"),
      		'id' => $event_hoofdleiding1_id,
    		));
    		$event_hoofdleiding1_displname = $result['values'][0]['display_name'];
    		$event_hoofdleiding1_firstname = $result['values'][0]['first_name'];
    	} else {
    		$event_hoofdleiding1_displname = 'hldn1';
    		$event_hoofdleiding1_firstname = 'hlfn1';
    	}
    	if ($event_hoofdleiding2_id)	{
    		$result = civicrm_api3('Contact', 'get', array(
      		'sequential' => 1,
      		'return' => array("display_name", "first_name"),
      		'id' => $event_hoofdleiding2_id,
    		));
    		$event_hoofdleiding2_displname = $result['values'][0]['display_name'];
    		$event_hoofdleiding2_firstname = $result['values'][0]['first_name'];
    	} else {
    		$event_hoofdleiding2_displname = 'hldn2';
    		$event_hoofdleiding2_firstname = 'hlfn2';
    	}

    	#if ($extdebug == 1) { watchdog('php', '<pre>part_welkkamp:' . print_r($part_welkkamp, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding1:' . print_r($event_hoofdleiding1, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding1_id:' . print_r($event_hoofdleiding1_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding1_displname:' . print_r($event_hoofdleiding1_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding1_firstname:' . print_r($event_hoofdleiding1_firstname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding2:' . print_r($event_hoofdleiding2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding2_id:' . print_r($event_hoofdleiding2_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding2_displname:' . print_r($event_hoofdleiding2_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding2_firstname:' . print_r($event_hoofdleiding2_firstname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		if ($exttag == 1) {
			#####################################################################################################
			# SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY
    		if ($extdebug == 1) { watchdog('php', '<pre>SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################
    	
			// #1 UPDATE the Deelnemer CV according to the tags and only if Deelnemer CV is empty or null
			$sql04          = "SELECT TG.description FROM civicrm_entity_tag ET INNER JOIN civicrm_tag TG ON ET.tag_id = TG.id WHERE ET.entity_id = '$entity_id' AND TG.name LIKE 'D%' ORDER BY TG.description ASC";
			$dao04          = CRM_Core_DAO::executeQuery($sql04);
			$welkejarendeel = array();
			while ($dao04->fetch()) {
				$welkejarendeel[] = $dao04->description;
				#if ($extdebug == 1) { watchdog('php', '<pre>dao04:'. print_r($dao04, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			}
			$tgdeel = count(array_filter($welkejarendeel));
			$cvdeel = implode('', $welkejarendeel);

			// #2 UPDATE the Leiding CV according to the tags and only if Leiding CV is empty or null
			$sql06          = "SELECT TG.description FROM civicrm_entity_tag ET INNER JOIN civicrm_tag TG ON ET.tag_id = TG.id WHERE ET.entity_id = '$entity_id' AND TG.name LIKE 'L%' ORDER BY TG.description ASC";
			$dao06          = CRM_Core_DAO::executeQuery($sql06);
			$welkejarenleid = array();
			while ($dao06->fetch()) {
				$welkejarenleid[] = $dao06->description;
				#if ($extdebug == 1) { watchdog('php', '<pre>dao06:'. print_r($dao06, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			}
			$tgleid = count(array_filter($welkejarenleid));
			$cvleid = implode('', $welkejarenleid);

			#if ($extdebug == 1) { watchdog('php', '<pre>tgdeel:'. print_r($tgdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>cvdeel:'. print_r($cvdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tgleid:'. print_r($tgleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>cvleid:'. print_r($cvleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#####################################################################################################
		# DESTILATE FIRST, LAST, COUNT ETC FROM CV AND UPDATE DB
    	if ($extdebug == 1) { watchdog('php', '<pre>DESTILATE FIRST, LAST, COUNT ETC FROM CV AND UPDATE DB [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		$eerstedeel  = 0;
		$laatstedeel = 0;
		$eersteleid  = 0;
		$laatsteleid = 0;

		#$arraydeel	 = explode("", $arraydeel);
		#$arrayleid	 = explode("", $arrayleid);
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel0:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid0:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>ditkaljaar:'. print_r($ditkaljaar, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($ditjaardeel == 1) {
			$part_functie = 'deelnemer';
   			if (empty($arraydeel)) {
   				$arraydeel = array($ditkaljaar); 
   			} else {
   				if (!in_array($ditkaljaar, $arraydeel)) {
   					array_push($arraydeel, $ditkaljaar);
   				}
   			}
   		}
		if ($ditjaarleid == 1) {
   			if (empty($arrayleid)) { 
   				$arrayleid = array($ditkaljaar);
   			} else {
   				if (!in_array($ditkaljaar, $arrayleid)) {
   					array_push($arrayleid, $ditkaljaar);
   				}
   			}
   		}
		if (!empty($arraydeel)) {
			#if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel2:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			$hoevaakdeel = count(array_filter($arraydeel));
			$welkedeel   = implode('', array_filter($arraydeel));
			#if ($hoevaakdeel == 1) {
				$welkedeel 	 = "".$welkedeel."";
			#}
			if ($hoevaakdeel > 0) {
				$eerstedeel  = min(array_filter($arraydeel));
				$laatstedeel = max(array_filter($arraydeel));
			}
		} else {
			$hoevaakdeel	= 0;
			$welkedeel 		= NULL;
			$eerstedeel 	= NULL;
			$laatstedeel 	= NULL;
		}
		#if ($extdebug == 1) { watchdog('php', '<pre>hoevaakdeel:'. print_r($hoevaakdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel_fin:'. print_r($welkedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if (!empty($arrayleid)) {
			#if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid2:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			$hoevaakleid = count(array_filter($arrayleid));
			$welkeleid   = implode('', array_filter($arrayleid));
			#if ($hoevaakleid == 1) {
				$welkeleid 	 = "".$welkeleid."";
			#}
			if ($hoevaakleid > 0) {
				$eersteleid  = min(array_filter($arrayleid));
				$laatsteleid = max(array_filter($arrayleid));
			}
		} else {
			$hoevaakleid	= 0;
			$welkeleid 		= NULL;
			$eersteleid 	= NULL;
			$laatsteleid 	= NULL;
		}
		#if ($extdebug == 1) { watchdog('php', '<pre>hoevaakleid:'. print_r($hoevaakleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid_fin:'. print_r($welkeleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		$totaalmee   = $hoevaakdeel + $hoevaakleid;
		$eerstekeer  = $hoevaakdeel > 0 ? $eerstedeel  : $eersteleid;
		$laatstekeer = $hoevaakleid > 0 ? $laatsteleid : $laatstedeel;

		if ($exttag == 1) {
			$tagverschildeel = $tgdeel - $hoevaakdeel;
			$tagverschilleid = $tgleid - $hoevaakleid;
		}
		#$welkejarendeelmin    = min(array_filter($welkejarendeel));
		#$welkejarenleidmin    = min(array_filter($welkejarenleid));
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeelmin:'. print_r($welkejarendeelmin, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleidmin:'. print_r($welkejarenleidmin, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>arraydeel:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>arrayleid:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>eerstedeel:'. print_r($eerstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>laatstedeel:'. print_r($laatstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>eersteleid:'. print_r($eersteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>laatsteleid:'. print_r($laatsteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>eerstekeer:'. print_r($eerstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>laatstekeer:'. print_r($laatstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		// M61: beware: hardcoded option group id
		$sql12 = "SELECT label AS kamplabel, value AS kampvalue FROM `civicrm_option_value` WHERE `option_group_id` = '386' AND value = '$part_welkkamp'";
		#if ($extdebug == 1) { watchdog('php', '<pre>sql12:' . print_r($sql12, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$dao12 = CRM_Core_DAO::executeQuery($sql12);
		#if ($extdebug == 1) { watchdog('php', '<pre>dao12:' . print_r($dao12, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		while ($dao12->fetch()) {
			$welkkamplang = $dao12->kamplabel;
			$welkkampkort = $dao12->kampvalue;
			$welkkampkort = strtolower($welkkampkort);
			if ($extdebug == 1) { watchdog('php', '<pre>welkkamplang:'. print_r($welkkamplang, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>welkkampkort:'. print_r($welkkampkort, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}

    	$params_contact = array(
			'contact_type' => 'Individual',
	   		'id'		   => $contact_id,
			'first_name'   => $first_name,
      		#'custom_896'   => $part_eerstexdeel,
      		#'custom_897'   => $part_eerstexleid,
      		'custom_865'   => $part_functie,
      		'custom_900'   => $welkkamplang, 
      		'custom_901'   => $welkkampkort,
      		'custom_938'   => $event_hoofdleiding1_displname,
      		'custom_939'   => $event_hoofdleiding2_displname,
      		'custom_951'   => $event_hoofdleiding1_firstname,
      		'custom_952'   => $event_hoofdleiding2_firstname,
      		'custom_474'   => $datum_drijf_ingevuld,
    	);
		if ($extdjcont == 1) {
			$result = civicrm_api3('Contact', 'create', $params_contact);
   				if ($extdebug == 1) { watchdog('php', '<pre>params_contact:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>params_contact EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		}

    	$params_participant = array(
      		'debug'        => 1,
			'event_id'	   => $part_eventid,
   			'id'           => $part_id,
   			'contact_id'   => $contact_id,
   			'custom_969'   => $part_functie,
   			'custom_949'   => $welkkamplang,
      		'custom_950'   => $welkkampkort,
      		'custom_961'   => $event_type_id,
      		'custom_962'   => $event_id,
      		'custom_944'   => $event_hoofdleiding1_displname,
      		'custom_945'   => $event_hoofdleiding2_displname,
      		'custom_953'   => $event_hoofdleiding1_firstname,
      		'custom_954'   => $event_hoofdleiding2_firstname,
    	);
		if ($extdjpart == 1) {
			$result = civicrm_api3('Participant', 'create', $params_participant);
   				if ($extdebug == 1) { watchdog('php', '<pre>params_participant:' . print_r($params_participant, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>params_participant EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		}

    	$params_cv = array(
			'contact_type' => 'Individual',
	   		'id'		   => $contact_id,
			'first_name'   => $first_name,

      		#'custom_376'   => $welkedeel, 
      		#'custom_382'   => $hoevaakdeel, 
      		#'custom_842'   => $eerstedeel,
      		#'custom_843'   => $laatstedeel,

      		#'custom_73'    => $welkeleid,
      		#'custom_74'    => $hoevaakleid, 
      		#'custom_844'   => $eersteleid,
      		#'custom_845'   => $laatsteleid,

      		'custom_846'   => $eerstekeer,
      		'custom_847'   => $laatstekeer,
      		'custom_458'   => $totaalmee,

      		#'custom_856'   => $cvdeel,
      		#'custom_848'   => $tgdeel,
      		#'custom_857'   => $cvleid,
      		#'custom_849'   => $tgleid,
      		#'custom_850'   => $tagverschildeel,
      		#'custom_851'   => $tagverschilleid,
    	);

    	if (isset($welkedeel) AND $hoevaakdeel > 0)	{ // voeg welkedeel alleen toe als het niet leeg is
	    	$params_cv['custom_376']	= $welkedeel;
	    	$params_cv['custom_382']	= $hoevaakdeel;
	    	$params_cv['custom_842']	= $eerstedeel;
	    	$params_cv['custom_843']	= $laatstedeel;
			if ($extdebug == 1) { watchdog('php', '<pre>array_add_welkedeel_376:' . print_r($welkedeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	}
    	if (isset($welkeleid) AND $hoevaakleid > 0)	{ // voeg welkeleid alleen toe als het niet leeg is
	    	$params_cv['custom_73']		= $welkeleid;
	    	$params_cv['custom_74']		= $hoevaakleid;
	    	$params_cv['custom_844']	= $eersteleid;
	    	$params_cv['custom_845']	= $laatsteleid;
			if ($extdebug == 1) { watchdog('php', '<pre>array_add_welkeleid_073:' . print_r($welkeleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	}

    	if (in_array($groupID, array("139", "190"))) {	// PART DEEL + PART LEID
			if ($extcv == 1) {
				$result = civicrm_api3('Contact', 'create', $params_cv);
   				if ($extdebug == 1) { watchdog('php', '<pre>params_cv:' . print_r($params_cv, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>params_cv EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
			}
   		}
   		if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG); }
			// ************************************************************************************************************
			// EXTENTION VOG
			// ************************************************************************************************************
   		if ($extvog == 1 AND $ditjaarleid AND ($groupID == 190 OR $groupID == 181)) {	// PART LEID & TAB INTAKE
			if ($extdebug == 1) { watchdog('php', '<pre>*** START EXTENSION VOG [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', NULL, WATCHDOG_DEBUG); }
      		#if ($extdebug == 1) { watchdog('php', '<pre>vogrecent:'. print_r($vogrecent, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($vogrecent) {
    			#$date1				= date_create($event_startdate);
    			#$date2				= date_create($vogrecent);
    			#$diff 				= date_diff($date1,$date2);
    			#$diffyears			= $diff->y;
    			#$diffmonths		= $diff->m;
    			#$diffmonthstotal	= $diffmonths + (12*$diffyears);
    			#if ($extdebug == 1) { watchdog('php', '<pre>diffmonthstotal:'. print_r($diffmonthstotal, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

    			if ($vogrecent AND strtotime($vogrecent) < strtotime($fiscalyear_start) AND strtotime($vogrecent) >= strtotime($grensvognoggoed)) { // Datum VOG in previous 2 fiscal years
    				$vogdatethisyear = 0;
    				$vognodig = 'noggoed';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] VALT BINNEN DE VOORGAANDE 2 FISCALE JAREN ['.$grensvognoggoed.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($vogrecent AND strtotime($vogrecent) >= strtotime($fiscalyear_start))	{ // Datum VOG binnen het huidige fiscal year
    				$vogdatethisyear = 0;
    				if ($hoevaakleid > 1) { $vognodig = 'opnieuw'; } else { $vognodig = 'eerstex'; }
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] VALT BINNEN HET HUIDIGE FISCAL YEAR ['.$fiscalyear_start.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($vogrecent AND strtotime($vogrecent) < strtotime($grensvognoggoed))		{ // Datum VOG ouder dan 3 fiscale jaren
    				$vogdatethisyear = 0;
    				$vognodig = 'opnieuw';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] IS OUDER DAN DE START VAN DE VOORGAANDE 2 FISCALE JAREN ['.$grensvognoggoed.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}

    			#if ($vogrecent && $diffmonthstotal >  34)   								{ $vognodig = 'opnieuw'; }
    			#if ($vogrecent && $diffmonthstotal <= 34 && $vogrecent < $fiscalyear_start) { $vognodig = 'noggoed'; }
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(vogrecent):' . print_r(strtotime($vogrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(fiscalyear_start):' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>vogrecent:' . print_r($vogrecent, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		} else {
    			$vognodig = 'eerstex';
    		}
    		if ($extdebug == 1) { watchdog('php', '<pre>vognodig1:'. print_r($vognodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		#if ($hoevaakleid == 1)							{ $vognodig = 'eerstex'; }
    		#if ($extdebug == 1) { watchdog('php', '<pre>hoevaakleid:'. print_r($hoevaakleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		#if ($extdebug == 1) { watchdog('php', '<pre>vognodig2:'. print_r($vognodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($extdebug == 1) { watchdog('php', '<pre>part_functie:'. print_r($part_functie, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($part_functie == 'hoofdleiding')			{ $vognodig = 'elkjaar'; }
    		if ($part_functie == 'bestuurslid')	 			{ $vognodig = 'elkjaar'; }
    		if ($extdebug == 1) { watchdog('php', '<pre>vognodig3:'. print_r($vognodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		
   			// WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ
    		if ($extvog == 1 AND $part_vogdatum AND empty($vogrecent)) {
    			$params_vog_tab = array(
					'contact_type' => 'Individual',
	   				'id'		   => $contact_id,
					'first_name'   => $first_name,
      				'custom_56'    => $part_vogdatum,
      				'custom_68'    => $part_vogkenmerk,
    			);
   				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_tab:' . print_r($params_vog_tab, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			if ($groupID == 190) { 	// UPDATE TAB (INTAKE) BIJ EDIT VAN PART LEID (indien er een recente vog datum is)
					if ($extvog == 1)	{ $result = civicrm_api3('Contact', 'create', $params_vog_tab); }
					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_tab EXECUTED [groupID: '.$groupID.'] [vogdatum: '.$part_vogdatum.']</pre>', NULL, WATCHDOG_DEBUG); }
   				}
   			}
   			// WERK DE GEGEVENS IN PART LEID VOG BIJ
   			if ($extvog == 1 AND $vognodig) { // vognodig zou altijd gevuld moeten zijn
				$params_vog_part = array(
      				'event_id'	   => $part_eventid,
   					'id'           => $part_id,
   					#'contact_id'   => $contact_id,
   					'custom_586'   => $vognodig,
    			);
    			if ($vognodig == 'noggoed') {
					#$params_vog_part['custom_603'] = $vogrecent;
					#$params_vog_part['custom_602'] = $vogkenmerk;
				}
   				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_part:' . print_r($params_vog_part, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($vogrecent AND $vognodig == 'noggoed') {
    				#if ($groupID == 181) { 	// UPDATE PART LEID VOG BIJ EDIT VAN TAB (INTAKE) (indien de vog nog goed is)
					if ($extvog == 1)	{ $result = civicrm_api3('Participant', 'create', $params_vog_part); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_part_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_part EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
				#}
   			}
   			// WERK DE VOG ACTIVITIES BIJ MET DE JUISTE STATUS
		if ($extvog == 1 AND ($groupID == 190 OR $groupID == 181) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {

   			if ($extdebug == 1) { watchdog('php', '<pre>--- VOG ACTIVITIES [GET] [groupID: '.$groupID.'] [op: '.$op.'] ---</pre>', NULL, WATCHDOG_DEBUG); }
			// ************************************************************************************************************
			// GET ACTIVITIES MBT. VOG
			// ************************************************************************************************************
			// GET ACTIVITIES 'VOG VERZOEK'
   			$params_vog_activity_verzoek_get = array(		// zoek activities 'VOG verzoek'
  				'sequential' => 1,
  				'return' => array("id", "activity_date_time", "status_id", "subject"),
  				'target_contact_id' => $contact_id,
  				'activity_type_id' => "VOG verzoek",
  				'activity_date_time' => array('>' => $fiscalyear_start),
  			);
  			#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_verzoek_get:' . print_r($params_vog_activity_verzoek_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			$result = civicrm_api3('Activity', 'get', $params_vog_activity_verzoek_get);
  			#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_verzoek_get_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			#if ($extdebug == 1) { watchdog('php', '<pre>result_count_verzoek:' . print_r($result['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			if ($result['count'] == 1) {
  				$vogverzoek_activity_id		= $result['values'][0]['id'];
  				$vogverzoek_activity_status	= $result['values'][0]['status_id'];
	  			if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			} else {
				$vogverzoek_activity_id		= NULL;
  				$vogverzoek_activity_status	= NULL;
  			}
			// GET ACTIVITIES 'VOG AANVRAAG'
  			$params_vog_activity_aanvraag_get = array(		// zoek activities 'VOG aanvraag'
   				'sequential' => 1,
  				'return' => array("id", "activity_date_time", "status_id", "subject"),
  				'target_contact_id' => $contact_id,
  				'activity_type_id' => "VOG aanvraag",
  				'activity_date_time' => array('>' => $fiscalyear_start),
  			);
  			#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_aanvraag_get:' . print_r($params_vog_activity_aanvraag_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			$result = civicrm_api3('Activity', 'get', $params_vog_activity_aanvraag_get);
  			#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_aanvraag_get_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			#if ($extdebug == 1) { watchdog('php', '<pre>result_count_aanvraag:' . print_r($result['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			if ($result['count'] == 1) {
  				$vogaanvraag_activity_id		= $result['values'][0]['id'];
  				$vogaanvraag_activity_status	= $result['values'][0]['status_id'];
	  			if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			} else {
				$vogaanvraag_activity_id		= NULL;
  				$vogaanvraag_activity_status	= NULL;
  			}
  			// GET ACTIVITIES 'VOG ONTVANGST'
   			$params_vog_activity_ontvangst_get = array(		// zoek activities 'VOG ontvangst'
  				'sequential' => 1,
  				'return' => array("id", "activity_date_time", "status_id", "subject"),
  				'target_contact_id' => $contact_id,
  				'activity_type_id' => "VOG ontvangst",
  				'activity_date_time' => array('>' => $fiscalyear_start),
  			);
  			#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_ontvangst_get:' . print_r($params_vog_activity_ontvangst_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			$result = civicrm_api3('Activity', 'get', $params_vog_activity_ontvangst_get);
  			#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_ontvangst_get_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			#if ($extdebug == 1) { watchdog('php', '<pre>result_count_ontvangst:' . print_r($result['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			if ($result['count'] == 1) {
  				$vogontvangst_activity_id		= $result['values'][0]['id'];
  				$vogontvangst_activity_status	= $result['values'][0]['status_id'];
	  			if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			} else {
				$vogontvangst_activity_id		= NULL;
  				$vogontvangst_activity_status	= NULL;
  			}
			// ************************************************************************************************************
			// CREATE ACTIVITIES
			// ************************************************************************************************************
			// CREATE AN ACTIVITY 'VERZOEK' ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
			if ($extdebug == 1) { watchdog('php', '<pre>--- VOG ACTIVITIES [CREATE] [groupID: '.$groupID.'] [op: '.$op.'] ---</pre>', NULL, WATCHDOG_DEBUG); }

			if ($part_vogingediend) {
				$datum_aanvraag = $part_vogingediend;
			} else {
				$newdate		 = strtotime ( '+14 day' , strtotime ( $part_vogverzocht ) ) ;
				$datum_aanvraag = date ( 'Y-m-d H:i:s' , $newdate );
			}
			if (strtotime($datum_aanvraag) >= strtotime($event_startdate))	{ $datum_aanvraag  = date($event_startdate, strtotime("-1 week")); } // als datum aanvraag > start kamp is, dan scheduled datum aanvraag = start kamp - 1wk 

			if ($part_vogontvangst) { // VOG-ontvangst is datum van ontvangst, indien leeg dan datum ingediend of datum verzocht (zou ook datum vog kunnen zijn)
				$datum_ontvangst = $part_vogontvangst;
			} elseif ($part_vogingediend) {
				$newdate		 = strtotime ( '+28 day' , strtotime ( $part_vogingediend ) ) ;
				$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
			} else {
				$newdate		 = strtotime ( '+42 day' , strtotime ( $part_vogverzocht ) ) ;
				$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
			}
			if (strtotime($datum_ontvangst) >= strtotime($event_startdate))	{ $datum_ontvangst  = date($event_startdate, strtotime("-1 week")); } // als datum aanvraag > start kamp is, dan scheduled datum ontvangst = start kamp - 1wk	
			if ($extdebug == 1) { watchdog('php', '<pre>part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_vogontvangst:' . print_r($part_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_vogdatum:' . print_r($part_vogdatum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>scheduled_datum_aanvraag:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>scheduled_datum_ontvangst:' . print_r($datum_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// CREATE AN ACTIVITY 'VERZOEK' ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
			if (empty($vogverzoek_activity_id) AND  $part_vogverzocht) {
  				$params_vog_activity_create_verzoek = array(		// update VOG aanvraag naar Completed als VOG ontvangst Completed is
  					#"debug"					=> 1,
  					"source_contact_id"		=> 1,
  					"target_id"				=> $contact_id,
  					'status_id'				=> "Completed",
  					'activity_type_id' 		=> "VOG verzoek",
  					'subject' 				=> "VOG aanvraag verzocht",
  					'activity_date_time'	=> $part_vogverzocht,
  				);
  				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek:' . print_r($params_vog_activity_create_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1)	{ $result = civicrm_api3('Activity', 'create', $params_vog_activity_create_verzoek); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if (empty($vogverzoek_activity_id))		{ $vogverzoek_activity_id		= key($result['values']); }
				if (empty($vogverzoek_activity_status))	{ $vogverzoek_activity_status	= 1; }
				if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id2:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status2:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			// CREATE AN ACTIVITY 'AANVRAAG' ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
  			if (empty($vogaanvraag_activity_id) AND $datum_aanvraag AND ($part_vogverzocht OR $part_vogingediend)) {
  				$params_vog_activity_create_aanvraag = array(
  					"source_contact_id"		=> 1,
   					"target_id"				=> $contact_id,
  					'status_id'				=> "Scheduled",
  					'activity_type_id' 		=> "VOG aanvraag",
  					'subject' 				=> "VOG aanvraag ingediend",
  					'activity_date_time'	=> $datum_aanvraag,
  				);
  				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag:' . print_r($params_vog_activity_create_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1)	{ $result = civicrm_api3('Activity', 'create', $params_vog_activity_create_aanvraag); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if (empty($vogaanvraag_activity_id))		{ $vogaanvraag_activity_id		= key($result['values']); }
				if (empty($vogaanvraag_activity_status))	{ $vogaanvraag_activity_status	= 1; }
				if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id2:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status2:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}

			// CREATE AN ACTIVITY 'ONTVANGST' ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
			if (empty($vogontvangst_activity_id) AND $datum_ontvangst AND ($part_vogverzocht OR $part_vogingediend)) {
  				$params_vog_activity_create_ontvangst = array(		// update VOG aanvraag naar Completed als VOG ontvangst Completed is
  					"source_contact_id"		=> 1,
  					"target_id"				=> $contact_id,
  					'status_id'				=> "Scheduled",
  					'activity_type_id' 		=> "VOG ontvangst",
  					'subject' 				=> "VOG ontvangst bevestigd",
  					'activity_date_time'	=> $datum_ontvangst,
  				);
  				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst:' . print_r($params_vog_activity_create_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1)	{ $result = civicrm_api3('Activity', 'create', $params_vog_activity_create_ontvangst); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if (empty($vogontvangst_activity_id))		{ $vogontvangst_activity_id			= key($result['values']); }
				if (empty($vogontvangst_activity_status))	{ $vogontvangst_activity_status		= 1; }
				if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id2:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status2:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			// ************************************************************************************************************
			// UPDATE ACTIVITIES
			// ************************************************************************************************************
			if ($extdebug == 1) { watchdog('php', '<pre>--- VOG ACTIVITIES [UPDATE] [groupID: '.$groupID.'] [op: '.$op.'] ---</pre>', NULL, WATCHDOG_DEBUG); }
			#$todaydatetime = date("Y-m-d H:i:s");
			#if ($extdebug == 1) { watchdog('php', '<pre>part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>todaydatetime:' . print_r($todaydatetime, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			if (strtotime($part_vogverzocht) >= strtotime($fiscalyear_start))		{ // alleen idien vogverzocht binnen huidige fiscale jaar valt
				$diffsinceverzoek	= date_diff(date_create($part_vogverzocht),date_create($todaydatetime));
				$dayssinceverzoek	= $diffsinceverzoek->format('%a');

				if ($dayssinceverzoek >= 0  AND $dayssinceverzoek < 14)				{ $status_aanvraag  = "Scheduled"; }
				if ($dayssinceverzoek >= 14 AND $dayssinceverzoek < 21)				{ $status_aanvraag  = "Left Message"; }
				if ($dayssinceverzoek >= 21 AND $dayssinceverzoek < 30)				{ $status_aanvraag  = "Unreachable"; }
				if ($dayssinceverzoek >= 30)										{ $status_aanvraag  = "No_show"; }
				if (strtotime($part_vogingediend) >= strtotime($fiscalyear_start))	{ $status_aanvraag  = "Completed"; }
				if (strtotime($part_vogdatum)	  >= strtotime($fiscalyear_start))	{ $status_aanvraag  = "Completed"; }	// als part_vogdatum binnen huidige fiscal year valt kan activity op completed

				#if ($extdebug == 1) { watchdog('php', '<pre>todaydatetime:' . print_r($todaydatetime, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>diffsinceverzoek:' . print_r($diffsinceverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>dagen_sinds_verzoek:' . print_r($dayssinceverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>status_aanvraag:' . print_r($status_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				$diffsinceaanvraag	= date_diff(date_create($part_vogingediend),date_create($todaydatetime));
				$dayssinceaanvraag	= $diffsinceaanvraag->format('%a');
				if ($dayssinceaanvraag >= 0  AND $dayssinceaanvraag < 28)			{ $status_ontvangst = "Scheduled"; }
				if ($dayssinceaanvraag >= 28 AND $dayssinceaanvraag < 42)			{ $status_ontvangst = "Left Message"; }
				if ($dayssinceaanvraag >= 42)										{ $status_ontvangst = "Unreachable"; }
				if ($dayssinceverzoek  >= 21 AND $status_ontvangst == "Scheduled")	{ $status_ontvangst = "Left Message"; } // als aanvraag Unreachable of No Show is -> schedule dan alsnog een reminder rond geplande ontvangstdatum
				if (strtotime($todaydatetime) > strtotime($event_startdate))		{ $status_aanvraag  = "No_show"; }		// No show nadat de startdag van kamp gepasseerd is
				if (strtotime($part_vogdatum) >= strtotime($fiscalyear_start))		{ $status_ontvangst = "Completed"; } 	// als part_vogdatum binnen huidige fiscal year valt kan activity op completed

				if ($extdebug == 1) { watchdog('php', '<pre>part_vogontvangst:' . print_r($part_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>part_vogdatum:' . print_r($part_vogdatum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>diffsinceaanvraag:' . print_r($diffsinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>dagen_sinds_aanvraag:' . print_r($dayssinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>status_ontvangst:' . print_r($status_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			// UPDATE ACTIVITY ONTVANGST STATUS N.A.V. DAYS SINCE...
			#if ((in_array($vogaanvraag_activity_status, array("1", "4", "5", "8")) AND in_array($vogontvangst_activity_status, array("2"))) AND $part_vogingediend) {
			if ($vogaanvraag_activity_id AND $datum_aanvraag AND $status_aanvraag) {
  				$params_vog_activity_change_aanvraag = array(
  					'id'					=> $vogaanvraag_activity_id,
  					'activity_type_id'		=> "VOG aanvraag",
  					'activity_date_time'	=> $datum_aanvraag,
 					'subject' 				=> "VOG aanvraag ingediend",
   					'status_id'				=> $status_aanvraag,
  				);
  				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag:' . print_r($params_vog_activity_change_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1)	{ $result = civicrm_api3('Activity', 'create', $params_vog_activity_change_aanvraag); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			// UPDATE ACTIVITY ONTVANGST STATUS NAV DAYS SINCE...
			#if ((in_array($vogontvangst_activity_status, array("1", "4", "5", "8")) AND in_array($vogaanvraag_activity_status, array("2"))) AND $part_vogdatum AND $vognodig != 'noggoed') {
			if ($vogontvangst_activity_id AND $datum_ontvangst AND $status_ontvangst) {
  				$params_vog_activity_change_ontvangst = array(
  					'id'					=> $vogontvangst_activity_id,
  					'activity_type_id'		=> "VOG ontvangst",
  					'activity_date_time'	=> $datum_ontvangst,
  					'subject' 				=> "VOG ontvangst bevestigd",
  					'status_id'				=> $status_ontvangst,
  				);
  				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst:' . print_r($params_vog_activity_change_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1)	{ $result = civicrm_api3('Activity', 'create', $params_vog_activity_change_ontvangst); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION VOG [groupID: '.$groupID.'] [op: '.$op.'] [kampleider: '.$displayname.'] ***</pre>', NULL, WATCHDOG_DEBUG); }
		}
    	}
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

?>