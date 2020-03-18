<?php

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once 'curriculum.civix.php';

function curriculum_civicrm_custom($op, $groupID, $entityID, &$params) {

	#civicrm_api3('Contact', 'getfields', array('cache_clear' => 1));
	#civicrm_api3('Participant', 'getfields', array('cache_clear' => 1));
	#CRM_Core_PseudoConstant::flush();

	$extdebug	= 1;
	$extcv 		= 1;
	$extdjcont 	= 1;
	$extdjpart	= 1;
	$exttag		= 0;
	$extrel		= 1;
	$extvog		= 1;
	$extact		= 1;
	$extref		= 1;

	if ($op != 'create' && $op != 'edit') { //    did we just create or edit a custom object?
  		if ($extdebug == 1) { watchdog('php', '<pre>EXIT: op != create OR op != edit</pre>', NULL, WATCHDOG_DEBUG); }
		return; //	if not, get out of here
	}

    if (in_array($groupID, array("139", "190", "140"))) { // CV & PART
		// 101  EVENT KENMERKEN
		// 139	PART DEEL
		// 190	PART LEID
		// (140	PART LEID VOG)
		// 106	TAB  WERVING
		// 103	TAB  CURRICULUM
		// 149  TAB  TALENT
		// 150	TAB  PROMOTIE
		// 165	PART REFERENTIE
		// 213  PART REF
		// 205  PART 
    	$processcurriculum = 1;
    } else {
        return; //	if not, get out of here
    }

    if (in_array($groupID, array("149", "139", "190", "140", "165", "213"))) { // CV & PART
    	if ($extdebug == 1) { watchdog('php', '<pre>*** 1. START EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] [entityID: '.$entityID.'] ***</pre>', null, WATCHDOG_DEBUG); }

    	$diffyears				= 0;
    	$eventtype 				= 0;
		$tagnr_deel 			= 0;
		$tagcv_deel 			= 0;
   		$tagcv_leid 			= 0;
   		$tagnr_leid 			= 0;
   		$tagverschildeel		= 0;
   		$tagverschilleid		= 0;
		$welkkamplang 			= 0;
		$welkkampkort 			= 0;
		$contact_id 			= NULL;
		$event_type_id 			= 0;
		$ditjaardeelyes 		= 0;
		$ditjaardeelmss 		= 0;
		$ditjaardeelnot 		= 0;
		$ditjaarleidyes			= 0;
		$ditjaarleidmss 		= 0;
		$ditjaarleidnot 		= 0;
		$arraydeel 				= array();
		$arrayleid 				= array();
		$ditkaljaar 			= date("Y");
		$partstatusyes 			= array(1,2,5,6,15,16);								//	PARTICIPANT STATUS BETEKENT DEELNAME = YES
		$partstatusnot 			= array(4);											//	PARTICIPANT STATUS BETEKENT DEELNAME = NO (GECANCELLED)
		$partstatusmss			= array(4,7,8,9,10);								//	PARTICIPANT STATUS BETEKENT DEELNAME = (NOG) NIET YES (IVM GECANCELLED, WACHTLIJST OF GOEDKEURING)
		$eventypesdeel 			= array(11, 12, 13, 14, 21, 22, 23, 24, 33, 102);	//	EVENT_TYPE_ID'S VAN DE KAMPEN VAN DIT JAAR 			(- TEST_DEEL)
		$eventypesleid 			= array(1, 101);									//	EVENT_TYPE_ID VAN HET LEIDING EVENT VAN DIT JAAR 	(- TEST_LEID)
		$eventypesdeeltest 		= array(102);	
		$eventypesleidtest 		= array(101);	
		$vognodig 				= NULL;
		$refnodig 				= NULL;
		$part_refingevuld  		= NULL;
		$rel_refingevuld  		= NULL;
		$datum_drijf_ingevuld 	= NULL;
		$drupal_name 			= NULL;
		$part_eventtypeid		= NULL;
		$params_contact 		= NULL;
		$params_participant 	= NULL;

		#####################################################################################################
		# 1.0 BEPAAL DE GRENZEN VAN HET HUIDIGE FISCALE JAAR
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.0 BEPAAL DE GRENZEN VAN HET HUIDIGE FISCALE JAAR [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		$cache_fiscalyear_start = Civi::cache()->get('cache_fiscalyear_start');
		$cache_fiscalyear_end 	= Civi::cache()->get('cache_fiscalyear_end');

		if ($extdebug == 1) { watchdog('php', '<pre>cache fiscalyear_start:' . print_r($cache_fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>cache fiscalyear_end:' . print_r($cache_fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		// find event_id's of camps of current year (find them by event_type_id)
		$config = CRM_Core_Config::singleton( );
		$fiscalYearStart = $config->fiscalYearStart;
		$todaydatetime = date("Y-m-d");
		#if ($extdebug == 1) { watchdog('php', '<pre>fiscalYearStart:' . print_r($fiscalYearStart, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>today:' . print_r($today, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$fiscalyear_start = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y");
		$fiscalyear_end   = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y");

    	#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start 0:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	if (strtotime($fiscalyear_start) > strtotime($todaydatetime)) {
  			$fiscalyear_start = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("-1 year"));
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start -1:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  		}
		$grensvognoggoed = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("-3 year")); // VOG noggoed als datum binnen priveous 2 fiscal year valt
		$grensrefnoggoed = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("-3 year")); // REF noggoed als datum binnen priveous 2 fiscal year valt
  		#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end 0:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if (strtotime($fiscalyear_end)   <= strtotime($todaydatetime)) {
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end :' . print_r(strtotime($fiscalyear_end), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			#if ($extdebug == 1) { watchdog('php', '<pre>today:' . print_r(strtotime($todaydatetime), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			$fiscalyear_end = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("+1 year"));
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end +1 year:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			#$fiscalyear_end = strtotime ( '-1 day' , strtotime ( '$fiscalyear_end' ) ) ; // M61: hier nog een datum minus 1 day maken die werkt
  			#$fiscalyear_end = date ( 'Y-m-d' , $fiscalyear_end );
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end -1 day:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		}
		$ditkampjaar = date('Y', strtotime($fiscalyear_end)); // M61: zou misschien gewoon jaar van event_date moeten worden
		if ($extdebug == 1) { watchdog('php', '<pre>ditkampjaar:' . print_r($ditkampjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		# ATTEMPTY TO RETREIVE FISCAL YEAR START VALUE FROM CACHE

		Civi::cache()->set('cache_fiscalyear_start', $fiscalyear_start);
		Civi::cache()->set('cache_fiscalyear_end', $fiscalyear_end);

		$cache_fiscalyear_start = Civi::cache()->get('cache_fiscalyear_start');
		$cache_fiscalyear_end 	= Civi::cache()->get('cache_fiscalyear_end');

		# ATTEMPTY TO RETREIVE FISCAL YEAR START VALUE FROM CACHE

		if ($extdebug == 1) { watchdog('php', '<pre>cache_fiscalyear_start:' . print_r($cache_fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>cache_fiscalyear_end:' . print_r($cache_fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.1 VIND ALLE EVENTS LEIDING & DEELNEMERS VOOR DIT JAAR
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.1 VIND ALLE EVENT LEIDING & DEELNEMERS VOOR DIT JAAR [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

   		$result_deel = civicrm_api3('Event', 'get', array(	// vind info over events deelnemers
   			'sequential'	=> 1,
      		'return'		=> array("id","title"),
			'is_template'	=> 0,
			'event_type_id'	=> array('IN' => $eventypesdeel),
			'title' 		=> ['NOT LIKE' => "TEST"],
			#'event_start_date_relative' => "this.fiscal_year",
			'start_date'	=> array('>' => $fiscalyear_start),
			'end_date'		=> array('<' => $fiscalyear_end),
    	));
    	#if ($extdebug == 1) { watchdog('php', '<pre>events deel:' . print_r($result_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$kampidsdeel 		= array(); //  CREATE THE ARRAY WITH DEEL IDS
    	$kampidsdeelcount 	= $result_deel['count']-1;
  		for ($i = 0; $i <= $kampidsdeelcount; $i++) {
      		$kamp_id = $result_deel['values'][$i]['id'];
      		$kampidsdeel[] = $kamp_id;
  		}
  		ksort($kampidsdeel);

   	    $result_leid = civicrm_api3('Event', 'get', array(	// vind info over events leiding
   			'sequential'	=> 1,
      		'return'		=> array("id","title"),
			'is_template'	=> 0,
			'event_type_id'	=> array('IN' => $eventypesleid),
			'title' 		=> ['NOT LIKE' => "TEST"],
			#'event_start_date_relative' => "this.fiscal_year",
			'start_date' 	=> array('>' => $fiscalyear_start),
			'end_date' 		=> array('<' => $fiscalyear_end),
    	));
    	#if ($extdebug == 1) { watchdog('php', '<pre>events leid:' . print_r($result_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	$kampidsleid[] 		= $result_leid['values'][0]['id'];
    	$kampidsdeelcount 	= $result_leid['count'];
  		ksort($kampidsleid);

  		$kampids_all = $kampidsdeel;
  		array_push($kampids_all, $kampidsleid[0]);

		if ($extdebug == 1) { watchdog('php', '<pre>kampidsdeelcount:' . print_r($kampidsdeelcount, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampidsdeel:' . print_r($kampidsdeel, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampidsleidcount:' . print_r($kampidsleidcount, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampidsleid:' . print_r($kampidsleid, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampids_all:' . print_r($kampids_all, true) . '</pre>', null, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.2 CHECK PARTICIPANT STATUS FOR ALL KAMPIDS
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.2 CHECK PARTICIPANT STATUS [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

    	# A. als partitipant wordt gewerkt dan zoek dan de participant info van dat event
    	# B. indien participant die wordt bewerkt is gecancelled dan kijken of er een andere registratie is die niet gecancceled is
    	# C. indien contact wordt geedit (en geen participant), dan kijken of er een actieve registratie in dit fiscal jaar is
    	# D. indien dit allemaal neit het geval is, gewoon basic info ophalen voor het contact

    	if (in_array($groupID, array("139", "190", "140"))) {	// PART DEEL + PART LEID + PART LEID VOG
    		if ($extdebug == 1) { watchdog('php', '<pre>ZOEK DE RELEVANTE VELDEN VOOR DEZE REGISTRATIE [PartID: '.$entityID.']</pre>', NULL, WATCHDOG_DEBUG); }
    		$params_partinfo = [
    			'debug'			=> 1,
      			'sequential'	=> 1,
      			'return' 		=> array("id","contact_id","first_name","event_id","participant_status_id", "participant_role_id", "custom_592", "custom_593", "custom_892", "custom_649","custom_567","custom_568","custom_56","custom_68","custom_599","custom_600","custom_959","custom_601", "custom_603","custom_602","custom_1004","custom_1003","custom_1021","custom_1018","custom_703","custom_707","custom_706","custom_1038"),
      			'status_id'		=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "On waitlist", "Pending from waitlist", "Awaiting approval", "Partially paid", "Pending refund", "Cancelled"),
      			'id'			=> $entityID,
    		];
    		try{
   				#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo1:' . print_r($params_partinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_part = civicrm_api3('Participant', 'get', $params_partinfo);
   				#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo1_result:' . print_r($result_part, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			catch (CiviCRM_API3_Exception $e) {
   				// Handle error here.
   				$errorMessage 	= $e->getMessage();
   				$errorCode 		= $e->getErrorCode();
   				$errorData 		= $e->getExtraParams();
   				if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# BEKIJK NU OF ER INDERDAAD EEN GELDIG PARTICIPANT RECORD IS
			#####################################################################################################
			if ($result_part['count'] == 0) {
				if ($extdebug == 1) { watchdog('php', '<pre>ERROR: GEEN PARTICIPANT INFO GEVONDEN</pre>', NULL, WATCHDOG_DEBUG); }
			}
			if ($result_part['count'] == 1) {
				if ($extdebug == 1) { watchdog('php', '<pre>SUCCESS: 1 PARTICIPANT RECORD GEVONDEN</pre>', NULL, WATCHDOG_DEBUG); }
				$part_status_id	= $result_part['values'][0]['participant_status_id'];
			}
			if ($result_part['count'] > 1) {
				if ($extdebug == 1) { watchdog('php', '<pre>ERROR: MEER DAN 1 PARTICIPANT RECORDS GEVONDEN</pre>', NULL, WATCHDOG_DEBUG); } // M61: hier juiste vd 2 bepalen
				return; //    if not, get out of here
			}
			#####################################################################################################
			# BEPAAL HET CONTACT ID
			#####################################################################################################
			if ($result_part['count'] >= 1) {
				$contact_id = $result_part['values'][0]['contact_id'];
			} else {
				$contact_id = $entityID;
			}
			if ($extdebug == 1) { watchdog('php', '<pre>contact_id_0:'. print_r($contact_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    	}
		#####################################################################################################
		# INDIEN (DIT JAAR) GECANCELLED ZOEK OF ER EEN ANDERE GELDIGE REGISTRATIE IS VOOR DIT JAAR
		#####################################################################################################
		if (in_array($groupID, array("103", "149")) OR $part_status_id == 4) {	// TAB WERVING of DEELNAME CANCELLED
   			$params_partinfo = [
      			'sequential' 	=> 1,
      			'return' 		=> array("id","contact_id","first_name","event_id","participant_status_id", "participant_role_id", "custom_592", "custom_593", "custom_892", "custom_649","custom_567","custom_568","custom_56","custom_68","custom_599","custom_600","custom_959","custom_601", "custom_603","custom_602","custom_1004","custom_1003","custom_1021","custom_1018","custom_703","custom_707","custom_706","custom_1038"),
      			'status_id' 	=> array("Registered","Deelgenomen","Pending from pay later","Pending from incomplete transaction","On waitlist","Pending from waitlist","Partially paid","Pending refund"),
      			'event_id' 		=> array('IN' => $kampids_all),	// gebruik de gevonden event_id's van de kampen van dit jaar
				#'event_start_date_relative' => "this.fiscal_year",
      			'start_date' 	=> ['>' => $fiscalyear_start],
      			'end_date' 		=> ['>' => $fiscalyear_end],
    		];
    		if ($part_status_id == 4) {
				if ($extdebug == 1) { watchdog('php', '<pre>PARTICIPANT STATUS: GECANCELLED. ZOEK ALSNOG EEN GELDIGE REGISTRATIE VOOR DIT JAAR</pre>', NULL, WATCHDOG_DEBUG); }
				$params_partinfo['id'] 			= $entityID;
			}
			if (in_array($groupID, array("103", "149"))) {	// TAB CURICULUM + TAB WERVING
				if ($extdebug == 1) { watchdog('php', '<pre>GEEN PARTICIPANT ID BESCHIKBAAR. ZOEK ALSNOG EEN GELDIGE REGISTRATIE VOOR DIT JAAR</pre>', NULL, WATCHDOG_DEBUG); }
				$params_partinfo['contact_id'] 	= $contact_id;
			}
    		if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo2:' . print_r($params_partinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result_part = civicrm_api3('Participant', 'get', $params_partinfo);
			if ($result_part['count'] == 1) {
				if ($extdebug == 1) { watchdog('php', '<pre>(ALSNOG) PARTICIPANT INFO GEVONDEN!</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo2_result:' . print_r($result_part, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
    	}
   		#####################################################################################################
		# GET CONTACT INFO WHEN TABS ARE CHECKED INSTEAD OF PARTICIPANT INFO IS EDITED
		#####################################################################################################
		$params_contactinfo = [
   			'debug'			=> 1,
   			'sequential'	=> 1,
   		];
		$params_contactinfo['return'] 		= array("id","contact_id","first_name","display_name","job_title","custom_376","custom_73","custom_74","custom_647","custom_474","custom_663");
		$params_contactinfo['contact_id'] 	= $contact_id;
/*
		if ($result_part['count'] >= 1) {
			$params_contactinfo['contact_id'] 	= $contact_id;
		} else {
			$params_contactinfo['contact_id'] 	= $entityID;
		}
*/
		#if ($extdebug == 1) { watchdog('php', '<pre>params_contactinfo:' . print_r($params_contactinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result_cont = civicrm_api3('Contact', 'get', $params_contactinfo);
		#if ($extdebug == 1) { watchdog('php', '<pre>params_contactinfo_result:' . print_r($result_cont, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.3 ASIGN RETREIVED VALUES TO VARIABLES
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.3 ASIGN RETREIVED VALUES TO VARIABLES [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		$contact_id 			= $result_cont['values'][0]['contact_id'];
		$first_name				= $result_cont['values'][0]['first_name'];
 		$displayname 			= $result_cont['values'][0]['display_name'];	// displayname van contact
 		$drupalnaam				= $result_cont['values'][0]['job_title'];
 		if ($extdebug == 1) { watchdog('php', '<pre>contact_id:'. print_r($contact_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>first_name:' . print_r($first_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>displayname:'. print_r($displayname, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>drupalnaam:'. print_r($drupalnaam, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($result_part['count'] == 1) {
   			$part_eventid 			= $result_part['values'][0]['event_id'];
   			$part_kamptypeid		= $result_part['values'][0]['custom_961'];
			$part_id 				= $result_part['values'][0]['id'];
	   		$part_role_id			= $result_part['values'][0]['participant_role_id'];
	   		$part_status_id			= $result_part['values'][0]['participant_status_id'];
	   		#$part_ditjaar1stdeel 	= $result_part['values'][0]['custom_592'][0]; // M61: check it the [0] should be added
	   		#$part_ditjaar1stleid 	= $result_part['values'][0]['custom_649'][0];
	   		$part_ditjaar1stdeel 	= $result_part['values'][0]['custom_592'];
	   		$part_ditjaar1stleid 	= $result_part['values'][0]['custom_649'];
			$part_gegevensgechecked	= $result_part['values'][0]['custom_1038'];	// PART datum gegevens gechecked
			if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:'. print_r($part_eventid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_kamptypeid:'. print_r($part_kamptypeid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_id:'. print_r($part_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_role_id:'. print_r($part_role_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_status_id:'. print_r($part_status_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_gegevensgechecked:'. print_r($part_gegevensgechecked, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if(isset($result_part['values'][0]['custom_592'][0]))	{ $part_ditjaar1stdeel 	= $result_part['values'][0]['custom_592'][0]; } else { $part_ditjaar1stdeel = ""; }
			#if(isset($result_part['values'][0]['custom_649'][0])) 	{ $part_ditjaar1stleid 	= $result_part['values'][0]['custom_649'][0]; } else { $part_ditjaar1stleid = ""; }
			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stdeel:'. print_r($part_ditjaar1stdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stleid:'. print_r($part_ditjaar1stleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}

		#if (in_array($part_eventid, $kampidsdeel)) {
		if (in_array($part_role_id, array("7", "8"))) { // ROLE_ID = DEELNEMER of DEELNEMER TOPKAMP
			$part_functie 			= 'deelnemer';
			$part_groepklas 		= $result_part['values'][0]['custom_593'];
			$part_groepsvoorkeur 	= $result_part['values'][0]['custom_892'];
			if ($extdebug == 1) { watchdog('php', '<pre>part_groepklas:'. print_r($part_groepklas, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_groepsvoorkeur:'. print_r($part_groepsvoorkeur, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}

		#if (in_array($part_eventid, $kampidsleid)) { // INDIEN DIT JAAR MEE ALS LEIDING
		if ($part_role_id == 6) { // ROLE_ID = LEIDING
			$part_welkkampleid		= $result_part['values'][0]['custom_567'];	// WELK KAMP (PART_LEID)
   			$part_functie 			= $result_part['values'][0]['custom_568'];
	   		$vogrecent 				= $result_part['values'][0]['custom_56'];	// TAB DITJAAR
   			$vogkenmerk 			= $result_part['values'][0]['custom_68'];	// TAB DITJAAR
   			$part_vogverzocht 		= $result_part['values'][0]['custom_599'];
			$part_vogingediend		= $result_part['values'][0]['custom_600'];
			$part_vogontvangst		= $result_part['values'][0]['custom_601'];
   			$part_vogdatum			= $result_part['values'][0]['custom_603'];
   			$part_vogkenmerk 		= $result_part['values'][0]['custom_602'];

   			$refrecent 				= $result_part['values'][0]['custom_1004'];	// TAB DITJAAR datum van de laatste referentie
   			$refkenmerk 			= $result_part['values'][0]['custom_1003'];	// TAB DITJAAR naam van de laatste referentie
   			$part_refverzocht 		= $result_part['values'][0]['custom_703'];
			$part_refingevuld 		= $result_part['values'][0]['custom_707'];	// PART datum feedback van referentie
			$part_refnaam			= $result_part['values'][0]['custom_1021'];	// PART naam van de referentie
   			$part_refkenmerk		= $result_part['values'][0]['custom_706']; 	// PART aanbeveling JA of NEE
 			
			if ($extdebug == 1) { watchdog('php', '<pre>part_welkkampleid:'. print_r($part_welkkampleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_functie:'. print_r($part_functie, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_vogverzocht:'. print_r($part_vogverzocht, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_vogingediend:'. print_r($part_vogingediend, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_vogontvangst:'. print_r($part_vogontvangst, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_vogdatum:'. print_r($part_vogdatum, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_referentie_ingevuld:'. print_r($part_refingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_referentie_naam:'. print_r($part_refnaam, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}

		$arraydeel	 			= $result_cont['values'][0]['custom_376'];	// welke jaren deel
		if (is_array($arraydeel)) { $arraydeel = array(); } 
		$arrayleid	 			= $result_cont['values'][0]['custom_73'];	// welke jaren leid
		if (is_array($arrayleid)) { $arrayleid = array(); } 
		$hoevaakleid			= $result_cont['values'][0]['custom_74'];	// hoe vaak leid

		#if ($extdebug == 1) { watchdog('php', '<pre>arraydeel 0:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>arrayleid 0:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# CORRIGEER DATUM DRIJVVEREN INGEVULD VANUIT DATUM BELANGSTELLING OF DRIJFVEREN GECHECKED (zou niet nodig moeten zijn)
		#####################################################################################################

		$datum_belangstelling 	= $result_cont['values'][0]['custom_647'];
		$datum_drijf_ingevuld	= $result_cont['values'][0]['custom_474'];
		$datum_drijf_gechecked	= $result_cont['values'][0]['custom_663'];

   		if ($extdebug == 1) { watchdog('php', '<pre>datum_belangstelling:'. print_r($datum_belangstelling, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_gechecked:'. print_r($datum_drijf_gechecked, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>hoevaakleid:'. print_r($hoevaakleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($datum_belangstelling AND empty($datum_drijf_ingevuld)) {
			$datum_drijf_ingevuld = $datum_belangstelling;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld_van_belangstelling:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}
		if ($datum_drijf_gechecked AND empty($datum_drijf_ingevuld)) {
			$datum_drijf_ingevuld = $datum_drijf_gechecked;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld_van_drijf_gechecked:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}
		if ($datum_drijf_ingevuld AND empty($datum_belangstelling) AND $hoevaakleid == 0) {		//	VOOR ALS DATUM DRIJF WEL IS INGEVULD VOOR NIEUWE LEIDING MAAR DATUM BEL NIET
			$datum_belangstelling = $datum_drijf_ingevuld;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_belangstelling_van_drijf_ingevuld:'. print_r($datum_belangstelling, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}

   		#####################################################################################################
		# LET OP !!!! $contact_id hoort niet leeg te zijn
		#####################################################################################################

		if (empty($contact_id)) {
			if ($extdebug == 1) { watchdog('php', '<pre>ERROR: CONTACT INFO LEEG > RETURN</pre>', NULL, WATCHDOG_DEBUG); }
			return; //    if not, get out of here
		}

		#####################################################################################################
		# 1.4 CHECK OF DEZE PERSOON DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.4. CHECK OF '.$displayname.' DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if (in_array($part_eventid, $kampidsdeel) AND in_array($part_status_id, $partstatusyes)) {	// INDIEN EVENTID = EVENT VOOR DEELNEMER + PART_STATUS = positief  
			$ditjaardeelyes = 1;
			$ditjaardeeltxt = 'WEL!';
		} elseif (in_array($part_eventid, $kampidsdeel) AND in_array($part_status_id, $partstatusmss)) {
			$ditjaardeelmss = 1;
			$ditjaardeeltxt = 'MSS.';
		} elseif (in_array($part_eventid, $kampidsdeel) AND $part_status_id == 4) {					// INDIEN EVENTID = EVENT DEEL 	+ PART_STATUS = cancelled
			$ditjaardeelnot = 1;
			$ditjaardeeltxt = 'ANNU';
		} else {
			$ditjaardeelnot = 1;
			$ditjaardeeltxt = 'NIET';
		}
		#####################################################################################################
		if (in_array($part_eventid, $kampidsleid) AND in_array($part_status_id, $partstatusyes)) {	// INDIEN EVENTID = EVENT LEID 	+ PART_STATUS = positief
			$ditjaarleidyes = 1;
			$ditjaarleidtxt = 'WEL!';
		} elseif (strtotime($datum_belangstelling) >= strtotime($fiscalyear_start)) {			
			$ditjaarleidmss = 1;
			$ditjaarleidtxt = 'MSS.';
		} elseif (in_array($part_eventid, $kampidsleid) AND $part_status_id == 4) {	// INDIEN EVENTID = EVENT LEID 	+ PART_STATUS = cancelled	
			$ditjaarleidmss = 1;
			$ditjaarleidtxt = 'ANNU';
		} else {
			$ditjaarleidnot = 1;
			$ditjaarleidtxt = 'NIET';
		}

		if ($extdebug == 1) { watchdog('php', '<pre>DEEL EVENT_id ('.$part_eventid.') DITJAAR *'.$ditjaardeeltxt.'* MEE ALS deelnemer [status:'.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>LEID EVENT_id ('.$part_eventid.') DITJAAR *'.$ditjaarleidtxt.'* MEE ALS '.$part_functie.' [status:'.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }

		#if ($extdebug == 1) { watchdog('php', '<pre>kampidsdeel:' . print_r($kampidsdeel, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>kampidsleid:' . print_r($kampidsleid, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>partstatusmss:' . print_r($partstatusmss, true) . '</pre>', null, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>partstatusyes:' . print_r($partstatusyes, true) . '</pre>', null, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelyes:' . print_r($ditjaardeelyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelmss:' . print_r($ditjaardeelmss, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelnot:' . print_r($ditjaardeelnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidyes:' . print_r($ditjaarleidyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidmss:' . print_r($ditjaarleidmss, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidnot:' . print_r($ditjaarleidnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.6 RETRIEVE DRUPAL UID, LOGINNAME & EMAIL
		#####################################################################################################
   		if (in_array($groupID, array("149", "139", "190")) AND empty($drupalnaam)) {	// PART DEEL + PART LEID + PART LEID VOG + PART LEID REF
   		#if ($ditjaarleidyes == 1 OR $ditjaarleidmss == 1) {	// PART DEEL + PART LEID + PART REF EN INDIEN DIT JAAR MEE ALS LEIDING
	   		if ($extdebug == 1) { watchdog('php', '<pre>### 1.6 RETRIEVE DRUPAL UID, LOGINNAME & EMAIL [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			$params_drupaluser = [
      			'return' 		=> "name",
      			'contact_id' 	=> $contact_id,
    		];
			try{
				#if ($extdebug == 1) { watchdog('php', '<pre>params_drupaluser:' . print_r($params_drupaluser, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			$result = civicrm_api3('User', 'getvalue', $params_drupaluser);
    			#if ($extdebug == 1) { watchdog('php', '<pre>params_drupaluser_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			}
  			catch (CiviCRM_API3_Exception $e) {
    			// Handle error here.
    			$errorMessage	= $e->getMessage();
    			$errorCode 		= $e->getErrorCode();
    			$errorData 		= $e->getExtraParams();
   				if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			if ($extdebug == 1) { watchdog('php', '<pre>ERROR: GEEN DRUPAL ACCOUNT GEVONDEN:' . print_r($errorData, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			}
    		$drupal_name = $result;
  			if ($extdebug == 1) { watchdog('php', '<pre>drupal_name:' . print_r($drupal_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  		}

		#####################################################################################################
		# 1.7 RETRIEVE THE EVENT TYPE ID OF THE EVENT (M61: waarom staat dit er eigenlijk in?) > IGV LEIDING DAN ZAL EVENT_TYPE_ID 1 ZIJN & EVENT_ID DAT VAN LEIDING EVENT
   		if ($extdebug == 1) { watchdog('php', '<pre>### 1.7 RETRIEVE THE EVENT TYPE ID OF THE EVENT [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if (in_array($part_eventtypeid, $eventypesdeel) OR in_array($part_eventtypeid, $eventypesleid)) {
			#$part_eventtypeid 	= NULL;
    		if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid (overwrite):' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	} else {
	   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid (oldtypeid):' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid (typesdeel):' . print_r($eventypesdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid (typesleid):' . print_r($eventypesleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			$result = civicrm_api3('Event', 'get', array(
    			'sequential' => 1,
      			'return' => array("event_type_id"),
				'event_id' => $part_eventid, 			// eventid of specific kamp
    		));
    		$part_eventtypeid 	= $result['values'][0]['event_type_id'];
    		if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:' . print_r($part_eventid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	   		if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid:' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	}
    	###################################################################################################
	}

	if (in_array($groupID, array("139", "190", "140", "165", "213"))) { 	// PART DEEL + PART LEID + PART VOG + PART REF
		$entity_id = $contact_id;
	}
	if (in_array($groupID, array("103", "149"))) {							// TAB CURICULUM + TAB TALENT
		$entity_id = $entityID;
	}
	if (in_array($groupID, array("149", "139", "190", "140", "165", "213"))) {

		#####################################################################################################
		# 1.8 GET EVENT INFO TO RETREIVE HOOFDLEIDING (AND OTHER EVENT STUFF)
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.8. GET EVENT INFO TO RETREIVE HOOFDLEIDING [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
    	if (($extdjpart == 1 OR $extdjcont == 1 OR $extvog == 1) AND ($ditjaardeelyes == 1 OR $ditjaardeelmss == 1 OR $ditjaarleidyes == 1)) {
			$params_event = [
				'sequential'	=> 1,
   				'return'		=> array("id","start_date", "end_date", "custom_440","custom_681","custom_682","custom_917", "custom_1042","custom_1162","custom_1161","event_type_id"),
   				'is_template'	=> 0,
				'id'			=> array('IN' => $kampidsdeel),			// gebruik de gevonden event_id's van de kampen van dit jaar
				'event_type_id' => array('IN' => $eventypesdeel),
   			];
   			if (in_array($part_eventtypeid, $eventypesdeel)) {			// EVENTTYPE = DEEL (afkorting kamp staat in initial_amount_label)
   				$params_event['id'] 			= $part_eventid;		// eventid of specific kamp		
   			}
  			if (in_array($part_eventtypeid, $eventypesleid)) {			// EVENTTYPE = LEID (zoek kamp waar leiding zich voor opgaf)
  				$params_event['custom_917'] 	= $part_welkkampleid;	// eventid of specific kamp		
  			}
			#if ($extdebug == 1) { watchdog('php', '<pre>params_event:' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result = civicrm_api3('Event', 'get', $params_event);
			if ($extdebug == 1) { watchdog('php', '<pre>params_event EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>params_event_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			$event_kamp_kort		= $result['values'][0]['custom_917'];
   			if ($part_welkkampleid) {
   				$event_kamp_kort	= $part_welkkampleid;
   			}
    		$event_id 				= $result['values'][0]['id'];
    		$event_type_id 			= $result['values'][0]['event_type_id'];
    		$event_type_name		= $result['values'][0]['custom_440'];

			$event_startdate 		= $result['values'][0]['start_date'];
			$event_enddate 			= $result['values'][0]['end_date'];

    		$event_locatie			= $result['values'][0]['custom_1162'];
    		$event_plaats			= $result['values'][0]['custom_1161'];

			$options = civicrm_api3('Contact','getoptions', array(
				'field' => 'custom_1162',
			));
			//if ($extdebug == 1) { watchdog('php', '<pre>options_result:' . print_r($options, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); 
			$event_locatie_lang = $options['values'][$event_locatie];

			$options = civicrm_api3('Contact','getoptions', array(
				'field' => 'custom_1161',
			));
			//if ($extdebug == 1) { watchdog('php', '<pre>options_result:' . print_r($options, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); 
			$event_plaats_lang = $options['values'][$event_plaats];

			$event_hoofdleiding1_id	= $result['values'][0]['custom_681_id'];
			$event_hoofdleiding2_id = $result['values'][0]['custom_682_id'];
			$event_hoofdleiding3_id = $result['values'][0]['custom_1042_id'];
			$event_hoofdleiding1	= $result['values'][0]['custom_681'];
			$event_hoofdleiding2 	= $result['values'][0]['custom_682'];
			$event_hoofdleiding3 	= $result['values'][0]['custom_1042'];

   			if ($extdebug == 1) { watchdog('php', '<pre>event_id:' . print_r($event_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>event_type_id:' . print_r($event_type_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>event_type_name:' . print_r($event_type_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>event_kamp_kort:' . print_r($event_kamp_kort, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			if ($extdebug == 1) { watchdog('php', '<pre>event_locatie:' . print_r($event_locatie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>event_plaats:' . print_r($event_plaats, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// HOOFDLEIDING 1
 	 		if (isset($event_hoofdleiding1_id))	{
				#if ($extdebug == 1) { watchdog('php', '<pre>$event_hoofdleiding1_id:' . print_r($event_hoofdleiding1_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			$result = civicrm_api3('Contact', 'get', array(
      				'sequential' => 1,
      				'return' => array("display_name", "first_name"),
      				'id' => $event_hoofdleiding1_id,
    			));
    			$event_hoofdleiding1_displname = $result['values'][0]['display_name'];
    			$event_hoofdleiding1_firstname = $result['values'][0]['first_name'];
    		} else {
    			$event_hoofdleiding1_displname = "";
    			$event_hoofdleiding1_firstname = "";
    		}
			#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding1_sortname:' . print_r($event_hoofdleiding1, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }    		
    		if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding1_displname:' . print_r($event_hoofdleiding1_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			// HOOFDLEIDING 2
 	 		if (isset($event_hoofdleiding2_id))	{
				#if ($extdebug == 1) { watchdog('php', '<pre>$event_hoofdleiding2_id:' . print_r($event_hoofdleiding2_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			$result = civicrm_api3('Contact', 'get', array(
      				'sequential' => 1,
      				'return' => array("display_name", "first_name"),
      				'id' => $event_hoofdleiding2_id,
    			));
    			$event_hoofdleiding2_displname = $result['values'][0]['display_name'];
    			$event_hoofdleiding2_firstname = $result['values'][0]['first_name'];
    		} else {
    			$event_hoofdleiding2_displname = "";
    			$event_hoofdleiding2_firstname = "";
    		}
			#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding2_sortname:' . print_r($event_hoofdleiding2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }    		
    		if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding2_displname:' . print_r($event_hoofdleiding2_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			// HOOFDLEIDING 3
 	 		if (isset($event_hoofdleiding3_id))	{
				#if ($extdebug == 1) { watchdog('php', '<pre>$event_hoofdleiding3_id:' . print_r($event_hoofdleiding3_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			$result = civicrm_api3('Contact', 'get', array(
      				'sequential' => 1,
      				'return' => array("display_name", "first_name"),
      				'id' => $event_hoofdleiding3_id,
    			));
    			$event_hoofdleiding3_displname = $result['values'][0]['display_name'];
    			$event_hoofdleiding3_firstname = $result['values'][0]['first_name'];
    		} else {
    			$event_hoofdleiding3_displname = "";
    			$event_hoofdleiding3_firstname = "";
    		}
    		#if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding3_sortname:' . print_r($event_hoofdleiding3, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($extdebug == 1) { watchdog('php', '<pre>result_hoofdleiding3_displname:' . print_r($event_hoofdleiding3_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			$options = civicrm_api3('Contact','getoptions', array(
				'field' => 'custom_567', // M61: beware: hardcoded option group id
			));
			#if ($extdebug == 1) { watchdog('php', '<pre>options_result:' . print_r($options, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); 
			$welkkampkort = $event_kamp_kort;
			$welkkamplang = $options['values'][$event_kamp_kort];
			if ($welkkampkort == 'TOP') { $welkkamplang = "$welkkamplang"." "."$ditkampjaar"; }

			# BEPAAL WELKE WEEK ADHV DE AFKORTING VAN HET KAMP
			$welkeweeknr  = substr($event_kamp_kort, -1);
			if (!in_array($welkeweeknr, array("1", "2"))) {	$welkeweeknr 	= "";}
#			if ($welkeweeknr == 1) 							{$welkeweek = 'week1';}
#			if ($welkeweeknr == 2) 							{$welkeweek = 'week2';}
#			if ($welkeweeknr != 1 AND $welkeweeknr != 2)	{$welkeweek = ""; $welkeweeknr 	= "";}

			if ($extdebug == 1) { watchdog('php', '<pre>welkkampkort:'. print_r($welkkampkort, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>welkkamplang:'. print_r($welkkamplang, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>welkeweek:'. print_r($welkeweek, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>welkeweeknr:'. print_r($welkeweeknr, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    	}

		#####################################################################################################
		# 1.9 SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.9 SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if ($exttag == 1) {    	
			// #1 UPDATE the Deelnemer CV according to the tags and only if Deelnemer CV is empty or null
			$sql04          = "SELECT TG.description FROM civicrm_entity_tag ET INNER JOIN civicrm_tag TG ON ET.tag_id = TG.id WHERE ET.entity_id = '$entity_id' AND TG.name LIKE 'D%' AND TG.name != 'DON' ORDER BY TG.description ASC";
			$dao04          = CRM_Core_DAO::executeQuery($sql04);
			$welkejarendeel = array();
			while ($dao04->fetch()) {
				$welkejarendeel[] = $dao04->description;
			}
			$tagnr_deel = count(array_filter($welkejarendeel));
			$tagcv_deel = implode('', $welkejarendeel);
			if ($extdebug == 1) { watchdog('php', '<pre>tags:welkejarendeel:'. print_r($welkejarendeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

    		$result = civicrm_api3('Tag', 'get', [
      			'sequential'	=> 1,
      			'return'		=> ["name"],
      			'parent_id'		=> 37,
      			'options'		=> ['limit' => 99],
    		]);
    		#$welketagsdeel = array();
    		#while ($result->fetch()) {
			#	$welketagsdeel[] = $result->name;
			#}

    		#if ($extdebug == 1) { watchdog('php', '<pre>tags:parent:deel:'. print_r($result, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		#if ($extdebug == 1) { watchdog('php', '<pre>tags:welketagsdeel:'. print_r($welketagsdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		    $result = civicrm_api3('Contact', 'get', [
      			'sequential' 	=> 1,
      			'return' 		=> ["tag"],
      			'id' 			=> $entity_id,
    		]);
    		if ($extdebug == 1) { watchdog('php', '<pre>tags:allcurrent'. print_r($result, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			// #2 UPDATE the Leiding CV according to the tags and only if Leiding CV is empty or null
			$sql06          = "SELECT TG.description FROM civicrm_entity_tag ET INNER JOIN civicrm_tag TG ON ET.tag_id = TG.id WHERE ET.entity_id = '$entity_id' AND TG.name LIKE 'L%' ORDER BY TG.description ASC";
			$dao06          = CRM_Core_DAO::executeQuery($sql06);
			$welkejarenleid = array();
			while ($dao06->fetch()) {
				$welkejarenleid[] = $dao06->description;
			}
			$tagnr_leid = count(array_filter($welkejarenleid));
			$tagcv_leid = implode('', $welkejarenleid);
			if ($extdebug == 1) { watchdog('php', '<pre>tags:welkejarenleid:'. print_r($welkejarenleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			if ($extdebug == 1) { watchdog('php', '<pre>tagnr_deel:'. print_r($tagnr_deel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel:'. print_r($tagcv_deel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagnr_leid:'. print_r($tagnr_leid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid:'. print_r($tagcv_leid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#####################################################################################################
		# 1.10 BEPAAL NIEUWE NETTO CV DEEL & LEID (& EERSTE + LAATSTE)
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.10 BEPAAL NIEUWE NETTO CV DEEL & LEID (& EERSTE + LAATSTE) [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if ($extcv == 1) { 
			$eerstedeel  = "";
			$laatstedeel = "";
			$eersteleid  = "";
			$laatsteleid = "";
			$eerstexdeel = NULL;
			$eerstexleid = NULL;

			#$arraydeel	 = explode("", $arraydeel);
			#$arrayleid	 = explode("", $arrayleid);

			// DEELNEMER: VOEG HUIDIG JAAR TOE OF VERWIJDER HUIDIG JAAR UIT CV OBV DEELNAMESTATUS
			if ($ditjaardeelyes == 1) {
				#$part_functie = 'deelnemer';
				if ( (array) $arraydeel === $arraydeel) {
					if (!in_array($ditkampjaar, $arraydeel)) {	// VOEG HUIDIG KAMPJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
						if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_org:' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						array_push($arraydeel, $ditkampjaar);	// VOEG HUIDIG KAMPJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
						if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_new (+ huidig jaar):' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					} else {
						#if ($extdebug == 1) { watchdog('php', '<pre>huidigjaar al in arraydeel:' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
   				} else {
  					$arraydeel = array($ditkampjaar);			// ZOU EIGENLIJK NIET KAMPJAAR MOETEN ZIJN (is einde huidig fiscal year) MAAR JAAR VAN EVENT DAT GEEDIT WORDT
					if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_1st (= huidig jaar):' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				}
   			} else {
 				if ($extdebug == 1) { watchdog('php', '<pre>arraydeel is not an array</pre>', NULL, WATCHDOG_DEBUG); }
				if ( (array) $arraydeel === $arraydeel) {
					if (in_array($ditkampjaar, $arraydeel)) {	// VERWIJDER HUIDIG JAAR UIT ARRAY INDIEN HET ER INZAT
						if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_org:' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						$arraydeel = array_diff($arraydeel, array($ditkampjaar));
						if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_new (- huidig jaar):' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
   			}

			// LEIDING: VOEG HUIDIG JAAR TOE OF VERWIJDER HUIDIG JAAR UIT CV OBV DEELNAMESTATUS
			if ($ditjaarleidyes == 1) {

   				// M61: dit staat erin omdat de waarde '1' op een of andere manier af en toe voorkwam (maar hier een ongeldige waarde is) 
   				if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_org:' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$del_val = 1;
   				if (is_array($arraydeel)) {
					if (($key = array_search($del_val, $arrayleid)) !== false) {
    					unset($arrayleid[$key]);
    					if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_new (- rogue 1):' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				#$part_functie = 'groepsleiding'; // M61: comment out omdat het andere leidingrollen overschreef		
				if ( (array) $arrayleid === $arrayleid) {
					if (!in_array($ditkampjaar, $arrayleid)) {	// VOEG HUIDIG KAMPJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
						if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_org:' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						array_push($arrayleid, $ditkampjaar);	// VOEG HUIDIG KAMPJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
						if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_new (+ huidig jaar):' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					} else {
						#if ($extdebug == 1) { watchdog('php', '<pre>huidigjaar al in arraydeel:' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
   				} else {
  					$arrayleid = array($ditkampjaar);			// ZOU EIGENLIJK NIET KAMPJAAR MOETEN ZIJN (is einde huidig fiscal year) MAAR JAAR VAN EVENT DAT GEEDIT WORDT
					if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_1st (= huidig jaar):' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				}
   			} else {
 				if ($extdebug == 1) { watchdog('php', '<pre>arrayleid is not an array</pre>', NULL, WATCHDOG_DEBUG); }
				if ( (array) $arrayleid === $arrayleid) {
					if (in_array($ditkampjaar, $arrayleid)) {	// VERWIJDER HUIDIG JAAR UIT ARRAY INDIEN HET ER INZAT
						if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_org:' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						$arrayleid = array_diff($arrayleid, array($ditkampjaar));
						if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_new (- huidig jaar):' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
   			}

   			// BEPAAL EERSTE EN LAATSTE JAAR DEEL
			if (!empty($arraydeel)) {
				$hoevaakdeel = count(array_filter($arraydeel));
				if ($extdebug == 1) { watchdog('php', '<pre>hoevaakdeel:'. print_r($hoevaakdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				$welkedeel   = implode('', array_filter($arraydeel));
				if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel 0:'. print_r($welkedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($hoevaakdeel == 1) {
					$welkedeel 	 = "".$welkedeel."";
					if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel 1:'. print_r($welkedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				#}
				if ($hoevaakdeel > 0) {
					$eerstedeel  = min(array_filter($arraydeel));
					$laatstedeel = max(array_filter($arraydeel));
				}
			} else {
				$hoevaakdeel	= 0;
				unset($welkedeel);
				//$welkedeel 		= array(); 			// NOT SURE IF "" IS VALID FOR THIS FIELD
				//$welkedeel 		= ""; 	// NOT SURE IF "" IS VALID FOR THIS FIELD
				$welkedeel 		= NULL; 				// NOT SURE IF "" IS VALID FOR THIS FIELD
				$eerstedeel 	= "";
				$laatstedeel 	= "";
			}

			// BEPAAL EERSTE EN LAATSTE JAAR LEID
			if (!empty($arrayleid)) {
				$hoevaakleid = count(array_filter($arrayleid));
				if ($extdebug == 1) { watchdog('php', '<pre>hoevaakleid:'. print_r($hoevaakleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				$welkeleid   = implode('', array_filter($arrayleid));
				if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid 0:'. print_r($welkeleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($hoevaakleid == 1) {
					$welkeleid 	 = "".$welkeleid."";
					if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid 1:'. print_r($welkeleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				#}
				if ($hoevaakleid > 0) {
					$eersteleid  = min(array_filter($arrayleid));
					$laatsteleid = max(array_filter($arrayleid));
				}
			} else {
				$hoevaakleid	= 0;
				unset($welkeleid);
				$welkeleid 		= NULL;	// NOT SURE IF "" IS VALID FOR THIS FIELD
				$eersteleid 	= "";
				$laatsteleid 	= "";
			}

			// BEPAAL EERSTE EN LAATSTE JAAR TOTAAL
			$totaalmee   = $hoevaakdeel + $hoevaakleid;
			$eerstekeer  = $hoevaakdeel > 0 ? $eerstedeel  : $eersteleid;
			$laatstekeer = $hoevaakleid > 0 ? $laatsteleid : $laatstedeel;

			if ($exttag == 1) {
				$tagverschildeel	= $tagnr_deel - $hoevaakdeel;
				$tagverschilleid	= $tagnr_leid - $hoevaakleid;
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
			if ($extdebug == 1) { watchdog('php', '<pre>eerstekeer:'. print_r($eerstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>laatstekeer:'. print_r($laatstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#####################################################################################################
		# 1.11 BEPAAL OF DIT HET EERSTE JAAR IS DAT DEZE PERSOON ALS DEELNEMER OF LEIDING MEEGAAT
   		if ($extdebug == 1) { watchdog('php', '<pre>### 1.11 BEPAAL OF DIT HET EERSTE JAAR IS DAT DEZE PERSOON ALS DEELNEMER OF LEIDING MEEGAAT [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if ($extcv == 1) {
			if ($extdebug == 1) { watchdog('php', '<pre>tagnr_deel:'. print_r($tagnr_deel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagnr_leid:'. print_r($tagnr_leid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

    		// poging om totaal aantal keren mee als deelnemer te berekenen op basis van event registraties
			$params_countpart_deel = [
				'status_id'		=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Partially paid", "Pending refund"),
      			'role_id'		=> "Deelnemer",
      			'contact_id'	=> $contact_id,
      			#'fee_amount'	=> ['>' => 1],
      			#'custom_992' 	=> ["kinderkamp", "tienerkamp", "brugkamp", "jeugdkamp", "topkamp"],
      			#'event_type_id' => array('IN' => $eventypesdeel), (cannot be combined with participant, this is an event field)
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_countpart_deel:' . print_r($params_countpart_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$kereneventdeel = civicrm_api3('Participant', 'getcount', $params_countpart_deel);
   			if ($extdebug == 1) { watchdog('php', '<pre>kereneventdeel:' . print_r($kereneventdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    		// poging om totaal aantal keren mee als deelnemer van het Topkamp te berekenen op basis van event registraties
			$params_countpart_top = [
				'status_id'  	=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Partially paid", "Pending refund"),
      			'role_id' 	 	=> "Deelnemer Topkamp",
      			'contact_id' 	=> $contact_id,
      			#'fee_amount' => ['>' => 1],
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_countpart_deel:' . print_r($params_countpart_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$kereneventtop = civicrm_api3('Participant', 'getcount', $params_countpart_top);
   			if ($extdebug == 1) { watchdog('php', '<pre>kereneventtop:' . print_r($kereneventtop, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    		// poging om totaal aantal keren mee als leiding te berekenen op basis van event registraties
			$params_countpart_leid = [
				'status_id'  	=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Partially paid", "Pending refund"),
      			'role_id' 	 	=> "Leiding",
      			'contact_id' 	=> $contact_id,
      			#'fee_amount' => ['>' => 1],
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_countpart_leid:' . print_r($params_countpart_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$kereneventleid = civicrm_api3('Participant', 'getcount', $params_countpart_leid);
   			if ($extdebug == 1) { watchdog('php', '<pre>kereneventleid:' . print_r($kereneventleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			#if ($part_ditjaar1stdeel == 'eerstex' AND $hoevaakdeel == 1 AND !($tagnr_deel >= 2)) { $eerstexdeel = 'eerstex'; } else { $eerstexdeel = NULL; }
   			#if ($part_ditjaar1stleid == 'eerstex' AND $hoevaakleid == 1 AND !($tagnr_leid >= 2)) { $eerstexleid = 'eerstex'; } else { $eerstexleid = NULL; }

			#$part_ditjaar1stdeel = implode('', $eerstexdeel);
			#$part_ditjaar1stleid = implode('', $eerstexleid);

			if ($extcv == 1) {
				$eventverschildeel	= $kereneventdeel - $hoevaakdeel;
				$eventverschilleid	= $kereneventleid - $hoevaakleid;
			}

			$part_ditjaar1stdeel = CRM_Core_DAO::VALUE_SEPARATOR . $eerstexdeel . CRM_Core_DAO::VALUE_SEPARATOR;
			$part_ditjaar1stleid = CRM_Core_DAO::VALUE_SEPARATOR . $eerstexleid . CRM_Core_DAO::VALUE_SEPARATOR;

   			if ($extdebug == 1) { watchdog('php', '<pre>eerstexdeel_0:' . print_r($eerstexdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>eerstexleid_0:' . print_r($eerstexleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stdeel_0:' . print_r($part_ditjaar1stdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stleid_0:' . print_r($part_ditjaar1stleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			if (($hoevaakdeel == 1 AND !($tagnr_deel >= 2) AND !($kereneventdeel >1)) OR ($kereneventdeel == 1 AND $ditjaardeelyes == 1)) { $eerstexdeel = 'eerstex'; } else { $eerstexdeel = ""; }
   			if (($hoevaakleid == 1 AND !($tagnr_leid >= 2) AND !($kereneventdeel >1)) OR ($kereneventleid == 1 AND $ditjaarleidyes == 1)) { $eerstexleid = 'eerstex'; } else { $eerstexleid = ""; }
   			if (($hoevaakdeel == 1 AND !($tagnr_deel >= 2) AND !($kereneventdeel >1)) OR ($kereneventdeel == 1 AND $ditjaardeelyes == 1)) { $part_ditjaar1stdeel = 'eerstex'; } else { $part_ditjaar1stdeel = NULL; }
   			if (($hoevaakleid == 1 AND !($tagnr_leid >= 2) AND !($kereneventdeel >1)) OR ($kereneventleid == 1 AND $ditjaarleidyes == 1)) { $part_ditjaar1stleid = 'eerstex'; } else { $part_ditjaar1stleid = NULL; }

   			// de 2 regels hieronder zouden eigenlijk verwerkt moeten worden in de conditionals hier boven.
   			if ($ditjaardeelnot == 1) { $part_ditjaar1stdeel = NULL; $eerstexdeel = NULL;}
   			if ($ditjaarleidnot == 1) { $part_ditjaar1stleid = NULL; $eerstexleid = NULL;}
   			#if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelnot:' . print_r($ditjaardeelnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			#if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidnot:' . print_r($ditjaarleidnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			if ($extdebug == 1) { watchdog('php', '<pre>eerstexdeel_1:' . print_r($eerstexdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>eerstexleid_1:' . print_r($eerstexleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stdeel_1:' . print_r($part_ditjaar1stdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stleid_1:' . print_r($part_ditjaar1stleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		}
		#####################################################################################################
		# 1.12 UPDATE PARAMS_CONTACT (CV & DITJAAR) MET EVENT INFO - EN ANDERS LEEGMAKEN! (HIER MOET NOG EEN ELSIF DUS)
		#####################################################################################################
		if ($extdjcont == 1) {
			if ($extdebug == 1) { watchdog('php', '<pre>### 1.12 UPDATE PARAMS_CONTACT MET EVENT INFO [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

	    	$params_contact = [ 	// MAAK DITJAAR velden leeg als dat van toepassing is
	    	  	#'debug'			=> 1,
	    	  	'contact_type' 	=> 'Individual',
	   			'id'		   	=> $contact_id,
  				'first_name'   	=> $first_name,
    		];

    		$params_contact['job_title']	= $drupal_name;
    		$params_contact['custom_647']	= $datum_belangstelling;
    		$params_contact['custom_474']	= $datum_drijf_ingevuld;
			$params_contact['custom_1010']	= "&euro; 0,00"; // mss niet gebruiken hier omdat er anders een update loop kan ontstaan

    		if ($ditjaardeelnot == 1 OR $ditjaarleidnot == 1) {
      			$params_contact['custom_993']	= "";
      			$params_contact['custom_994']	= "";
      			$params_contact['custom_995']	= "";
      		}
			if ($ditjaardeelyes != 1 OR $ditjaarleidyes != 1) {
      			$params_contact['custom_865']	= "";
      			$params_contact['custom_900']	= ""; 
      			$params_contact['custom_901']	= "";
      			$params_contact['custom_1048']	= "";
      			$params_contact['custom_938']	= "";
      			$params_contact['custom_939']	= "";
      			$params_contact['custom_1043']	= "";
      			$params_contact['custom_951']	= "";
      			$params_contact['custom_952']	= "";
      			$params_contact['custom_1044']	= "";
      			$params_contact['custom_996']	= "";
      			$params_contact['custom_997']	= "";
      			$params_contact['custom_1051']	= "";
      		}

    		if ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1 OR $ditjaarleidmss == 1) {
    			$params_contact['custom_993'] 	= $event_type_name;
    			$params_contact['custom_994'] 	= $event_type_id;
    			$params_contact['custom_995'] 	= $event_id;
      			$params_contact['custom_1030']	= $part_id;				// participant id (zou ook eitity ID kunnen zijn, maar iig niet contact_id)
      			$params_contact['custom_865'] 	= $part_functie;
    			$params_contact['custom_900'] 	= $welkkamplang;
    			$params_contact['custom_901'] 	= $welkkampkort;
    			$params_contact['custom_1048'] 	= $welkeweeknr;
    			$params_contact['custom_1157'] 	= $ditkampjaar;
	   			$params_contact['custom_1155'] 	= $event_startdate;
    			$params_contact['custom_1156'] 	= $event_enddate;
	   			$params_contact['custom_1163'] 	= $event_locatie_lang;
    			$params_contact['custom_1164'] 	= $event_plaats_lang;
			}

    		if ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1) {
    			$params_contact['custom_938'] 	= $event_hoofdleiding1_displname;
    			$params_contact['custom_939'] 	= $event_hoofdleiding2_displname;
    			$params_contact['custom_1043'] 	= $event_hoofdleiding3_displname;
    			$params_contact['custom_951'] 	= $event_hoofdleiding1_firstname;
    			$params_contact['custom_952'] 	= $event_hoofdleiding2_firstname;
    			$params_contact['custom_1044'] 	= $event_hoofdleiding3_firstname;
    			$params_contact['custom_996'] 	= $eerstexdeel;
    			$params_contact['custom_997'] 	= $eerstexleid;
			}
    		if ($ditjaardeelyes == 1 OR $ditjaardeelmss == 1) {
			    $params_contact['custom_1051']  = $part_groepklas;
  			    $params_contact['custom_1180']  = $part_groepsvoorkeur;
			}
		}
		#####################################################################################################
		# 1.13 UPDATE PARAMS_PARTICIPANT MET EVENT INFO
    	if ($extdjpart == 1 AND ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1)) { // M61: hier van maken dat het ook op voorgaande jaren werkt
		#####################################################################################################
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.13 UPDATE PARAMS_PARTICIPANT MET EVENT INFO [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
    		$params_participant = [
   				'id'           => $part_id,
				'event_id'	   => $part_eventid,
   				'contact_id'   => $contact_id,

      			'custom_992'   => $event_type_name,
      			'custom_961'   => $event_type_id,
      			'custom_962'   => $event_id,

   				'custom_969'   => $part_functie,
   				'custom_949'   => $welkkamplang,
      			'custom_950'   => $welkkampkort,
	    		'custom_1050'  => $welkeweeknr,
    		];

    		if ($event_hoofdleiding1_displname) {
    			$params_participant['custom_944']	= $event_hoofdleiding1_displname;
    			$params_participant['custom_945']	= $event_hoofdleiding2_displname;
    		}
    		if ($event_hoofdleiding1_displname) {
    			$params_participant['custom_1046']	= $event_hoofdleiding1_displname;
    			$params_participant['custom_953']	= $event_hoofdleiding2_displname;
    		}
    		if ($event_hoofdleiding1_displname) {
    			$params_participant['custom_954']	= $event_hoofdleiding1_displname;
    			$params_participant['custom_1047']	= $event_hoofdleiding2_displname;
    		}
    		if ($part_ditjaar1stdeel) {
    			$params_participant['custom_592']	= $part_ditjaar1stdeel;
    		}
    		if ($part_ditjaar1stleid) {
    			$params_participant['custom_649']	= $part_ditjaar1stleid;
    		}
		}
		#####################################################################################################
		# 1.14 UPDATE params_contact MET CV INFO
    	#if ($extcv == 1 AND ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1 OR $ditjaarleidmss == 1)) { // M61: hier van maken dat het ook op voorgaande jaren werkt
   		if ($extcv == 1) {
		#####################################################################################################
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.14 UPDATE params_contact MET STATISTIEKEN[groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

			$params_contact['custom_846']   = $eerstekeer;
			$params_contact['custom_847']   = $laatstekeer;
			$params_contact['custom_458']	= $totaalmee;
			$params_contact['custom_1001']  = $kereneventdeel;
			$params_contact['custom_1002']  = $kereneventleid;
			$params_contact['custom_1110']  = $eventverschildeel;
			$params_contact['custom_1112']	= $eventverschilleid;
			$params_contact['custom_1039']	= $part_gegevensgechecked;

    		if ($exttag == 1) {
   				$params_contact['custom_856']   = $tagcv_deel;
   				$params_contact['custom_848']   = $tagnr_deel;
  				$params_contact['custom_857']   = $tagcv_leid;
   				$params_contact['custom_849']   = $tagnr_leid;
      			$params_contact['custom_850']   = $tagverschildeel;
      			$params_contact['custom_851']	= $tagverschilleid;
    		}

    		if ( (isset($welkedeel) AND $hoevaakdeel > 0) OR (!isset($welkedeel) AND $hoevaakdeel == 0) )	{ 	// voeg welkedeel alleen toe als het niet leeg is
	    		$params_contact['custom_376']	= $welkedeel;
	    		$params_contact['custom_382']	= $hoevaakdeel;
	    		$params_contact['custom_842']	= $eerstedeel;
	    		$params_contact['custom_843']	= $laatstedeel;
	    		$params_contact['custom_1027']	= $kereneventtop;
				#if ($extdebug == 1) { watchdog('php', '<pre>array_add_welkedeel_376:' . print_r($welkedeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			}
    		if ( (isset($welkeleid) AND $hoevaakleid > 0) OR (!isset($welkeleid) AND $hoevaakleid == 0) )	{ 	// voeg welkeleid alleen toe als het niet leeg is
	    		$params_contact['custom_73']	= $welkeleid;
	    		$params_contact['custom_74']	= $hoevaakleid;
	    		$params_contact['custom_844']	= $eersteleid;
	    		$params_contact['custom_845']	= $laatsteleid;
				#if ($extdebug == 1) { watchdog('php', '<pre>array_add_welkeleid_073:' . print_r($welkeleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		}

			#$params_contact['custom_1121'] = "params_contact";

    		if ($extcv == 1) {
   				#if ($extdebug == 1) { watchdog('php', '<pre>params_contact:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#$result = civicrm_api3('Contact', 'create', $params_contact);
				#if ($extdebug == 1) { watchdog('php', '<pre>params_contact EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
   			}
	   	}

		#####################################################################################################
		# 1.15 RETRIEVE RELATED HOOFDLEIDING   	
		#####################################################################################################
	    if ($extrel == 1 AND ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1 OR $ditjaarleidmss == 1)) { // M61: hier van maken dat het ook op voorgaande jaren werkt
		#####################################################################################################
  			if (in_array($groupID, array("139", "190"))) {	// PART DEEL + PART LEID
	    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.15 RETRIEVE RELATED HOOFDLEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				$params_get_related_hoofdleiding = [
    				'sequential' 			=> 1,
      				'return' 				=> ["contact_id_a", "contact_id_b", "is_active", "start_date", "end_date", "id"],
      				'contact_id_a' 			=> $contact_id,
      				'relationship_type_id' 	=> 17,
     				#'event_start_date_relative' => "this.fiscal_year",
      				'start_date' 			=> ['>' => $fiscalyear_start],
    			];
    			try{
					#if ($extdebug == 1) { watchdog('php', '<pre>params_get_related_hoofdleiding:' . print_r($params_get_related_hoofdleiding, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				$result = civicrm_api3('Relationship', 'get', $params_get_related_hoofdleiding);
    				#if ($extdebug == 1) { watchdog('php', '<pre>params_get_related_hoofdleiding_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					$related_hoofdleiding_id	= $result['values'][0]['contact_id_b'];
					$related_hoofdleiding_relid	= $result['values'][0]['id'];
					if ($extdebug == 1) { watchdog('php', '<pre>$related_hoofdleiding_id:' . print_r($related_hoofdleiding_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>$related_hoofdleiding_relid:' . print_r($related_hoofdleiding_relid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  				catch (CiviCRM_API3_Exception $e) {
    				// Handle error here.
    				$errorMessage 	= $e->getMessage();
    				$errorCode 		= $e->getErrorCode();
    				$errorData 		= $e->getExtraParams();
   					if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				if ($extdebug == 1) { watchdog('php', '<pre>ERROR: GEEN RELATED HOOFDLEIDING GEVONDEN:' . print_r($errorData, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  			}
  		}
		#####################################################################################################
		# 1.16 CREATE RELATED HOOFDLEIDING
		#####################################################################################################
	    if ($extrel == 1 AND ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1 OR $ditjaarleidmss == 1)) { // M61: hier van maken dat het ook op voorgaande jaren werkt
		#####################################################################################################
			if ($welkkampkort == 'KK1')	{ $related_hoofdleiding_id = 14197;}
			if ($welkkampkort == 'KK2')	{ $related_hoofdleiding_id = 14198;}
			if ($welkkampkort == 'BK1')	{ $related_hoofdleiding_id = 14199;}
			if ($welkkampkort == 'BK2')	{ $related_hoofdleiding_id = 14200;}
			if ($welkkampkort == 'TK1')	{ $related_hoofdleiding_id = 14201;}
			if ($welkkampkort == 'TK2')	{ $related_hoofdleiding_id = 14202;}
			if ($welkkampkort == 'JK1')	{ $related_hoofdleiding_id = 14203;}
			if ($welkkampkort == 'JK2')	{ $related_hoofdleiding_id = 14204;}
			if ($welkkampkort == 'TOP')	{ $related_hoofdleiding_id = 14205;}

  			if (in_array($groupID, array("139", "190"))) {	// PART DEEL + PART LEID
	    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.15 CREATE RELATED HOOFDLEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				$params_create_related_hoofdleiding = [
      				'contact_id_a' 			=> $contact_id,
      				'contact_id_b' 			=> $related_hoofdleiding_id,
      				'relationship_type_id' 	=> 17,
      				'start_date' 			=> $fiscalyear_start,
      				'end_date' 				=> $fiscalyear_end,
      				'is_active'				=> 1,
    			];
    			try{
					if ($extdebug == 1) { watchdog('php', '<pre>params_create_related_hoofdleiding:' . print_r($params_create_related_hoofdleiding, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($related_hoofdleiding_id) {
//    					$result = civicrm_api3('Relationship', 'create', $params_create_related_hoofdleiding);
					}
    				#if ($extdebug == 1) { watchdog('php', '<pre>params_create_related_hoofdleiding_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#$related_hoofdleiding_id		= $result['values'][0]['contact_id_b'];
					#$related_hoofdleiding_relid	= $result['values'][0]['id'];
					if ($extdebug == 1) { watchdog('php', '<pre>$related_hoofdleiding_id:' . print_r($related_hoofdleiding_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>$related_hoofdleiding_relid:' . print_r($related_hoofdleiding_relid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  				catch (CiviCRM_API3_Exception $e) {
    				// Handle error here.
    				$errorMessage 	= $e->getMessage();
    				$errorCode 		= $e->getErrorCode();
    				$errorData 		= $e->getExtraParams();
   					if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				if ($extdebug == 1) { watchdog('php', '<pre>ERROR: GEEN RELATED HOOFDLEIDING AANGEMAAKT:' . print_r($errorData, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  			}
  		}
   		if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] [entityID: '.$entityID.'] [kampleider: '.$displayname.'] ***</pre>', null, WATCHDOG_DEBUG); }
		###############################################################################################################
		### 2. EXTENSION VOG/REF
		###############################################################################################################

   		if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {	// PART LEID + PART LEID REF + PART LEID REFERENTIE
   			if (empty($contact_id)) { // GA ALLEEN DOOR ALS CONTACT ID NIET LEEG IS (DEZE CHECK IS EERDER OOK AL GEDAAN)
				if ($extdebug == 1) { watchdog('php', '<pre>--- SKIP EXTENSION VOG-REF (empty contact_id) [groupID: '.$groupID.'] [op: '.$op.']---</pre>', null, WATCHDOG_DEBUG); }
				return; //   if not, get out of here
			}
		if ($extdebug == 1) { watchdog('php', '<pre>### 2. START EXTENSION VOG-REF [groupID: '.$groupID.'] [op: '.$op.'] [entityID: '.$entityID.'] [kampleider: '.$displayname.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>.part_id:'. print_r($part_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_eventid:'. print_r($part_eventid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_status_id:'. print_r($part_status_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>.part_welkkamp:'. print_r($part_welkkampleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_functie:'. print_r($part_functie, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>.tab_vogrecent:'. print_r($vogrecent, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.tab_refrecent:'. print_r($refrecent, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>.part_vogverzocht:'. print_r($part_vogverzocht, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_vogingediend:'. print_r($part_vogingediend, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_vogontvangst:'. print_r($part_vogontvangst, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_vogdatum:'. print_r($part_vogdatum, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_vogkenmerk:'. print_r($part_vogkenmerk, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_referentie_ingevuld:'. print_r($part_refingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>.part_referentie_naam:'. print_r($part_refnaam, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			###############################################################################################################
			// 2.1a BEPAAL OF DE VOG NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN VOG MOET WORDEN AANGEVRAAGD 
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.1a BEPAAL OF DE VOG NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN VOG MOET WORDEN AANGEVRAAGD</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(vogrecent):' . print_r(strtotime($vogrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    		if ($vogrecent) {
    			if ($vogrecent AND strtotime($vogrecent) < strtotime($fiscalyear_start) AND strtotime($vogrecent) >= strtotime($grensvognoggoed)) { // Datum VOG in previous 2 fiscal years
    				$vogdatethisyear = 0;
    				$vognodig = 'noggoed';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] IS RECENTER DAN START VAN AFGELOPEN 3 FISCALE JAREN (INCL.HUIDIGE) ['.$grensvognoggoed.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($vogrecent AND strtotime($vogrecent) >= strtotime($fiscalyear_start))	{ // Datum VOG binnen het huidige fiscal year
    				$vogdatethisyear = 0;
    				if ($hoevaakleid > 1) { $vognodig = 'opnieuw'; } else { $vognodig = 'eerstex'; }
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] VALT BINNEN HET HUIDIGE FISCAL YEAR ['.$fiscalyear_start.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($vogrecent AND strtotime($vogrecent) < strtotime($grensvognoggoed))		{ // Datum VOG ouder dan 3 fiscale jaren
    				$vogdatethisyear = 0;
    				$vognodig = 'opnieuw';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] IS OUDER DAN START VAN AFGELOPEN 3 FISCALE JAREN (INCL.HUIDIGE)['.$grensvognoggoed.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(vogrecent):' . print_r(strtotime($vogrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(grensvognoggoed):' . print_r(strtotime($grensvognoggoed), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(fiscalyear_start):' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>vogrecent:' . print_r($vogrecent, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		} else {
    			$vognodig = 'eerstex';
    		}
			#####################################################################################################
			# OVERRIDE DE BEREKENING VOOR BEPAALDE ROLLEN
 			#####################################################################################################
    		#if ($extdebug == 1) { watchdog('php', '<pre>vognodig_0:'. print_r($vognodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($extdebug == 1) { watchdog('php', '<pre>part_functie:'. print_r($part_functie, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($part_functie == 'hoofdleiding')	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($part_functie == 'bestuurslid')	 	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($ditjaarleidnot == 1)				{ $vognodig = ''; $refnodig = ''; }
    		if ($extdebug == 1) { watchdog('php', '<pre>vognodig_1:'. print_r($vognodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			###############################################################################################################
			### 2.1b BEPAAL OF DE REF NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN REF MOET WORDEN AANGEVRAAGD 
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.1b BEPAAL OF DE REF NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN REF MOET WORDEN AANGEVRAAGD</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(refrecent):' . print_r(strtotime($refrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($refrecent) {
    			if ($refrecent AND strtotime($refrecent) < strtotime($fiscalyear_start) AND strtotime($refrecent) >= strtotime($grensrefnoggoed)) { // Datum VOG in previous 2 fiscal years
    				$refdatethisyear = 0;
    				$refnodig = 'noggoed';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM REF ['.$refrecent.'] IS RECENTER DAN START VAN AFGELOPEN 3 FISCALE JAREN (INCL.HUIDIGE) ['.$grensrefnoggoed.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($refrecent AND strtotime($refrecent) >= strtotime($fiscalyear_start))	{ // Datum REF binnen het huidige fiscal year
    				$refdatethisyear = 0;
    				if ($hoevaakleid > 1) { $refnodig = 'opnieuw'; } else { $refnodig = 'eerstex'; }
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM REF ['.$refrecent.'] VALT BINNEN HET HUIDIGE FISCAL YEAR ['.$fiscalyear_start.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($refrecent AND strtotime($refrecent) < strtotime($grensrefnoggoed))		{ // Datum REF ouder dan 3 fiscale jaren
    				$refdatethisyear = 0;
    				$refnodig = 'opnieuw';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM REF ['.$refrecent.'] IS OUDER DAN START VAN AFGELOPEN 3 FISCALE JAREN (INCL.HUIDIGE)['.$grensrefnoggoed.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}

       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(refrecent):' . print_r(strtotime($refrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(grensrefnoggoed):' . print_r(strtotime($grensrefnoggoed), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(fiscalyear_start):' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>refrecent:' . print_r($refrecent, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_start:' . print_r($fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		} else {
    			$refnodig = 'eerstex';
    		}
			#####################################################################################################
			# OVERRIDE DE BEREKENING VOOR BEPAALDE ROLLEN
 			#####################################################################################################
    		#if ($extdebug == 1) { watchdog('php', '<pre>refnodig_0:'. print_r($refnodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($extdebug == 1) { watchdog('php', '<pre>part_functie:'. print_r($part_functie, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($part_functie == 'hoofdleiding')	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($part_functie == 'bestuurslid')	 	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($ditjaarleidnot == 1)				{ $vognodig = ''; $refnodig = ''; }

    		// M61: deze hier alleen tijdelijk om REF gelijk te trekken met VOG: dus pas bij VOG opnieuw ook REF opnieuw (ook indien nog nooit ingevuld)
    		if ($vognodig == 'noggoed')	 			{ $refnodig = 'noggoed'; }
    		if ($vognodig == 'opnieuw')	 			{ $refnodig = 'opnieuw'; }

    		if ($extdebug == 1) { watchdog('php', '<pre>refnodig_1:'. print_r($refnodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			#####################################################################################################
   			if (in_array($groupID, array("165", "213")) AND $ditjaarleidyes == 1) {	// PART DEEL + PART LEID + PART REF EN INDIEN DIT JAAR MEE ALS LEIDING
	    		if ($extdebug == 1) { watchdog('php', '<pre>### 2.1x. RETRIEVE REFERENTIE INGEVULD VANUIT CUSTOM FIELD AAN REFERENTIE ACTIVITEIT [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				$params_referentie = [
    				'sequential' 			=> 1,
      				'return'				=> "start_date",
      				'contact_id_a' 			=> $contact_id,
      				'relationship_type_id' 	=> 16,
     				'event_start_date_relative' => "this.fiscal_year",
      				//'start_date' 			=> ['>' => $fiscalyear_start],
    			];
    			try{
					#if ($extdebug == 1) { watchdog('php', '<pre>params_referentie:' . print_r($params_referentie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				$result = civicrm_api3('Relationship', 'getvalue', $params_referentie);
    				#if ($extdebug == 1) { watchdog('php', '<pre>params_referentie_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	    			$rel_refingevuld = $result;
    				if ($extdebug == 1) { watchdog('php', '<pre>relatie_referentie_ingevuld:' . print_r($rel_refingevuld, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  				catch (CiviCRM_API3_Exception $e) {
    				// Handle error here.
    				$errorMessage 	= $e->getMessage();
    				$errorCode 		= $e->getErrorCode();
    				$errorData 		= $e->getExtraParams();
   					if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				#if ($extdebug == 1) { watchdog('php', '<pre>ERROR: GEEN REFERENTIE ACTIVITEIT GEVONDEN:' . print_r($errorData, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  			}
			#####################################################################################################

			###############################################################################################################
			// 2.2 CHECK OF ER EEN VOG VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.2 CHECK OF ER EEN VOG VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
  			$verzoekditjaar = 0;
			if (strtotime($part_vogverzocht) >= strtotime($fiscalyear_start) AND strtotime($part_vogverzocht) <= strtotime($fiscalyear_end)) {
				// alleen indien vogverzocht binnen huidige fiscale jaar valt
				$verzoekditjaar = 1;
			}
			if ($extdebug == 1) { watchdog('php', '<pre>verzoekditjaar:' . print_r($verzoekditjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
			// 2.3 WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ (indien er een part_vog_datum is)
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.3 WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ (indien er een part_vog_datum is</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
    		if ($extvog == 1) {

				$params_contact['custom_998']		= $vognodig;	 # TAB NODIG VOG
   				if (($part_vogdatum AND !$vogrecent) OR strtotime($part_vogdatum) > strtotime($vogrecent)) {				// alleen overschrijven als er een nieuwere VOG datum is OF $vogrecent leeg is
					$params_contact['custom_56']	= $part_vogdatum;
					$params_contact['custom_68']	= $part_vogkenmerk;
				}
				$params_contact['custom_1019']		= $refnodig;	 # TAB NODIG REF
   				if (($part_refingevuld AND !$refrecent) AND strtotime($part_refingevuld) >= strtotime($fiscalyear_start)) {	// alleen overschrijven als er een nieuwere REG datum is OF $refingevuld leeg is
					$params_contact['custom_1004']	= $part_refingevuld;
					$params_contact['custom_1003']	= $part_refnaam;
				}

				#$params_contact['custom_1117'] = "params_intake_tab";

    			try{
   					#if ($extdebug == 1) { watchdog('php', '<pre>params_intake_tab:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#$result_intake_tab = civicrm_api3('Contact', 'create', $params_contact);
					#if ($extdebug == 1) { watchdog('php', '<pre>params_intake_tab_result:' . print_r($result_intake_tab, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_intake_tab EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
				}
				catch (CiviCRM_API3_Exception $e) {
   					// Handle error here.
   					$errorMessage 	= $e->getMessage();
   					$errorCode 		= $e->getErrorCode();
   					$errorData 		= $e->getExtraParams();
   					if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
   			}
/*
			$results_intake_tab_api4 = civicrm_api4('Contact', 'update', [
  				'where' => [
    				['id', '=', $contact_id],
  				],
  				'values' => [
    				'INTAKE.DITJAAR_VOG_nodig' => $vognodig, 
    				'INTAKE.DITJAAR_REF_nodig' => $refnodig,
  				],
  				'reload' => true,
  				'checkPermissions' => false,
			]);
			if ($extdebug == 1) { watchdog('php', '<pre>results_intake_tab_api4:' . print_r($results_intake_tab_api4, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
*/
			###############################################################################################################
			// 2.4 WERK DE GEGEVENS IN PART LEID VOG BIJ
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.4 WERK DE GEGEVENS IN PART LEID VOG BIJ</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
    		if ($extvog == 1 AND ($ditjaarleidmss == 1 OR $ditjaarleidyes == 1)) {
/*
				$params_participant = [
      				#'debug'        => 1,
   					'id'           => $part_id,
					'event_id'	   => $part_eventid,
   					'contact_id'   => $contact_id,
    			];
*/
    			$params_participant['id']  		   = $part_id;
    			$params_participant['event_id']	   = $part_eventid;
    			$params_participant['contact_id']  = $contact_id;

    			$params_participant['custom_990']  = $vognodig;	#PART NODIG VOG
				$params_participant['custom_1018'] = $refnodig;	#PART NODIG REF

    			if ($vognodig == 'noggoed') {
					#$params_participant['custom_603'] = $vogrecent;
					#$params_participant['custom_602'] = $vogkenmerk;
				}
   			}

   			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {	// PART LEID & TAB INTAKE EN INDIEN DIT JAAR MEE ALS LEIDING
			###############################################################################################################
			### 3 GET ACTIVITIES MBT. VOG
			###############################################################################################################
   				if ($extdebug == 1) { watchdog('php', '<pre>### 3. VOG ACTIVITIES [GET] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 3.1 GET ACTIVITIES 'VOG VERZOEK'
				// ************************************************************************************************************
   				$params_vog_activity_verzoek_get = [		// zoek activities 'VOG verzoek'
  					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG_verzoek",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_verzoek_get:' . print_r($params_vog_activity_verzoek_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_verzoek = civicrm_api3('Activity', 'get', $params_vog_activity_verzoek_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_verzoek_get_result:' . print_r($result_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_verzoek:' . print_r($result_verzoek['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_verzoek['count'] == 1) {
  					$vogverzoek_activity_id		= $result_verzoek['values'][0]['id'];
  					$vogverzoek_activity_status	= $result_verzoek['values'][0]['status_id'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogverzoek_activity_id		= NULL;
  					$vogverzoek_activity_status	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id: No Activity Found (VOG verzocht: ' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 3.2 GET ACTIVITIES 'VOG AANVRAAG'
				// ************************************************************************************************************
  				$params_vog_activity_aanvraag_get = [		// zoek activities 'VOG aanvraag'
   					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG_aanvraag",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_aanvraag_get:' . print_r($params_vog_activity_aanvraag_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$result_aanvraag = civicrm_api3('Activity', 'get', $params_vog_activity_aanvraag_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_aanvraag_get_result:' . print_r($result_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_aanvraag:' . print_r($result_aanvraag['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_aanvraag['count'] == 1) {
  					$vogaanvraag_activity_id		= $result_aanvraag['values'][0]['id'];
  					$vogaanvraag_activity_status	= $result_aanvraag['values'][0]['status_id'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogaanvraag_activity_id		= NULL;
  					$vogaanvraag_activity_status	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id: No Activity Found (VOG ingediend: ' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 3.3 GET ACTIVITIES 'VOG ONTVANGST'
				// ************************************************************************************************************
  				$params_vog_activity_ontvangst_get = [		// zoek activities 'VOG ontvangst'
   					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG_ontvangst",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_ontvangst_get:' . print_r($params_vog_activity_ontvangst_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_ontvangst = civicrm_api3('Activity', 'get', $params_vog_activity_ontvangst_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_ontvangst_get_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_ontvangst:' . print_r($result['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_ontvangst['count'] == 1) {
  					$vogontvangst_activity_id		= $result_ontvangst['values'][0]['id'];
  					$vogontvangst_activity_status	= $result_ontvangst['values'][0]['status_id'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogontvangst_activity_id		= NULL;
  					$vogontvangst_activity_status	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id: No Activity Found (VOG ontvangst: ' . print_r($part_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

  				}
			}

			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213"))) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
			###############################################################################################################
			### 4. BEPAAL DE JUISTE DATUMS VOOR ACTIVITIES AANVRAAG & ONTVANGST
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 4. VOG ACTIVITIES [DEFINE NEW DATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 4.1 BEPAAL (NIEUWE) DATUM ACTIVITY AANVRAAG
				// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>1. part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>3. part_vogontvangst:' . print_r($part_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>### 4.1 BEPAAL (NIEUWE) DATUM ACTIVITY AANVRAAG ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($part_vogingediend) { // TODO: als leiding datum in de toekomst ingaf, dat corrigeren naar datum van verzenden formulier
					$datum_aanvraag  = $part_vogingediend;											// ZET DATUM AANVRAAG VAN ACTIVITY GELIJK AAN AANVRAAGDATUM
				} else {
					$newdate		 = strtotime ( '+30 day' , strtotime ($part_vogverzocht) ) ;	// ZET DEADLINE AANVRAAG OP 30 DAGEN NA VERZOEK
					$datum_aanvraag  = date ( 'Y-m-d H:i:s' , $newdate );
				}
				#if ($extdebug == 1) { watchdog('php', '<pre>event_startdate:' . print_r($event_startdate, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// als nieuwe datum aanvraag > start kamp is, dan scheduled datum aanvraag = start kamp - 1wk DUBBELCHECK!
				if (strtotime($datum_aanvraag) >= strtotime($event_startdate))	{
					if ($extdebug == 1) { watchdog('php', '<pre>### DATUM ACTIVITY AANVRAAG > EVENT START DATE ###</pre>', NULL, WATCHDOG_DEBUG); }
					if ($welkeweeknr == 2) {
						$newdate		= strtotime ( '+7 day' , strtotime ($event_startdate) ) ;
						$datum_aanvraag = date ( 'Y-m-d' , $newdate );
					} else {
						$datum_aanvraag = $event_startdate;
					}
					if ($extdebug == 1) { watchdog('php', '<pre>AANVRAAG > STARTDATE: nieuwe datum aanvraag ivm weeknr ' . print_r($welkeweeknr, TRUE) . ' : ' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($extdebug == 1) { watchdog('php', '<pre>*. scheduled_datum_aanvraag:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 4.2 BEPAAL (NIEUWE) DATUM ACTIVITY ONTVANGST
				// ************************************************************************************************************

				if ($extdebug == 1) { watchdog('php', '<pre>### 4.2 BEPAAL (NIEUWE) DATUM ACTIVITY ONTVANGST ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($part_vogontvangst) { // VOG-ontvangst is datum van ontvangst, indien leeg dan datum ingediend of datum verzocht (zou ook datum vog kunnen zijn)
					$datum_ontvangst = $part_vogontvangst;											// ZET DATUM ONTVANGST VAN ACTIVITY GELIJK AAN ONTVANGSTDATUM
				} elseif ($part_vogingediend) {
					$newdate		 = strtotime ( '+60 day' , strtotime ($part_vogingediend) ) ;	// ZET 'DEADLINE' ONTVANGST OP 30 DAGEN NA INDIENEN AANVRAAG
					$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
				} else {																			// ZET EEN FICTIEVE DATUM VOOR ACTIVITY ONTVANGST 6 WEKEN NA VERZOEKDATUM
					$newdate		 = strtotime ( '+90 day' , strtotime ($part_vogverzocht) ) ;
					$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
				}
				#if ($extdebug == 1) { watchdog('php', '<pre>event_startdate:' . print_r($event_startdate, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// als nieuwe datum aanvraag > start kamp is, dan scheduled datum ontvangst = start kamp - 1wk DUBBELCHECK!
				if (strtotime($datum_ontvangst) >= strtotime($event_startdate))	{
					if ($extdebug == 1) { watchdog('php', '<pre>### DATUM ACTIVITY ONTVANGST > EVENT START DATE ###</pre>', NULL, WATCHDOG_DEBUG); }
					if ($welkeweeknr == 2) {
						$newdate		 = strtotime ( '+14 day' , strtotime ($event_startdate) ) ;
						$datum_ontvangst = date ( 'Y-m-d' , $newdate );
					} else {
						$newdate		 = strtotime ( '+7 day' , strtotime ($event_startdate) ) ;
						$datum_ontvangst = date ( 'Y-m-d' , $newdate );
					}
					if ($extdebug == 1) { watchdog('php', '<pre>ONTVANGST > STARTDATE: nieuwe datum ontvangst ivm weeknr ' . print_r($welkeweeknr, TRUE) . ' : ' . print_r($datum_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				} 
				#if ($extdebug == 1) { watchdog('php', '<pre>4. part_vogdatum:' . print_r($part_vogdatum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>### 4.x (NIEUWE) DATUM ACTIVITY AANVRAAG & ONTVANGST ###</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>*. scheduled_datum_aanvraag:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>*. scheduled_datum_ontvangst:' . print_r($datum_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}

			if ($extact == 1 AND (in_array($groupID, array("190", "140", "165", "213"))) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw")) AND $verzoekditjaar == 1) {
			###############################################################################################################
			### 5. CREATE ACTIVITIES
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 5. VOG ACTIVITIES [CREATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 5.1 CREATE AN ACTIVITY 'VERZOEK' ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ************************************************************************************************************
				if (empty($vogverzoek_activity_id) AND $part_vogverzocht) {
					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.1 CREATE AN ACTIVITY VERZOEK ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }
					/*
  					$params_vog_activity_create_verzoek = [		// update VOG aanvraag naar Completed als VOG ontvangst Completed is
  						'debug'        			=> 1,
  						"source_contact_id"		=> 1,
  						"target_id"				=> $contact_id,
  						'status_id'				=> "Completed",
  						'activity_type_id' 		=> "VOG_verzoek",
  						'subject' 				=> "VOG aanvraag verzocht",
  						'activity_date_time'	=> $part_vogverzocht,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek:' . print_r($params_vog_activity_create_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extact == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
  						$result = civicrm_api3('Activity', 'create', $params_vog_activity_create_verzoek);
  						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek EXECUTED</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if (empty($vogverzoek_activity_id))		{ $vogverzoek_activity_id		= key($result['values']); }
						if (empty($vogverzoek_activity_status))	{ $vogverzoek_activity_status	= 1; }
						if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id2:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status2:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					}
  					*/
  					if ($extvog == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$results = \Civi\Api4\Activity::create()
  						->addValue('source_contact_id', 1)
  						->addValue('target_contact_id', $contact_id)
  						->addValue('activity_type_id', 118)
  						->addValue('activity_date_time', $part_vogverzocht)
  						->addValue('subject', 'VOG aanvraag verzocht')
  						->addValue('status_id', 2) // initial status completed
		  				->addValue('checkPermissions', false)
  						->setChain([
    						'name_me_0' => ['ActivityContact', 'create', ['values' => ['activity_id' => '$id', 'contact_id' => $contact_id, 'record_type_id' => 3]], ], 
    						'name_me_1' => ['ActivityContact', 'create', ['values' => ['activity_id' => '$id', 'contact_id' => 1, 'record_type_id' => 2]], ]
  						])
  						->execute();
						foreach ($results as $result) {
  							// do something
  							#if ($extdebug == 1) { watchdog('php', '<pre>vog_verzoek_api4_create_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  							if (empty($vogverzoek_activity_id))		{ $vogverzoek_activity_id		= $result['id']; }
							if (empty($vogverzoek_activity_status))	{ $vogverzoek_activity_status	= 7; }
							if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id2:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status2:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

						}
					}
				}
				// ************************************************************************************************************
				// 5.2 CREATE AN ACTIVITY 'AANVRAAG' ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ***********************************************************************************************************
  				if (empty($vogaanvraag_activity_id) AND $datum_aanvraag AND ($part_vogverzocht OR $part_vogingediend)) {
  					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.2 CREATE AN ACTIVITY AANVRAAG ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }
  					/*
  					$params_vog_activity_create_aanvraag = [
  						'source_contact_id'		=> 1,
   						'target_id'				=> $contact_id,
  						'status_id'				=> 7, // initial status draft
  						'activity_type_id' 		=> 'VOG_aanvraag',
  						'subject' 				=> 'VOG aanvraag ingediend',
  						'activity_date_time'	=> $datum_aanvraag,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag:' . print_r($params_vog_activity_create_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

  					if ($extact == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
  						try{
    					#	$result = civicrm_api3('Activity', 'create', $params_vog_activity_create_aanvraag);
  						}
  						catch (CiviCRM_API3_Exception $e) {
    						// Handle error here.
    						$errorMessage 	= $e->getMessage();
    						$errorCode 		= $e->getErrorCode();
    						$errorData 		= $e->getExtraParams();
    						return [
      							'is_error'		=> 1,
      							'error_message'	=> $errorMessage,
      							'error_code'	=> $errorCode,
      							'error_data'	=> $errorData,
    						];
  						}
  						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag EXECUTED</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  						if (empty($vogaanvraag_activity_id))		{ $vogaanvraag_activity_id		= key($result['values']); }
						if (empty($vogaanvraag_activity_status))	{ $vogaanvraag_activity_status	= 1; }
						if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id2:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status2:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					}
  					*/
  					if ($extvog == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$results = \Civi\Api4\Activity::create()
  						->addValue('source_contact_id', 1)
  						->addValue('target_contact_id', $contact_id)
  						->addValue('activity_type_id', 119)
  						->addValue('activity_date_time', $datum_aanvraag)
  						->addValue('subject', 'VOG aanvraag ingediend')
  						->addValue('status_id', 7) // initial status draft
  						->addValue('checkPermissions', false)
  						->setChain([
    						'name_me_0' => ['ActivityContact', 'create', ['values' => ['activity_id' => '$id', 'contact_id' => $contact_id, 'record_type_id' => 3]], ], 
    						'name_me_1' => ['ActivityContact', 'create', ['values' => ['activity_id' => '$id', 'contact_id' => 1, 'record_type_id' => 2]], ]
  						])
  						->execute();
						foreach ($results as $result) {
  							// do something
  							#if ($extdebug == 1) { watchdog('php', '<pre>vog_aanvraag_api4_create_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  							if (empty($vogaanvraag_activity_id))		{ $vogaanvraag_activity_id		= $result['id']; }
							if (empty($vogaanvraag_activity_status))	{ $vogaanvraag_activity_status	= 7; }
							if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id2:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status2:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					}
				}

				// ************************************************************************************************************
				// 5.3 CREATE AN ACTIVITY 'ONTVANGST' ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ************************************************************************************************************
				if (empty($vogontvangst_activity_id) AND $datum_ontvangst AND ($part_vogverzocht OR $part_vogingediend)) {
					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.3 CREATE AN ACTIVITY ONTVANGST ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }
					/*
  					$params_vog_activity_create_ontvangst = [		// update VOG aanvraag naar Completed als VOG ontvangst Completed is
  						'sequential' 			=> 0,
  						'source_contact_id'		=> 1,
  						'target_id'				=> $contact_id,
  						'status_id'				=> "Pending",
  						'activity_type_id' 		=> "VOG_ontvangst",
  						'subject' 				=> "VOG ontvangst bevestigd",
  						'activity_date_time'	=> $datum_ontvangst,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst:' . print_r($params_vog_activity_create_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extact == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
  						$result = civicrm_api3('Activity', 'create', $params_vog_activity_create_ontvangst);
  						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst EXECUTED</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  						if (empty($vogontvangst_activity_id))		{ $vogontvangst_activity_id			= key($result['values']); }
						if (empty($vogontvangst_activity_status))	{ $vogontvangst_activity_status		= 1; }
						if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id2:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status2:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					}
  					*/
  					if ($extvog == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$results = \Civi\Api4\Activity::create()
  						->addValue('source_contact_id', 1)
  						->addValue('target_contact_id', $contact_id)
  						->addValue('activity_type_id', 120)
  						->addValue('activity_date_time', $datum_ontvangst)
  						->addValue('subject', 'VOG ontvangst bevestigd')
  						->addValue('status_id', 7) // initial status draft
  						->addValue('checkPermissions', false)
  						->setChain([
    						'name_me_0' => ['ActivityContact', 'create', ['values' => ['activity_id' => '$id', 'contact_id' => $contact_id, 'record_type_id' => 3]], ], 
    						'name_me_1' => ['ActivityContact', 'create', ['values' => ['activity_id' => '$id', 'contact_id' => 1, 'record_type_id' => 2]], ]
  						])
  						->execute();
						foreach ($results as $result) {
  							// do something
  							#if ($extdebug == 1) { watchdog('php', '<pre>vog_ontvangst_api4_create_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  							if (empty($vogontvangst_activity_id))		{ $vogontvangst_activity_id			= $result['id']; }
							if (empty($vogontvangst_activity_status))	{ $vogontvangst_activity_status		= 7; }
							if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id2:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status2:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					}
				}
			}

			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213"))) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw")) AND $verzoekditjaar == 1) {
			###############################################################################################################
			### 6. UPDATE ACTIVITIES
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 6. VOG ACTIVITIES [UPDATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT AANVRAAG
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT AANVRAAG</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				$diffsinceverzoek	= date_diff(date_create($part_vogverzocht),date_create($todaydatetime));
				$dayssinceverzoek	= $diffsinceverzoek->format('%a');
				if ($dayssinceverzoek >= 0  AND $dayssinceverzoek < 14)				{ $status_aanvraag = "Pending"; 		}	// AFWACHTING
				if ($dayssinceverzoek >= 14 AND $dayssinceverzoek < 21)				{ $status_aanvraag = "Left Message"; 	}	// HERINNERD
				if ($dayssinceverzoek >= 21 AND $dayssinceverzoek < 30)				{ $status_aanvraag = "Unreachable"; 	}	// ONBEREIKBAAR
				if ($dayssinceverzoek >= 30)										{ $status_aanvraag = "No_show"; 		}	// VERLOPEN
				#if ($dayssinceverzoek >= 30)										{ $status_aanvraag = "Pending"; 		}
				if (strtotime($todaydatetime) > strtotime($event_startdate) AND $status_aanvraag != "Completed")			{ $status_aanvraag = "Bounced"; 		} // Bounced nadat de startdag van kamp gepasseerd is

				// LET OP: DE VOLGENDE 2 REGELS NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if (strtotime($part_vogingediend) >= strtotime($fiscalyear_start))	{ // hier een between van maken (of liever nog een functie)
					#if ($extdebug == 1) { watchdog('php', '<pre>A. part_vogingediend:' . print_r(strtotime($part_vogingediend), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>B. fiscalyear_start:' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>STATUS AANVRAAG AANGEPAST VAN: '.$status_aanvraag.' NAAR Completed (omdat part_vogingediend: '.$part_vogingediend.' >= '.$fiscalyear_start.')</pre>', NULL, WATCHDOG_DEBUG); }
					$status_aanvraag  = "Completed";
				}
				if (strtotime($part_vogdatum)	  >= strtotime($fiscalyear_start) AND $status_aanvraag != 'Completed')	{ // hier een between van maken (of liever nog een functie)
					#if ($extdebug == 1) { watchdog('php', '<pre>A. part_vogdatum:' . print_r(strtotime($part_vogdatum), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>B. fiscalyear_start:' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>STATUS AANVRAAG AANGEPAST VAN: '.$status_aanvraag.' NAAR Completed (omdat part_vogdatum: '.$part_vogdatum.' >= '.$fiscalyear_start.')</pre>', NULL, WATCHDOG_DEBUG); }
					$status_aanvraag  = "Completed";
				}
				if (strtotime($part_vogingediend) < strtotime($fiscalyear_start) AND strtotime($part_vogdatum) < strtotime($fiscalyear_start))	{
					// als part_vogdatum binnen huidige fiscal year valt kan activity op completed (nu alleen later dan fiscal year start)
					if ($extdebug == 1) { watchdog('php', '<pre>a. part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>b. diffsinceverzoek:' . print_r($diffsinceverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>b. dagen_sinds_verzoek:' . print_r($dayssinceverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($extdebug == 1) { watchdog('php', '<pre>c. (new) status_aanvraag:' . print_r($status_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 6.2 BEPAAL (NIEUWE) STATUS ACTIVITEIT ONTVANGST
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.2 BEPAAL (NIEUWE) STATUS ACTIVITEIT ONTVANGST</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				$diffsinceaanvraag	= date_diff(date_create($part_vogingediend),date_create($todaydatetime));
				$dayssinceaanvraag	= $diffsinceaanvraag->format('%a');
				if ($dayssinceaanvraag >= 0  AND $dayssinceaanvraag < 28)			{ $status_ontvangst = "Pending"; 		}
				if ($dayssinceaanvraag >= 28 AND $dayssinceaanvraag < 42)			{ $status_ontvangst = "Left Message"; 	}
				if ($dayssinceaanvraag >= 42)										{ $status_ontvangst = "Unreachable"; 	}
				#if ($dayssinceverzoek  >= 21 AND $status_ontvangst == "Pending")	{ $status_ontvangst = "Left Message"; 	} // Als aanvraag Unreachable of Bounced is -> schedule dan alsnog een reminder rond geplande ontvangstdatum
				if (strtotime($todaydatetime) > strtotime($event_startdate) AND $status_ontvangst != "Completed")			{ $status_ontvangst = "No_show"; 		} // Bounced nadat de startdag van kamp gepasseerd is
				// LET OP: DE VOLGENDE 1 REGEL NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if ($status_aanvraag != "Completed")								{ $status_ontvangst	= "Available"; 		} // Maak status Activiteit ONTVANGST = Available als status AANVRAAG nog niet Completed is (civirules proof?)
				// LET OP: DE VOLGENDE 1 REGEL NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if (strtotime($part_vogdatum) >= strtotime($fiscalyear_start))		{ $status_ontvangst = "Completed"; 		} else {
					if ($extdebug == 1) { watchdog('php', '<pre>a. part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>b. diffsinceaanvraag:' . print_r($diffsinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>b. dagen_sinds_aanvraag:' . print_r($dayssinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				} // als part_vogdatum binnen huidige fiscal year valt kan activity op completed
				if ($extdebug == 1) { watchdog('php', '<pre>c. (new) status_ontvangst:' . print_r($status_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// *****************************************************************************************************************
				// 6.3 UPDATE ACTIVITY VERZOEK
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.3 UPDATE ACTIVITY VERZOEK STATUS NAV DAYS SINCE...</pre>', NULL, WATCHDOG_DEBUG); }	
				// *****************************************************************************************************************
				if ($vogverzoek_activity_id AND $part_vogverzocht) {
  					$params_vog_activity_change_verzoek = [
  						'id'					=> $vogverzoek_activity_id,
  						#'activity_type_id'		=> "VOG_verzoek",
  						'activity_date_time'	=> $part_vogverzocht,
  						#'subject' 				=> "VOG aanvraag verzocht",
  						#'status_id'				=> 2,
  						'status_id'				=> 'Completed',
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_verzoek:' . print_r($params_vog_activity_change_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api3('Activity', 'create', $params_vog_activity_change_verzoek);
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_verzoek EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_verzoek_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}

				// ************************************************************************************************************
				// 6.4 UPDATE ACTIVITY AANVRAAG STATUS N.A.V. DAYS SINCE...	
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.4 UPDATE ACTIVITY AANVRAAG STATUS N.A.V. DAYS SINCE...</pre>', NULL, WATCHDOG_DEBUG); }	
				// ************************************************************************************************************
				#if ((in_array($vogaanvraag_activity_status, array("1", "4", "5", "8")) AND in_array($vogontvangst_activity_status, array("2"))) AND $part_vogingediend) {
				#if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>datum_aanvraag:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>status_aanvraag:' . print_r($status_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($vogaanvraag_activity_id AND $datum_aanvraag AND $status_aanvraag) {
  					$params_vog_activity_change_aanvraag = [
  						'id'					=> $vogaanvraag_activity_id,
  						#'activity_type_id'		=> "VOG_aanvraag",
  						'activity_date_time'	=> $datum_aanvraag,
  						#'subject' 				=> "VOG aanvraag bevestigd",
  						'status_id'				=> $status_aanvraag,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag:' . print_r($params_vog_activity_change_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api3('Activity', 'create', $params_vog_activity_change_aanvraag);
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}

/*
				if ($vogaanvraag_activity_id AND $datum_aanvraag AND $status_aanvraag) {
  					$params_vog_activity_change_aanvraag3 = [
  							'id'					=> $vogaanvraag_activity_id,
  							'activity_type_id'		=> "VOG_aanvraag",
  							'activity_date_time'	=> $datum_aanvraag,
 							'subject' 				=> "VOG aanvraag ingediend",
   							'status_id'				=> $status_aanvraag,
  					];
  					$params_vog_activity_change_aanvraag = [
  							#'id'					=> $vogaanvraag_activity_id,
  							#'activity_type_id'		=> "VOG_aanvraag",
  							#'activity_date_time'	=> $datum_aanvraag,
 							#'subject' 				=> "VOG aanvraag ingediend",
   							#'status_id'				=> $status_aanvraag,
							'values' 				=> array('activity_date_time' => $datum_aanvraag, 'status_id' => $status_aanvraag, 'subject' => "VOG aanvraag ingediend"),
							#'where' 				=> array('id' => $vogaanvraag_activity_id, 'activity_type_id' => 119),
							#'where' 				=> 'id', '=', $vogaanvraag_activity_id,
							'where' 				=> array('id' => $vogaanvraag_activity_id),
							#'checkPermissions'		=> TRUE,
							#'checkPermissions'		=> FALSE,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag:' . print_r($params_vog_activity_change_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api4('Activity', 'Update', $params_vog_activity_change_aanvraag);
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
*/
				/*
				if ($vogaanvraag_activity_id AND $datum_aanvraag AND $status_aanvraag) {
					$results = \Civi\Api4\Activity::update()
  						->addValue('subject', 'VOG aanvraag ingediend')
  						->addValue('activity_date_time', $datum_aanvraag)
  						->addValue('status_id', $status_aanvraag)
  						->setReload(true)
  						->addWhere('id', '=', $vogaanvraag_activity_id)
  						->addWhere('activity_type_id', '=', 119)
  						->execute();
  					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag:' . print_r($params_vog_activity_change_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extact == 1)	{
						foreach ($results as $result) {
  							// do something
  							if ($extdebug == 1) { watchdog('php', '<pre>vog_aanvraag_api4_update_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				*/
				// *****************************************************************************************************************
				// 6.5 UPDATE ACTIVITY ONTVANGST STATUS NAV DAYS SINCE...
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.5 UPDATE ACTIVITY ONTVANGST STATUS NAV DAYS SINCE...</pre>', NULL, WATCHDOG_DEBUG); }	
				// *****************************************************************************************************************
				#if ((in_array($vogontvangst_activity_status, array("1", "4", "5", "8")) AND in_array($vogaanvraag_activity_status, array("2"))) AND $part_vogdatum AND $vognodig != 'noggoed') {
				if ($vogontvangst_activity_id AND $datum_ontvangst AND $status_ontvangst) {
  					$params_vog_activity_change_ontvangst = [
  						'id'					=> $vogontvangst_activity_id,
  						#'activity_type_id'		=> "VOG_ontvangst",
  						'activity_date_time'	=> $datum_ontvangst,
  						#'subject' 				=> "VOG ontvangst bevestigd",
  						'status_id'				=> $status_ontvangst,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst:' . print_r($params_vog_activity_change_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api3('Activity', 'create', $params_vog_activity_change_ontvangst);
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				/*
				if ($vogontvangst_activity_id AND $datum_ontvangst AND $status_ontvangst) {
					$results = \Civi\Api4\Activity::update()
  						->addValue('subject', 'VOG ontvangst bevestigd')
  						->addValue('activity_date_time', $datum_ontvangst)
  						->addValue('status_id', $status_ontvangst)
  						->setReload(true)
  						->addWhere('id', '=', $vogontvangst_activity_id)
  						->addWhere('activity_type_id', '=', 120)
  						->execute();
  					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag:' . print_r($params_vog_activity_change_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extact == 1)	{
						foreach ($results as $result) {
  							// do something
  							if ($extdebug == 1) { watchdog('php', '<pre>vog_ontvangst_api4_update_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
						if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_ontvangst_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				*/
			}

			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {
			###############################################################################################################
			### 7. DELETE ACTIVITIES (indien: 1. ze waren aangemaakt maar VOG nog goed was 2. er dit jaar geen verzoek was 3. de status niet completed was
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 7. VOG ACTIVITIES [DELETE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1 AND (in_array($vognodig, array("noggoed")) OR $ditjaarleidnot == 1 OR $verzoekditjaar == 0)) {
					#if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidnot:' . print_r($ditjaarleidnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	if ($vogverzoek_activity_id 	AND $vogverzoek_activity_status != 2)	{
			    		$result = civicrm_api3('Activity', 'delete', array('id' => $vogverzoek_activity_id,));
			    		if ($extdebug == 1) { watchdog('php', '<pre>ACTIVITY VERWIJDERD vogverzoek:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	}
			    	if ($vogaanvraag_activity_id 	AND $vogaanvraag_activity_status != 2)	{
			    		$result = civicrm_api3('Activity', 'delete', array('id' => $vogaanvraag_activity_id,));
			    		if ($extdebug == 1) { watchdog('php', '<pre>ACTIVITY VERWIJDERD vogaanvraag:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	}
			    	if ($vogontvangst_activity_id 	AND $vogontvangst_activity_status != 2)	{
			    		$result = civicrm_api3('Activity', 'delete', array('id' => $vogontvangst_activity_id,));
			    		if ($extdebug == 1) { watchdog('php', '<pre>ACTIVITY VERWIJDERD vogontvangst:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	}
				}
			}
			###############################################################################################################
			### 8 GET ACTIVITIES MBT. VOG
			###############################################################################################################
   				if ($extdebug == 1) { watchdog('php', '<pre>### 8. VOG ACTIVITIES [GET] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 8.1 GET ACTIVITIES 'VOG VERZOEK'
				// ************************************************************************************************************
   				$params_vog_activity_verzoek_get2 = [		// zoek activities 'VOG verzoek'
  					'sequential' 			=> 1,
  					'return' 				=> array("id", "activity_date_time", "status_id"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id' 		=> "VOG_verzoek",
  					'activity_date_time' 	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_verzoek_get:' . print_r($params_vog_activity_verzoek_get2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_verzoek2 = civicrm_api3('Activity', 'get', $params_vog_activity_verzoek_get2);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_verzoek_get_result:' . print_r($result_verzoek2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_verzoek:' . print_r($result_verzoek2['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_verzoek2['count'] == 1) {
  					$vogverzoek_activity_id2		= $result_verzoek2['values'][0]['id'];
  					$vogverzoek_activity_status2	= $result_verzoek2['values'][0]['status_id'];
  					$vogverzoek_activity_datetime2	= $result_verzoek2['values'][0]['activity_date_time'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id2:' . print_r($vogverzoek_activity_id2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status2:' . print_r($vogverzoek_activity_status2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_datetime2:' . print_r($vogverzoek_activity_datetime2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogverzoek_activity_id2		= NULL;
  					$vogverzoek_activity_status2	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id2: No Activity Found</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 8.2 GET ACTIVITIES 'VOG AANVRAAG'
				// ************************************************************************************************************
  				$params_vog_activity_aanvraag_get2 = [		// zoek activities 'VOG aanvraag'
   					'sequential' 			=> 1,
  					'return' 				=> array("id", "activity_date_time", "status_id"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id'		=> "VOG_aanvraag",
  					'activity_date_time' 	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_aanvraag_get2:' . print_r($params_vog_activity_aanvraag_get2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$result_aanvraag2 = civicrm_api3('Activity', 'get', $params_vog_activity_aanvraag_get2);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_aanvraag_get_result2:' . print_r($result_aanvraag2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_aanvraag2:' . print_r($result_aanvraag2['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_aanvraag2['count'] == 1) {
  					$vogaanvraag_activity_id2		= $result_aanvraag2['values'][0]['id'];
  					$vogaanvraag_activity_status2	= $result_aanvraag2['values'][0]['status_id'];
  					$vogaanvraag_activity_datetime2	= $result_aanvraag2['values'][0]['activity_date_time'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id2:' . print_r($vogaanvraag_activity_id2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status2:' . print_r($vogaanvraag_activity_status2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_datetime2:' . print_r($vogaanvraag_activity_datetime2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogaanvraag_activity_id2		= NULL;
  					$vogaanvraag_activity_status2	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id2: No Activity Found</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 8.3 GET ACTIVITIES 'VOG ONTVANGST'
				// ************************************************************************************************************
  				$params_vog_activity_ontvangst_get2 = [		// zoek activities 'VOG ontvangst'
   					'sequential'			=> 1,
  					'return' 				=> array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id' 		=> "VOG_ontvangst",
  					'activity_date_time'	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_ontvangst_get2:' . print_r($params_vog_activity_ontvangst_get2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_ontvangst2 = civicrm_api3('Activity', 'get', $params_vog_activity_ontvangst_get2);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_ontvangst_get_result2:' . print_r($result_ontvangst2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_ontvangst:' . print_r($result_ontvangst2['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_ontvangst2['count'] == 1) {
  					$vogontvangst_activity_id2		= $result_ontvangst2['values'][0]['id'];
  					$vogontvangst_activity_status2	= $result_ontvangst2['values'][0]['status_id'];
  					$vogontvangst_activity_datetime2= $result_ontvangst2['values'][0]['activity_date_time'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id2:' . print_r($vogontvangst_activity_id2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status2:' . print_r($vogontvangst_activity_status2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_datetime2:' . print_r($vogontvangst_activity_datetime2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogontvangst_activity_id2		= NULL;
  					$vogontvangst_activity_status2	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id2: No Activity Found</pre>', NULL, WATCHDOG_DEBUG); }
  				}
  		if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION VOG [groupID: '.$groupID.'] [op: '.$op.'] [entityID: '.$entityID.'] [kampleider: '.$displayname.'] ***</pre>', NULL, WATCHDOG_DEBUG); }

    	}
	}
	if ($params_contact) {
		$params_contact['debug'] = 1;
		if ($extdebug == 1) { watchdog('php', '<pre>params_contact:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result = civicrm_api3('Contact', 'create', $params_contact);
		if ($extdebug == 1) { watchdog('php', '<pre>params_contact EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
	}
	if ($params_participant) {
		$params_participant['debug'] = 1;
		if ($extdebug == 1) { watchdog('php', '<pre>params_participant:' . print_r($params_participant, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result = civicrm_api3('Participant', 'create', $params_participant);
		if ($extdebug == 1) { watchdog('php', '<pre>params_participant EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
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
