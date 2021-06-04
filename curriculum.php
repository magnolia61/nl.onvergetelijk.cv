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
	$exttag		= 1;
	$extrel		= 1;
	$extvog		= 1;
	$extact		= 1;
	$extref		= 1;
	$testreg	= 1;

	if ($op != 'create' && $op != 'edit') { //    did we just create or edit a custom object?
  		if ($extdebug == 1) { watchdog('php', '<pre>EXIT: op != create OR op != edit</pre>', NULL, WATCHDOG_DEBUG); }
		return; //	if not, get out of here
	}

	// if (in_array($groupID, array("149", "139", "190","140"))) { // CV & PART
	if (in_array($groupID, array("149", "139", "190"))) { // CV & PART
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
		$tagcv_deel 			= NULL;
		$tagcv_deel_array		= [];
   		$extcv_deel 			= NULL;
		$evtcv_deel_array 		= [];
   		$tagcv_leid 			= NULL;
   		$tagcv_leid_array 		= [];
   		$extcv_leid 			= NULL;
   		$extcv_leid_array 		= [];
   		$tagnr_leid 			= 0;
   		$tagverschildeel		= 0;
   		$tagverschilleid		= 0;
		$welkkamplang 			= 0;
		$welkkampkort 			= 0;
		$contact_id 			= NULL;
		$event_type_id 			= 0;

		$diteventdeelyes 		= 0;
		$diteventdeelmss 		= 0;
		$diteventdeelnot 		= 0;
		$diteventleidyes		= 0;
		$diteventleidmss 		= 0;
		$diteventleidnot 		= 0;

		$ditjaardeelyes 		= 0;
		$ditjaardeelmss 		= 0;
		$ditjaardeelnot 		= 0;
		$ditjaarleidyes			= 0;
		$ditjaarleidmss 		= 0;
		$ditjaarleidnot 		= 0;
		$eventdeelarray			= [];
		$eventleidarray			= [];
		$curcv_deel_array 		= [];
		$curcv_leid_array 		= [];
		$curcv_deel_array_nr	= 0;
		$curcv_leid_array_nr	= 0;
		$ditkaljaar 			= date("Y");
		$partstatusyes 			= array(1,2,5,6,15);								//	PARTICIPANT STATUS BETEKENT DEELNAME = YES
		$partstatusnot 			= array(4,11,16);									//	PARTICIPANT STATUS BETEKENT DEELNAME = NO (GECANCELLED, NIET GOEDGEKEURD OR PENDING-REFUND)
		$partstatusmss			= array(7,8,9,10);									//	PARTICIPANT STATUS BETEKENT DEELNAME = (NOG) NIET YES (WACHTLIJST OF GOEDKEURING)
		$eventypesdeel 			= array(11, 12, 13, 14, 21, 22, 23, 24, 33, 102);	//	EVENT_TYPE_ID'S VAN DE KAMPEN VAN DIT JAAR 			(- TEST_DEEL)
		$eventypesleid 			= array(1, 101);									//	EVENT_TYPE_ID VAN HET LEIDING EVENT VAN DIT JAAR 	(- TEST_LEID)
		$eventypesstaf 			= array(2);											//	EVENT_TYPE_ID VAN HET KAMPSTAF EVENT VAN DIT JAAR 	(- KAMPSTAF)
		$eventypesdeeltest 		= array(102);
		$eventypesleidtest 		= array(101);
		$vognodig 				= NULL;
		$refnodig 				= NULL;
		$vogdatethisyear 		= 0;
		$vogdatenextyear 		= 0;
		$part_reffeedback  		= NULL;
		$datum_drijf_ingevuld 	= NULL;
		$drupal_name 			= NULL;
		$part_eventtypeid		= NULL;
		$params_contact 		= NULL;
		$params_participant 	= NULL;
		$drupalufmatch_found 	= 0;
		$part_groepsletter		= NULL;
		$part_groepskleur		= NULL;
		$eerstedeel  			= "";
		$laatstedeel 			= "";
		$eersteleid  			= "";
		$laatsteleid 			= "";
		$eerstexdeel 			= NULL;
		$eerstexleid 			= NULL;

		#####################################################################################################
		# 1.0 BEPAAL DE GRENZEN VAN HET HUIDIGE FISCALE JAAR
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.0 BEPAAL DE GRENZEN VAN HET HUIDIGE FISCALE JAAR [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
/*
		$cache_fiscalyear_start = Civi::cache()->get('cache_fiscalyear_start');
		$cache_fiscalyear_end 	= Civi::cache()->get('cache_fiscalyear_end');

		if ($extdebug == 1) { watchdog('php', '<pre>cache fiscalyear_start:' . print_r($cache_fiscalyear_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>cache fiscalyear_end:' . print_r($cache_fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
*/
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

  		// zet grens 'NOG GOED OP 1 SEPTEMBER - reken te late VOG's nog bij dat jaar
		$grensvognoggoed 		= date("1-9-Y", strtotime("-3 year")); // VOG noggoed als datum binnen priveous 2 fiscal year valt
		$grensvognoggoedplusone = date("1-9-Y", strtotime("-2 year")); // M61 tijdelijk gebruiken voor bepalen of VOG volgend jaar weer nodig is tbv gelijktrekken REF
		$grensrefnoggoed 		= date("1-9-Y", strtotime("-3 year")); // REF noggoed als datum binnen priveous 2 fiscal year valt
  		if ($extdebug == 1) { watchdog('php', '<pre>grensvognoggoed:' . print_r($grensvognoggoed, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>grensrefnoggoed:' . print_r($grensrefnoggoed, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }


  		#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end 0:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if (strtotime($fiscalyear_end)   <= strtotime($todaydatetime)) {
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end :' . print_r(strtotime($fiscalyear_end), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			#if ($extdebug == 1) { watchdog('php', '<pre>today:' . print_r(strtotime($todaydatetime), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			$fiscalyear_end = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("+1 year"));
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end +1 year:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			$fiscalyear_end = date('Y-m-d',(strtotime ( '-1 day' , strtotime ( $fiscalyear_end) ) ));
  			#if ($extdebug == 1) { watchdog('php', '<pre>fiscalyear_end -1 day:' . print_r($fiscalyear_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#$ditkampjaar = date('Y', strtotime($fiscalyear_end)); // M61: zou misschien gewoon jaar van event_date moeten worden
		#if ($extdebug == 1) { watchdog('php', '<pre>ditkampjaar:' . print_r($ditkampjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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

		$params_event_deel = [
  			'checkPermissions' => FALSE,
  			'select' => [
    			'id', 'event_type_id', 'title', 'row_count',
  			],
  			'where' => [
    			['event_type_id', 'IN', $eventypesdeel],
    			//['title', 'NOT LIKE', '%TEST%'],
    			['start_date', '=', 'this.fiscal_year'],
  			],
		];

		#if ($extdebug == 1) { watchdog('php', '<pre>params_event_deel:' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result_deel = civicrm_api4('Event', 'get', $params_event_deel);
		#if ($extdebug == 1) { watchdog('php', '<pre>params_event_deel_result:' . print_r($result_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$kampidsdeelcount 	= $result_deel->count();
		$kampidsdeel 		= $result_deel->column('id');  // maakt een array met alleen de velden voor id
		ksort($kampidsdeel);

		$params_event_leid = [
  			'checkPermissions' => FALSE,
  			'select' => [
    			'id', 'event_type_id', 'title', 'row_count',
  			],
  			'where' => [
    			['event_type_id', 'IN', $eventypesleid],
    			['title', 'NOT LIKE', '%TEST%'],
    			['start_date', '=', 'this.fiscal_year'],
  			],
		];

		#if ($extdebug == 1) { watchdog('php', '<pre>params_event_leid:' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result_leid = civicrm_api4('Event', 'get', $params_event_leid);
		#if ($extdebug == 1) { watchdog('php', '<pre>params_event_leid_result:' . print_r($result_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$kampidsleid 		= $result_leid->column('id'); // maakt een array met alleen de velden voor id
		ksort($kampidsleid);
  		$kampids_all 		= $kampidsdeel;
  		array_push($kampids_all, $kampidsleid[0]);

		$params_event_staf = [
  			'checkPermissions' => FALSE,
  			'select' => [
    			'id', 'event_type_id', 'title', 'row_count',
  			],
  			'where' => [
    			['event_type_id', 'IN', $eventypesstaf],
    			['title', 'NOT LIKE', '%TEST%'],
    			['start_date', '>=', $todaydatetime],
    			['start_date', '<', $fiscalyear_end],
  			],
		];

		#if ($extdebug == 1) { watchdog('php', '<pre>params_event_staf:' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result_staf = civicrm_api4('Event', 'get', $params_event_staf);
		#if ($extdebug == 1) { watchdog('php', '<pre>params_event_staf_result:' . print_r($result_staf, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$kampidsstaf 		= $result_staf->column('id'); // maakt een array met alleen de velden voor id
		ksort($kampidsstaf);

		if ($extdebug == 1) { watchdog('php', '<pre>kampidsdeel:' . print_r($kampidsdeel, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampidsleid:' . print_r($kampidsleid, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampidsstaf:' . print_r($kampidsstaf, true) . '</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>kampids_all:' . print_r($kampids_all, true) . '</pre>', null, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.2 CHECK PARTICIPANT STATUS FOR ALL KAMPIDS
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.2 CHECK PARTICIPANT STATUS [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

    	# A. als partitipant wordt gewerkt dan zoek dan de participant info van dat event
    	# B. indien participant die wordt bewerkt is gecancelled dan kijken of er een andere registratie is die niet gecancceled is
    	# C. indien contact wordt geedit (en geen participant), dan kijken of er een actieve registratie in dit fiscal jaar is
    	# D. indien dit allemaal niet het geval is, gewoon basic info ophalen voor het contact

    	$params_partinfo = [
    		'debug'			=> 1,
      		'sequential'	=> 1,
      		'return' 		=> array("id","contact_id","first_name","event_id","participant_status_id", "participant_role_id", "custom_592", "custom_593", "custom_892", "custom_649","custom_567","custom_568","custom_56","custom_68","custom_599","custom_600","custom_959","custom_601", "custom_603","custom_602","custom_1004","custom_1003","custom_1021","custom_1018","custom_1295","custom_1296","custom_706","custom_1038", "custom_1214", "custom_1215", "custom_968", "custom_1227", "custom_967", "custom_961"),
      		'status_id'		=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "On waitlist", "Pending from waitlist", "Awaiting approval", "Partially paid", "Pending refund", "Cancelled"),
    		];

    	if (in_array($groupID, array("139", "190", "140"))) {	// PART DEEL + PART LEID + PART LEID VOG
			$params_partinfo['id'] = $entityID;
    	}
		if (in_array($groupID, array("149"))) {					// TAB TALENT
			$params_partinfo['contact_id'] = $entityID;
			$params_partinfo['event_id'] = array('IN' => $kampids_all);
    	}

   		if ($extdebug == 1) { watchdog('php', '<pre>ZOEK DE RELEVANTE VELDEN VOOR DEZE REGISTRATIE [PartID: '.$entityID.']</pre>', NULL, WATCHDOG_DEBUG); }
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
		#####################################################################################################
		# INDIEN (DIT JAAR) GECANCELLED ZOEK OF ER EEN ANDERE GELDIGE REGISTRATIE IS VOOR DIT JAAR
		#####################################################################################################
		// M61: TODO TODO !!!! ALS JE EEN DEELNEMER HANDMATIG OP GECANCELLED ZET MOET DAT EVENT GECANCELLED WORDEN EN HET EVENT_ID VAN DAT EVENT GEBRUIKT WORDEN.
		// DAAROM VOORLOPIG DIT MAAR EVEN DEACTIVEREN
		/*
		if (in_array($groupID, array("103", "149")) OR $part_status_id == 4) {	// TAB WERVING of DEELNAME CANCELLED
   			$params_partinfo = [
      			'sequential' 	=> 1,
      			'return' 		=> array("id","contact_id","first_name","event_id","participant_status_id", "participant_role_id", "custom_592", "custom_593", "custom_892", "custom_649","custom_567","custom_568","custom_56","custom_68","custom_599","custom_600","custom_959","custom_601", "custom_603","custom_602","custom_1004","custom_1003","custom_1021","custom_1018","custom_1295","custom_1296","custom_706","custom_1038"),
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
    	*/
   		#####################################################################################################
		# GET CONTACT INFO WHEN TABS ARE CHECKED INSTEAD OF PARTICIPANT INFO IS EDITED
		#####################################################################################################
		$params_contactinfo = [
   			'debug'			=> 1,
   			'sequential'	=> 1,
   		];
		$params_contactinfo['return'] 		= array("id","contact_id","email", "first_name","middle_name","last_name","display_name","job_title","custom_376","custom_73","custom_74","custom_647","custom_474","custom_663","custom_377","custom_378","custom_1172");
		$params_contactinfo['contact_id'] 	= $contact_id;

		#if ($extdebug == 1) { watchdog('php', '<pre>params_contactinfo:' . print_r($params_contactinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result_cont = civicrm_api3('Contact', 'get', $params_contactinfo);
		#if ($extdebug == 1) { watchdog('php', '<pre>params_contactinfo_result:' . print_r($result_cont, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.3 ASIGN RETREIVED VALUES TO VARIABLES
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.3 ASIGN RETREIVED VALUES TO VARIABLES [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		$contact_id 			= $result_cont['values'][0]['contact_id'];
		$prim_email 			= trim($result_cont['values'][0]['email']);
		$first_name				= trim($result_cont['values'][0]['first_name']);
		$middle_name			= trim($result_cont['values'][0]['middle_name']);
		$last_name				= trim($result_cont['values'][0]['last_name']);
 		$displayname 			= trim($result_cont['values'][0]['display_name']);
 		$drupalnaam				= trim($result_cont['values'][0]['job_title']);
 		if ($extdebug == 1) { watchdog('php', '<pre>contact_id:'. print_r($contact_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
 		if ($extdebug == 1) { watchdog('php', '<pre>prim_email:'. print_r($prim_email, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
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

		if (in_array($part_role_id, array("7", "8"))) { // ROLE_ID = DEELNEMER of DEELNEMER TOPKAMP
			$part_functie 					= 'deelnemer';
			$part_groepklas 				= $result_part['values'][0]['custom_593'];
			$part_groepsvoorkeur 			= $result_part['values'][0]['custom_892'];
			$part_tijdslotbrengen 			= $result_part['values'][0]['custom_1214'];
			$part_tijdslothalen_aankomst 	= $result_part['values'][0]['custom_1215'];

			$options = civicrm_api3('Contact','getoptions', array(
				'field' => 'custom_1215',
			));
			if ($extdebug == 1) { watchdog('php', '<pre>options_result:'. print_r($options, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#$part_tijdslothalen_aankomst 	= array_search($part_tijdslothalen, $options);
			$part_tijdslothalen 	= $options['values'][$part_tijdslothalen_aankomst];
			
			$part_groepsletter 		= $result_part['values'][0]['custom_968'];
			$part_groepskleur 		= $result_part['values'][0]['custom_1227'];
			$part_slaapzaal 		= $result_part['values'][0]['custom_967'];
			if ($extdebug == 1) { watchdog('php', '<pre>part_groepklas:'. print_r($part_groepklas, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_groepsvoorkeur:'. print_r($part_groepsvoorkeur, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_tijdslotbrengen:'. print_r($part_tijdslotbrengen, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_tijdslothalen:'. print_r($part_tijdslothalen, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_tijdslothalen_aankomst:'. print_r($part_tijdslothalen_aankomst, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_groepsletter:'. print_r($part_groepsletter, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_groepskleur:'. print_r($part_groepskleur, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_slaapzaal:'. print_r($part_slaapzaal, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}

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

   			$refrecent 				= $result_part['values'][0]['custom_1004'];	// TAB DITJAAR datum van de laatste referentie // M61 ??? dat doet dit?
   			$refkenmerk 			= $result_part['values'][0]['custom_1003'];	// TAB DITJAAR naam van de laatste referentie
   			$part_refpersoon 		= $result_part['values'][0]['custom_1301'];
   			$part_refgevraagd 		= $result_part['values'][0]['custom_1295'];
			$part_reffeedback 		= $result_part['values'][0]['custom_1296'];	// PART datum feedback van referentie
 			
			if ($extdebug == 1) { watchdog('php', '<pre>part_welkkampleid:'. print_r($part_welkkampleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>part_functie:'. print_r($part_functie, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_refpersoon:'. print_r($part_refpersoon, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_refgevraagd:'. print_r($part_refgevraagd, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>part_reffeedback:'. print_r($part_reffeedback, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}

		$curcv_deel_array	 	= $result_cont['values'][0]['custom_376'];	// welke jaren deel
		#if (!is_array($curcv_deel_array)) { $curcv_deel_array = []; } 
		$curcv_leid_array	 	= $result_cont['values'][0]['custom_73'];	// welke jaren leid
		#if (!is_array($curcv_leid_array)) { $curcv_leid_array = []; } 

		#if ($extdebug == 1) { watchdog('php', '<pre>curcv_deel 0:'. print_r($curcv_deel_array, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>curcv_leid 0:'. print_r($curcv_leid_array, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# CORRIGEER DATUM DRIJFVEREN INGEVULD VANUIT DATUM BELANGSTELLING OF DRIJFVEREN GECHECKED (zou niet nodig moeten zijn)
		#####################################################################################################

		$datum_belangstelling 	= $result_cont['values'][0]['custom_647'];
		$datum_drijf_ingevuld	= $result_cont['values'][0]['custom_474'];
		$datum_drijf_gechecked	= $result_cont['values'][0]['custom_663'];

   		if ($extdebug == 1) { watchdog('php', '<pre>$datum_belangstelling:'. print_r($datum_belangstelling, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>$datum_drijf_ingevuld:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>$datum_drijf_gechecked:'. print_r($datum_drijf_gechecked, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		#if ($datum_belangstelling AND empty($datum_drijf_ingevuld)) {
		#	$datum_drijf_ingevuld = $datum_belangstelling;
		#	if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld_van_belangstelling:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		#}
		if ($datum_drijf_gechecked AND empty($datum_drijf_ingevuld)) {
			$datum_drijf_ingevuld = $datum_drijf_gechecked;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld_van_drijf_gechecked:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}
		if ($datum_drijf_ingevuld AND empty($datum_belangstelling) AND $curcv_leid_nr == 0) {
		//	VOOR ALS DATUM DRIJF WEL IS INGEVULD VOOR NIEUWE LEIDING MAAR DATUM BEL NIET
			$datum_belangstelling = $datum_drijf_ingevuld;
			if ($extdebug == 1) { watchdog('php', '<pre>datum_belangstelling_van_drijf_ingevuld:'. print_r($datum_belangstelling, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }	
		}

		#####################################################################################################
		# 'CORRIGEER' VELDEN RONDOM BELANGSTELLING
		#####################################################################################################

		$belangstelling_week 	= $result_cont['values'][0]['custom_377'];
		$belangstelling_groep	= $result_cont['values'][0]['custom_378'];
		$belangstelling_kampen	= $result_cont['values'][0]['custom_1172'];
		$belangstelling_array	= [];

		if ($extdebug == 1) { watchdog('php', '<pre>belangstelling_week:'. print_r($belangstelling_week, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>belangstelling_groep:'. print_r($belangstelling_groep, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if (($belangstelling_week == 'week1' OR $belangstelling_week == 'maaktnietuit') AND in_array('kinderkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'KK1');
		}
		if (($belangstelling_week == 'week2' OR $belangstelling_week == 'maaktnietuit') AND in_array('kinderkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'KK2');
		}
		if (($belangstelling_week == 'week1' OR $belangstelling_week == 'maaktnietuit') AND in_array('brugkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'BK1');
		}
		if (($belangstelling_week == 'week2' OR $belangstelling_week == 'maaktnietuit') AND in_array('brugkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'BK2');
		}
		if (($belangstelling_week == 'week1' OR $belangstelling_week == 'maaktnietuit') AND in_array('tienerkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'TK1');
		}
		if (($belangstelling_week == 'week2' OR $belangstelling_week == 'maaktnietuit') AND in_array('tienerkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'TK2');
		}
		if (($belangstelling_week == 'week1' OR $belangstelling_week == 'maaktnietuit') AND in_array('jeugdkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'JK1');
		}
		if (($belangstelling_week == 'week2' OR $belangstelling_week == 'maaktnietuit') AND in_array('jeugdkamp', $belangstelling_groep)) {
			array_push($belangstelling_array, 'JK2');
		}

		#if ($extdebug == 1) { watchdog('php', '<pre>belangstelling_kampen:'. print_r($belangstelling_kampen, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>belangstelling_kampen:'. print_r($belangstelling_array, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

   		#####################################################################################################
		# LET OP !!!! $contact_id hoort niet leeg te zijn
		#####################################################################################################

		if (empty($contact_id)) {
			if ($extdebug == 1) { watchdog('php', '<pre>ERROR: CONTACT INFO LEEG > RETURN</pre>', NULL, WATCHDOG_DEBUG); }
			return; //    if not, get out of here
		}

		#####################################################################################################
		# 1.4 RETRIEVE THE EVENT TYPE ID OF THE EVENT (M61: waarom staat dit er eigenlijk in?) 
		# IGV LEIDING DAN ZAL EVENT_TYPE_ID 1 ZIJN & EVENT_ID DAT VAN LEIDING EVENT
   		if ($extdebug == 1) { watchdog('php', '<pre>### 1.4 RETRIEVE THE EVENT TYPE ID OF THE EVENT [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if (in_array($part_eventtypeid, $eventypesdeel) OR in_array($part_eventtypeid, $eventypesleid)) {
			#$part_eventtypeid 	= NULL;
    		if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid (overwrite):' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	} else {
   			if ($part_eventid) {
   				$result = civicrm_api3('Event', 'get', array(
    				'sequential' 	=> 1,
      				'return' 		=> array("event_type_id", "start_date"),
					'event_id' 		=> $part_eventid, 			// eventid of specific kamp
    			));
    			$part_eventtypeid 	= $result['values'][0]['event_type_id']; 
    			$event_startdate 	= $result['values'][0]['start_date'];
				$evtkampjaar 		= date('Y', strtotime($event_startdate));
				$evtkampjaarkort	= date('y', strtotime($event_startdate));
				$ditkampjaar 		= date('Y', strtotime($fiscalyear_end));
				if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:' . print_r($part_eventid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	   			if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid:' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	   			if ($extdebug == 1) { watchdog('php', '<pre>evtkampjaar:' . print_r($evtkampjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>ditkampjaar:' . print_r($ditkampjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
    	}
    	###################################################################################################

		#####################################################################################################
		# 1.5 CHECK OF DEZE PERSOON DIT EVENT MEEGAAT ALS DEELNEMER OF LEIDING (OOK VOOR EVENTS IN HET VERLEDEN)
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.5. CHECK OF '.$displayname.' DIT EVENT MEEGAAT ALS DEELNEMER OF LEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if (in_array($part_eventtypeid, $eventypesdeel) AND in_array($part_status_id, $partstatusyes)) {	
			$diteventdeelyes = 1;
			$diteventdeeltxt = 'WEL!';
		} elseif (in_array($part_eventtypeid, $eventypesdeel) AND $part_status_id == 4) {
			$diteventdeelnot = 1;
			$diteventdeeltxt = 'ANNU';
		} elseif (in_array($part_eventtypeid, $eventypesdeel) AND in_array($part_status_id, $partstatusmss)) {
			$diteventdeelmss = 1;
			$diteventdeeltxt = 'MSS.';
		} else {
			$diteventdeelnot = 1;
			$diteventdeeltxt = 'NIET';
		}
		#####################################################################################################
		if (in_array($part_eventtypeid, $eventypesleid) AND in_array($part_status_id, $partstatusyes)) {
			$diteventleidyes = 1;
			$diteventleidtxt = 'WEL!';
		} elseif (in_array($part_eventtypeid, $eventypesleid) AND $part_status_id == 4) {
			$diteventleidnot = 1;
			$diteventleidtxt = 'ANNU';
		} elseif (in_array($part_eventtypeid, $eventypesleid) AND in_array($part_status_id, $partstatusmss)) {
			$diteventleidmss = 1;
			$diteventleidtxt = 'MSS.';
		} else {
			$diteventleidnot = 1;
			$diteventleidtxt = 'NIET';
		}

		if ($extdebug == 1) { watchdog('php', '<pre>DEEL EVENT_id ('.$part_eventid.') EVENT TYPE ('.$part_eventtypeid.') DITEVENT *'.$diteventdeeltxt.'* MEE ALS deelnemer [status:'.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>LEID EVENT_id ('.$part_eventid.') EVENT TYPE ('.$part_eventtypeid.') DITEVENT *'.$diteventleidtxt.'* MEE ALS '.$part_functie.' [status:'.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>diteventdeelyes:' . print_r($diteventdeelyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>diteventdeelmss:' . print_r($diteventdeelmss, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>diteventdeelnot:' . print_r($diteventdeelnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>diteventleidyes:' . print_r($diteventleidyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>diteventleidnot:' . print_r($diteventleidnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.6 CHECK OF DEZE PERSOON DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.6. CHECK OF '.$displayname.' DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if (in_array($part_eventid, $kampidsdeel) AND in_array($part_status_id, $partstatusyes)) {
			$ditjaardeelyes = 1;
			$ditjaardeeltxt = 'WEL!';
		} elseif (in_array($part_eventid, $kampidsdeel) AND $part_status_id == 4) {
			$ditjaardeelnot = 1;
			$ditjaardeeltxt = 'ANNU';
		} elseif (in_array($part_eventid, $kampidsdeel) AND in_array($part_status_id, $partstatusmss)) {
			$ditjaardeelmss = 1;
			$ditjaardeeltxt = 'MSS.';
		} else {
			$ditjaardeelnot = 1;
			$ditjaardeeltxt = 'NIET';
		}
		#####################################################################################################
		if (in_array($part_eventid, $kampidsleid) AND in_array($part_status_id, $partstatusyes)) {
			$ditjaarleidyes = 1;
			$ditjaarleidtxt = 'WEL!';
		} elseif (in_array($part_eventid, $kampidsleid) AND $part_status_id == 4) {
			$ditjaarleidnot = 1;
			$ditjaarleidtxt = 'ANNU';
		} elseif (strtotime($datum_belangstelling) >= strtotime($fiscalyear_start)) {			
			$ditjaarleidmss = 1;
			$ditjaarleidtxt = 'MSS.';
		} else {
			$ditjaarleidnot = 1;
			$ditjaarleidtxt = 'NIET';
		}

		if ($extdebug == 1) { watchdog('php', '<pre>DEEL EVENT_id ('.$part_eventid.') EVENT TYPE ('.$part_eventtypeid.') DITJAAR *'.$ditjaardeeltxt.'* MEE ALS deelnemer [status:'.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>LEID EVENT_id ('.$part_eventid.') EVENT TYPE ('.$part_eventtypeid.') DITJAAR *'.$ditjaarleidtxt.'* MEE ALS '.$part_functie.' [status:'.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelyes:' . print_r($ditjaardeelyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelmss:' . print_r($ditjaardeelmss, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaardeelnot:' . print_r($ditjaardeelnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidyes:' . print_r($ditjaarleidyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidmss:' . print_r($ditjaarleidmss, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>ditjaarleidnot:' . print_r($ditjaarleidnot, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.6b RETREIVE LEEFTIJD TIJDENS START KAMP
		#####################################################################################################
		if ($extdebug == 1) { watchdog('php', '<pre>### 1.6b RETRIEVE CURRENT AGE AND AGE AT EVENT [part_eventid: '.$part_eventid.'] ('.$groupID.') ###</pre>', NULL, WATCHDOG_DEBUG); }
		if ($diteventdeelyes == 1 OR $diteventleidyes == 1) {
			$fop		= $op;
			$fgroupID	= $groupID;
			$fentityID	= $entityID;
			$fbasedate	= $event_startdate;
			if ($extdebug == 1) { watchdog('php', '<pre>event_start_date:'. print_r($fbasedate, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			$leeftijdevenement = leeftijd_configure($fop, $fgroupID, $fentityID, $fbasedate);
			if ($extdebug == 1) { watchdog('php', '<pre>leeftijdevenement:'. print_r($leeftijdevenement, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}
		
			$fop		= $op;
			$fgroupID	= $groupID;
			$fentityID	= $entityID;
			$fbasedate	= date("Y-m-d");
			if ($extdebug == 1) { watchdog('php', '<pre>datum vandaag:'. print_r($fbasedate, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			$leeftijdvandaag = leeftijd_configure($fop, $fgroupID, $fentityID, $fbasedate);
			if ($extdebug == 1) { watchdog('php', '<pre>leeftijdvandaag:'. print_r($leeftijdvandaag, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		#####################################################################################################
		# 1.7 RETRIEVE DRUPAL UID, LOGINNAME & EMAIL
		#####################################################################################################

   		if (in_array($groupID, array("149", "139", "190"))) {	// PART DEEL + PART LEID + PART LEID VOG + PART LEID REF
			if ($extdebug == 1) { watchdog('php', '<pre>### 1.7 RETRIEVE DRUPAL UID, LOGINNAME & EMAIL [part_eventid: '.$part_eventid.'] ('.$groupID.') ###</pre>', NULL, WATCHDOG_DEBUG); }
    		// CONSTRUCT A PASSWORD
			$user_pwd		= bin2hex(openssl_random_pseudo_bytes(8));
    		// CONSTRUCT A USERNAME
			$firstname 		= str_replace(' ', '_', $first_name);
			$middlename 	= str_replace(' ', '_', $middle_name);
			$lastname 		= str_replace(' ', '_', $last_name);

			$firstname 		= transliterator_transliterate('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII', $firstname);
			$middlename 	= transliterator_transliterate('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII', $middlename);
			$lastname 		= transliterator_transliterate('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII', $lastname);

			#if ($extdebug == 1) { watchdog('php', '<pre>FN:' . print_r($firstname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>MN:' . print_r($middlename, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>LN:' . print_r($lastname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			$firstname 		= preg_replace('/[^ \w-]/','',strtolower(trim($firstname)));	// keep only letters and numbers and dashes
			$middlename 	= preg_replace('/[^ \w-]/','',strtolower(trim($middlename)));	// keep only letters and numbers and dashes
			$lastname 		= preg_replace('/[^ \w-]/','',strtolower(trim($lastname)));		// keep only letters and numbers and dashes

			#if ($extdebug == 1) { watchdog('php', '<pre>FN:' . print_r($firstname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>MN:' . print_r($middlename, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>LN:' . print_r($lastname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			if ($middlename) {
				$user_name	= $firstname.".".$middlename.".".$lastname;
			} else {
				$user_name	= $firstname.".".$lastname;
			}
			$user_name	= strtolower(trim($user_name));					// lowercase username
			#$user_name 	= preg_replace('/[^ \w-]/','',$user_name);	// keep only letters and numbers and dashes
			#$user_name 	= normalizer_normalize($user_name);			// remove accent marks using PHP's *intl*
			// CONSTRUCT THE EMAIL
			if (in_array($part_functie, array('hoofdleiding', 'kernteamlid', 'hoofdkeuken'))) {
				$user_email	= $user_name."@onvergetelijk.nl";
				if ($extdebug == 1) { watchdog('php', '<pre>user_email:' . print_r($user_email, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			} else {
				$user_email	= $prim_email; 					// IF NOT DEELNEMER > GEBRUIK PRIMARY EMAIL
				if ($extdebug == 1) { watchdog('php', '<pre>user_email:' . print_r($user_email, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			if (($leeftijdvandaag > 0 AND $leeftijdvandaag < 18) OR empty($prim_email)) {
				$user_email	= $user_name."@placeholder.nl";	// GEBRUIK SWS PLACEHOLDER <18 JAAR
			}
			#if ($extdebug == 1) { watchdog('php', '<pre>part_functie:' . print_r($part_functie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>leeftijdvandaag:' . print_r($leeftijdvandaag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>prim_email:' . print_r($prim_email, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>user_email:' . print_r($user_email, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>user_name:' . print_r($user_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			$params_drupaluser = [
      			'return' 		=> "name",
      			'contact_id' 	=> $contact_id,
    		];

    	    try{
	   			#if ($extdebug == 1) { watchdog('php', '<pre>params_drupaluser:' . print_r($params_drupaluser, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$result_drupal = civicrm_api3('User', 'getvalue', $params_drupaluser);
   				#if ($extdebug == 1) { watchdog('php', '<pre>params_drupaluser_result:' . print_r($result_drupal, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			catch (CiviCRM_API3_Exception $e) {
   				// Handle error here.
   				$errorMessage 	= $e->getMessage();
   				$errorCode 		= $e->getErrorCode();
   				$errorData 		= $e->getExtraParams();
   				#if ($extdebug == 1) { watchdog('php', '<pre>ERRORCODE:' . print_r($errorCode, TRUE) . ' ERRORMESSAGE:' . print_r($errorMessage, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			$drupalname_found = 0;
			}
      		if ($result_drupal !== false) {
				$drupal_name = $result_drupal;
			}
  			if ($drupal_name) {
	    		#if ($extdebug == 1) { watchdog('php', '<pre>a) PRIMA: VIA CID '.$contact_id.' WEL DRUPAL NAAM GEVONDEN: '.$drupal_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
	    		if ($extdebug == 1) { watchdog('php', '<pre>a) PRIMA: VIA CID: '.$contact_id.' WEL DRUPAL NAAM GEVONDEN | drupalname: '.$drupal_name.'</pre>', NULL, WATCHDOG_DEBUG); }
  				$drupalname_found = 1;
  			} else {
				#if ($extdebug == 1) { watchdog('php', '<pre>a) ERROR: VIA CID '.$contact_id.' GEEN DRUPAL NAAM GEVONDEN</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>a) ERROR: VIA CID: '.$contact_id.' GEEN DRUPAL NAAM GEVONDEN</pre>', NULL, WATCHDOG_DEBUG); }
    			$drupalname_found = 0;
  			}
			#####################################################################################################
  			#if ($drupalname_found == 0) {
			#####################################################################################################
				#if ($extdebug == 1) { watchdog('php', '<pre>constructed user_name: ' . print_r($user_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// CHECK IF A CURRENT UF MATCH IS PRESENT
				$params_ufmatchget = [
  					'checkPermissions' => FALSE,
  					'select' => [
    					'id', 
    					'uf_id', 
    					'contact_id',
  					],
  					'where' => [
    					['contact_id', '=', $contact_id],
  					],
				];
      			#if ($extdebug == 1) { watchdog('php', '<pre>params_ufmatchget:' . print_r($params_ufmatchget, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
      			$result 		= civicrm_api4('UFMatch', 'get', $params_ufmatchget);
      			if ($result !== false) {
      				$ufmatch_id 	= $result[0]['id'];
    				$ufmatch_ufid 	= $result[0]['uf_id'];
    			}

    			if ($ufmatch_ufid) {
					if ($extdebug == 1) { watchdog('php', '<pre>b) PRIMA: VIA CID: '.$contact_id.' WEL EEN UFMATCH GEVONDEN | drupalid: '.$ufmatch_ufid.'</pre>', NULL, WATCHDOG_DEBUG); }
    				$drupalufmatch_found = 1;
    				#if ($extdebug == 1) { watchdog('php', '<pre>Current user UFMatch_id: ' . print_r($ufmatch_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>Current user UFMatch_ufid: ' . print_r($ufmatch_ufid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    			} else {
					if ($extdebug == 1) { watchdog('php', '<pre>b) ERROR: VIA CID: '.$contact_id.' GEEN UFMATCH GEVONDEN</pre>', NULL, WATCHDOG_DEBUG); }
    				$drupalufmatch_found = 0;
    			}
    		#}
    		#if ($extdebug == 1) { watchdog('php', '<pre>drupalufmatch_found: ' . print_r($drupalufmatch_found, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################
				// CHECK IF A CONFLICTING UF MATCH IS PRESENT FOR ANOTHER CONTACT_ID
				$params_ufmatchget = [
					'checkPermissions' => FALSE,
					'select' => [
						'id', 
						'uf_id', 
						'contact_id',
					],
					'where' => [
						['uf_name', '=', $user_email],
						['contact_id', '!=', $contact_id],
					],
				];
      			#if ($extdebug == 1) { watchdog('php', '<pre>params_ufmatchget:' . print_r($params_ufmatchget, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result = civicrm_api4('UFMatch', 'get', $params_ufmatchget);
      			#if ($extdebug == 1) { watchdog('php', '<pre>result_ufmatchget:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($result !== false) {
					$ufmatch_contact_id = $result[0]['contact_id'];
				}

    			if ($ufmatch_contact_id) {
					if ($extdebug == 1) { watchdog('php', '<pre>c) ERROR: VIA MAIL: '.$user_email.' CONFLICTERENDE UF MATCH GEVONDEN | contactid: '.$ufmatch_contact_id.'</pre>', NULL, WATCHDOG_DEBUG); }
    				$drupalufmatchother_found = 1;
    			} else {
					if ($extdebug == 1) { watchdog('php', '<pre>c) PRIMA: VIA MAIL: '.$user_email.' GEEN UF MATCH GEVONDEN VOOR ANDER CONTACT_ID</pre>', NULL, WATCHDOG_DEBUG); }
    				$drupalufmatchother_found = 0;
    			}
    		#}

				// CHECK IF THIS DRUPAL ACCOUNT IS PRESENT WITH THE CONSTRUCTED USERNAME
				// M61: mogelijk security risk omdat ander account met dezelfde naam gekoppeld kan worden!!!
				// Misschien moet hier nog het emailadres aan een check toegevoegd worden
				$drupal_loadname	= user_load_by_name($user_name);
				$drupal_uid 		= $drupal_loadname->uid;
				#if ($extdebug == 1) { watchdog('php', '<pre>drupal_loadname: ' . print_r($drupal_loadname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($drupal_uid) {
					if ($extdebug == 1) { watchdog('php', '<pre>d) LETOP: VIA NAME DRUPAL ACCOUNT GEVONDEN: ' . print_r($user_name.' | drupalid: '.$drupal_uid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					$drupalaccount_found = 1;
				} else {
					if ($extdebug == 1) { watchdog('php', '<pre>d) LETOP: VIA NAME DRUPAL ACCOUNT NIET GEVONDEN: ' . print_r($user_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					$drupalaccount_found = 0;
				}

				if ($drupal_uid > 0 AND $drupal_uid != $ufmatch_ufid) {
					if ($extdebug == 1) { watchdog('php', '<pre>d) ERROR: VIA NAAM: '.$user_name.' CONFLICTERENDE UF MATCH GEVONDEN | drupalid: '.$drupal_uid.'</pre>', NULL, WATCHDOG_DEBUG); }
					$drupalaccount_nameconflict = 1;
				}
				if ($drupal_uid > 0 AND $drupal_uid == $ufmatch_ufid) {
					if ($extdebug == 1) { watchdog('php', '<pre>d) PRIMA: VIA NAAM: '.$user_name.' DRUPAL ACCOUNT GEVONDEN DAT OOK HOORT BIJ DRUPALID: drupalid: '.$drupal_uid.'</pre>', NULL, WATCHDOG_DEBUG); }
					$drupalaccount_nameconflict = 0;
				}

			#####################################################################################################
			if ($drupalname_found == 0 AND $drupalufmatch_found == 0 AND $drupalufmatchother_found != 1) {
			#####################################################################################################

    			if ($drupalufmatch_found == 0 AND ($ditjaardeelyes == 1 OR $ditjaardeelmss = 1)) {
    				if ($drupalaccount_found == 0) {
						#####################################################################################################
						# CREATE DRUPAL USER
						#####################################################################################################
						$new_user 	= array(
  							'name'	 => $user_name,
  							'pass'	 => $user_pwd, 		// note: do not md5 the password
  							'mail'	 => $user_email,	// M61: dit was placholder email (voor kids) omdat dubbele emails niet konden in drupal
  							'status' => 1,
  							'init'   => $user_email,
							'roles'  => array(
	   									DRUPAL_AUTHENTICATED_RID => 'authenticated user',
    									11 => 'custom role',
  									),
						);
						#if ($extdebug == 1) { watchdog('php', '<pre>Drupal user create params: ' . print_r($new_user, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						// The first parameter is sent blank so a new user is created.
						$account 	= user_save(NULL, $new_user);
						$drupal_uid = $account->uid;
						#####################################################################################################
						# LADEN VAN ZOJUIST GEMAAKTE DRUPAL ACCOUNT
						#####################################################################################################

						if ($drupal_uid) {
	  						if ($extdebug == 1) { watchdog('php', '<pre>e) PRIMA: DRUPAL USER CREATED: ' . print_r($user_name.' | drupalid: '.$drupal_uid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							// RETRIEVE UF MATCH ID
							$params_ufmatchget = [
  								'checkPermissions' => FALSE,
  								'select' => [
    								'id', 
    								'uf_id', 
    								'contact_id',
  								],
  								'where' => [
    								['uf_id', '=', $drupal_uid],
  								],
							];
      						#if ($extdebug == 1) { watchdog('php', '<pre>params_ufmatchget:' . print_r($params_ufmatchget, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
      						$result 		= civicrm_api4('UFMatch', 'get', $params_ufmatchget);
      						$ufmatch_id 	= $result[0]['id'];
      						$ufmatch_ufid 	= $result[0]['uf_id'];
		    				#if ($extdebug == 1) { watchdog('php', '<pre>result_ufmatchget:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		    				//if ($extdebug == 1) { watchdog('php', '<pre>UFMatch_id:' . print_r($ufmatch_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }				
							if ($ufmatch_id) {
								// DELETE EXISTING UF MATCH (die wijst naar lege nieuwe civicrm contact)
								$params_ufmatchdelete = [
		      						'id' => $ufmatch_id,
    	  						];
								#if ($extdebug == 1) { watchdog('php', '<pre>params_ufmatchdelete:' . print_r($params_ufmatchdelete, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
      							$result = civicrm_api3('UFMatch', 'delete', $params_ufmatchdelete);
    							if ($extdebug == 1) { watchdog('php', '<pre>UFMatch_delete:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    						}
						}
						#####################################################################################################
						# CREATE NEW UF MATCH WITH NEWLY CREATED DRUPAL USER (M61: match ook met evt verkeer dubbel gevonden uid!!!)
						#####################################################################################################
						$params_ufmatchcreate = [
							'checkPermissions' => FALSE,
  							'values' => [
								'uf_id'			=> $drupal_uid,
      							'uf_name'		=> $user_email,
      							'contact_id'	=> $contact_id,
      							'domain_id'		=> 1,
  							],
						];

						if ($extdebug == 1) { watchdog('php', '<pre>params_ufmatchcreate:' . print_r($params_ufmatchcreate, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
      					$result = civicrm_api4('UFMatch', 'create', $params_ufmatchcreate);
    					#if ($extdebug == 1) { watchdog('php', '<pre>UFMatch created: ' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

						// CHECK IF A CURRENT UF MATCH IS PRESENT
						$params_ufmatchget = [
  							'checkPermissions' => FALSE,
  							'select' => [
    							'id', 
    							'uf_id', 
    							'contact_id',
  							],
  							'where' => [
    							['contact_id', '=', $contact_id],
  							],
						];
      					#if ($extdebug == 1) { watchdog('php', '<pre>params_ufmatchget:' . print_r($params_ufmatchget, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
      					$result 		= civicrm_api4('UFMatch', 'get', $params_ufmatchget);
      					if ($result !== false) {
      						$ufmatch_id 	= $result[0]['id'];
    						$ufmatch_ufid 	= $result[0]['uf_id'];
    					}

		    			if ($ufmatch_ufid) {
	    					if ($extdebug == 1) { watchdog('php', '<pre>f) PRIMA: WEL UF MATCH GEVONDEN: ' . print_r($user_name.' | drupalid: '.$ufmatch_ufid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    						$drupalufmatch_found = 1;
    						#if ($extdebug == 1) { watchdog('php', '<pre>Current user UFMatch_id: ' . print_r($ufmatch_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							#if ($extdebug == 1) { watchdog('php', '<pre>Current user UFMatch_ufid: ' . print_r($ufmatch_ufid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    					} else {
	    					if ($extdebug == 1) { watchdog('php', '<pre>f) ERROR: GEEN UF MATCH GEVONDEN VOOR CONTACT_ID: ' . print_r($contact_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    						$drupalufmatch_found = 0;
    					}

					}

  				}
    		}
    		#####################################################################################################
			if ($drupalname_found == 1 AND $drupalufmatch_found == 1 AND $drupalufmatchother_found != 1) {
			#####################################################################################################
			# UPDATE DRUPAL ACCOUNT WITH PRIMARY EMAIL (ONVERGETELIJK.NL VOOR HOOFDLEIDING) EN UPDATE USERNAME
			#####################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>Change drupal mail for drupalid: ' . print_r($ufmatch_ufid, TRUE) . ' to ' . print_r($user_email, TRUE) . ' (PREPARING)</pre>', NULL, WATCHDOG_DEBUG); }
				if ($user_name) { $drupal_name = $user_name; }
				if ($extdebug == 1) { watchdog('php', '<pre>Change drupal name for drupalid: ' . print_r($ufmatch_ufid, TRUE) . ' to ' . print_r($drupal_name, TRUE) . ' (PREPARING)</pre>', NULL, WATCHDOG_DEBUG); }

				###########################################
				# UPDATE DRUPAL USER USING DRUPAL API
				###########################################

				// load user object
				$existingUser = user_load($ufmatch_ufid);
				#if ($extdebug == 1) { watchdog('php', '<pre>existingUser: ' . print_r($existingUser, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				$existingUser->mail = $user_email;
				// update some user property
				if (in_array($part_functie, array('hoofdleiding'))) {
					$existingUser->mail = $user_email;
				}
				if (in_array($part_functie, array('kernteamlid', 'hoofdkeuken'))) {
					$existingUser->mail = $prim_email;
				}
				// always update username (for all roles)
				$existingUser->name = $user_name;

				#if ($extdebug == 1) { watchdog('php', '<pre>existingUser->name: ' . print_r($existingUser->name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>existingUser->mail: ' . print_r($existingUser->mail, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>existingUser->uid: ' . print_r($existingUser->uid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// save existing user
				if ($existingUser->uid AND $drupalaccount_nameconflict != 1) {
					user_save((object) array('uid' => $existingUser->uid), (array) $existingUser);
					if ($extdebug == 1) { watchdog('php', '<pre>Change drupal mail for drupalid: ' . print_r($ufmatch_ufid, TRUE) . ' to ' . print_r($user_email, TRUE) . ' (UPDATED)</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>Change drupal name for drupalid: ' . print_r($ufmatch_ufid, TRUE) . ' to ' . print_r($drupal_name, TRUE) . ' (UPDATED)</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($drupalaccount_nameconflict == 1) {
					if ($extdebug == 1) { watchdog('php', '<pre>WARING: SKIPPED UPDATE WANT DRUPAL ACCOUNT GEVONDEN MET ZELFDE NAAM: ' . print_r($user_name.' | '.$drupal_uid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				###########################################

				if (in_array($part_functie, array('hoofdleiding', 'kernteamlid', 'hoofdkeuken'))) {

    				$result = civicrm_api3('Email', 'get', [
      					'sequential' 		=> 1,
      					'return' 			=> ["id", "email"],
      					'location_type_id' 	=> "Other",
      					'contact_id' 		=> $contact_id,
    				]);
    				$email_other_id 		= $result['values'][0]['id'];
    				$email_other_address 	= $result['values'][0]['email'];
   					#if ($extdebug == 1) { watchdog('php', '<pre>email_other_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   					#if ($extdebug == 1) { watchdog('php', '<pre>email_other_id:' . print_r($email_other_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   					#if ($extdebug == 1) { watchdog('php', '<pre>email_other_address:' . print_r($email_other_address, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>user_email:' . print_r($user_email, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    				if ($email_other_id AND $email_other_id > 0 AND $email_other_address == $user_email) {
						if ($extdebug == 1) { watchdog('php', '<pre>Email other al correct aangemaakt: ' . print_r($email_other_address, TRUE). '</pre>', NULL, WATCHDOG_DEBUG); }
    				}
    				if ($email_other_id AND $email_other_id > 0 AND $email_other_address != $user_email) {
	    				$result = civicrm_api3('Email', 'delete', [
    	  					'id' => $email_other_id,
    					]);
    					if ($extdebug == 1) { watchdog('php', '<pre>Email other: ' . print_r($email_other_address, TRUE) . ' verwijderd: ' . print_r($email_other_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				}
	    			if (empty($email_other_id)) {
	    				$result = civicrm_api3('Email', 'create', [
    	  					'contact_id' 		=> $contact_id,
      						'email' 			=> $user_email,
      						'location_type_id' 	=> "Other",
    					]);
						if ($extdebug == 1) { watchdog('php', '<pre>Email other: ' . print_r($user_email, TRUE) . ' aangemaakt</pre>', NULL, WATCHDOG_DEBUG); }
	    			}
    			}
			}
  		}
	}
		#####################################################################################################
		# 1.7b VOEG HOOFDLEIDING TOE AAN ACL GROEP VAN HET KAMP INDIEN HANDMATIG TOEGEVOEGD AAN KAMPSTAF ACL
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.7b VOEG HOOFDLEIDING TOE AAN ACL GROEP VAN HET KAMP INDIEN HANDMATIG TOEGEVOEGD AAN KAMPSTAF ACL ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		if (in_array($part_functie, array('hoofdleiding', 'bestuurslid'))) {

			$params_groupcontact = [
				'checkPermissions' => FALSE,
  				'select' => [
  					'row_count',
    				'id', 
    				'group_id', 
    				'group_id:name',
  				],
  				'where' => [
    				['group_id', '=', 456], 			// M61: hardcoded id of ACL group Ditjaar_kampstaf
    				['contact_id', '=', $contact_id],
    				['status', '=', 'Added'],
  				],
			];
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact);
			$group_staf = $result->count();
			if ($extdebug == 1) { watchdog('php', '<pre>Deel van group staf: '.$group_staf.'</pre>', NULL, WATCHDOG_DEBUG); }

			if ($part_welkkampleid == 'KK1')	{ $aclgroupkamp = 941;}
			if ($part_welkkampleid == 'KK2')	{ $aclgroupkamp = 942;}
			if ($part_welkkampleid == 'BK1')	{ $aclgroupkamp = 943;}
			if ($part_welkkampleid == 'BK2')	{ $aclgroupkamp = 944;}
			if ($part_welkkampleid == 'TK1')	{ $aclgroupkamp = 945;}
			if ($part_welkkampleid == 'TK2')	{ $aclgroupkamp = 946;}
			if ($part_welkkampleid == 'JK1')	{ $aclgroupkamp = 947;}
			if ($part_welkkampleid == 'JK2')	{ $aclgroupkamp = 948;}
			if ($part_welkkampleid == 'TOP')	{ $aclgroupkamp = 949;}
			if ($part_welkkampleid == 'bestuurstaken')	{ $aclgroupkamp = 455;}

			$params_groupcontact_get = [
				'checkPermissions' => FALSE,
  				'select' => [
  					'row_count',
    				'id', 
    				'group_id', 
    				'group_id:name',
  				],
  				'where' => [
    				['group_id', 	'=', $aclgroupkamp], // M61: hardcoded id of ACL group Ditjaar_kampstaf
    				['contact_id', 	'=', $contact_id],
    				['status', 		'=', 'Added'],
  				],
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_groupcontact_get:' . print_r($params_groupcontact_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_get);
			#if ($extdebug == 1) { watchdog('php', '<pre>result_groupcontact_get:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$group_kamp = $result->count();
			if ($extdebug == 1) { watchdog('php', '<pre>Deel van group kamp: ('.$part_welkkampleid.')</pre>', NULL, WATCHDOG_DEBUG); }

			#####################################################################################################
			# INDIEN IN KAMPSTAF EN LEIDING VAN DIT KAMP VOEG DAN OOK TOE AAN ACL GROEP VAN DIT KAMP
			#####################################################################################################
			if ($group_staf == 1 AND $group_kamp == 0 AND in_array($part_functie, array('hoofdleiding'))) {
				$params_groupcontact_create = [
					'checkPermissions' => FALSE,
  					'values' => [
    					'group_id' 		=> $aclgroupkamp, 
    					'contact_id' 	=> $contact_id,
    					'status' 		=> 'Added',
  					],
				];
				#if ($extdebug == 1) { watchdog('php', '<pre>params_groupcontact_create:' . print_r($params_groupcontact_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result = civicrm_api4('GroupContact', 'create', $params_groupcontact_create);
				if ($extdebug == 1) { watchdog('php', '<pre>Toegevoegd aan ACL groep hoofdleiding van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>', NULL, WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# INDIEN NIET IN KAMPSTAF EN LEIDING VAN DIT KAMP VERWIJDER DAN OOK TOE AAN ACL GROEP VAN DIT KAMP
			#####################################################################################################
			if ($group_staf == 0 AND $group_kamp == 1 AND in_array($part_functie, array('hoofdleiding'))) {
				$params_groupcontact_remove = [
					'checkPermissions' => FALSE,
  					'values' => [
    					'status' => 'Removed',
  					],
					'where' => [
						['group_id', 	'=', $aclgroupkamp], 
    					['contact_id', 	'=', $contact_id],
  					],
				];
				#if ($extdebug == 1) { watchdog('php', '<pre>params_groupcontact_remove:' . print_r($params_groupcontact_remove, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_remove);
				if ($extdebug == 1) { watchdog('php', '<pre>Verwijderd uit ACL groep hoofdleiding van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>', NULL, WATCHDOG_DEBUG); }
			}
			#####################################################################################################
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
		// M61: TODO: dit overschrijft mogelijk waarden van de participant get, met name event_id
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.8. GET EVENT INFO TO RETREIVE HOOFDLEIDING [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
    	if (($extdjpart == 1 OR $extdjcont == 1 OR $extvog == 1) AND ($diteventdeelyes == 1 OR $diteventdeelmss == 1 OR $diteventleidyes == 1)) {

   			if (in_array($part_eventtypeid, $eventypesdeel)) {			// EVENTTYPE = DEEL (afkorting kamp staat in initial_amount_label)
   				$params_var = [
	    			['event_type_id', 'IN', $eventypesdeel],
    				//['title', 'NOT LIKE', '%TEST%'],
   					['id', '=', $part_eventid],							// eventid of specific kamp
   				];
   			}
  			if (in_array($part_eventtypeid, $eventypesleid)) {			// EVENTTYPE = LEID (zoek kamp waar leiding zich voor opgaf)

				$evtkampjaar_start 	= date("01-01-$evtkampjaar");
				$evtkampjaar_einde 	= date("31-12-$evtkampjaar");
				if ($extdebug == 1) { watchdog('php', '<pre>evtkampjaar_start: ' . print_r($evtkampjaar_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>evtkampjaar_einde: ' . print_r($evtkampjaar_einde, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

  				$params_var = [
	    			['event_type_id', 'IN', $eventypesdeel],
    				//['title', 'NOT LIKE', '%TEST%'],
    				['start_date', '>=', $evtkampjaar_start],						// niet alleen in dit fiscale jaar
    				['start_date', '<=', $evtkampjaar_einde],
  					['Event_Kenmerken.Welk_kamp_kort_', '=', $part_welkkampleid],	// eventid of specific kamp
  				];
  			}
			if (in_array($part_functie, array('bestuurslid'))) {
			   	$params_var = [
	    			['event_type_id', 'IN', $eventypesleid],
    				['title', 'NOT LIKE', '%TEST%'],
   					['id', '=', $part_eventid],							// eventid of specific kamp
   				];	
			}

			#if ($extdebug == 1) { watchdog('php', '<pre>params_var:' . print_r($params_var, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

  			if ($params_var) {
				$params_event = [
					'checkPermissions' => FALSE,
  					'select' => [
	    				'id', 'event_type_id', 'event_type_id:label', 'start_date', 'end_date',
	    				'Event_Kenmerken.Type_kamp:name',
	    				'Event_Kenmerken.Welk_kamp_kort_',
						'Event_Kenmerken.Kamplocatie',
	    				'Event_Kenmerken.Kampplaats',
	    				'Taken_rollen.Hoofdleiding',
	    				'Taken_rollen.Hoofdleiding_2',
	    				'Taken_rollen.Hoofdleiding_3',
  					],
  					'where' => $params_var,
				];
				#if ($extdebug == 1) { watchdog('php', '<pre>params_event' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result = civicrm_api4('Event', 'get', $params_event);
				#if ($extdebug == 1) { watchdog('php', '<pre>params_event_results:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    			$event_id 				= $result[0]['id'];
    			$event_type_id 			= $result[0]['event_type_id'];
    			$event_type_label		= $result[0]['event_type_id:label'];
    			$event_type_name		= $result[0]['Event_Kenmerken.Type_kamp:name'];

				$event_startdate 		= $result[0]['start_date'];
				$event_enddate 			= $result[0]['end_date'];

    			$event_locatie			= $result[0]['Event_Kenmerken.Kamplocatie'];
    			$event_plaats			= $result[0]['Event_Kenmerken.Kampplaats'];

				$event_hoofdleiding1_id	= $result[0]['Taken_rollen.Hoofdleiding'];
				$event_hoofdleiding2_id	= $result[0]['Taken_rollen.Hoofdleiding_2'];
				$event_hoofdleiding3_id	= $result[0]['Taken_rollen.Hoofdleiding_3'];

				if ($extdebug == 1) { watchdog('php', '<pre>event_hoofdleiding1_id:' . print_r($event_hoofdleiding1_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>event_hoofdleiding2_id:' . print_r($event_hoofdleiding2_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>event_hoofdleiding3_id:' . print_r($event_hoofdleiding3_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				$event_kamp_kort		= $result[0]['Event_Kenmerken.Welk_kamp_kort_'];
				if ($event_type_id == 1) {	// ONLY FOR LEIDING
   					$event_kamp_kort	= $part_welkkampleid;
   				}

   				if ($extdebug == 1) { watchdog('php', '<pre>event_id:' . print_r($event_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				if ($extdebug == 1) { watchdog('php', '<pre>event_type_id:' . print_r($event_type_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				if ($extdebug == 1) { watchdog('php', '<pre>event_type_name:' . print_r($event_type_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				if ($extdebug == 1) { watchdog('php', '<pre>event_kamp_kort:' . print_r($event_kamp_kort, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			}

			if ($extdebug == 1) { watchdog('php', '<pre>event_locatie:' . print_r($event_locatie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($event_locatie) {
				$params_optionvalues = [
					'checkPermissions' => FALSE,
  					'select' => [
    					'name', 'label',
  					],
  					'where' => [
    					['value', '=', $event_locatie],	// M61 deze where moet specifieker, nu wordt in elke waarde uit elke optiongroup gezocht
    					['option_group_id', '=', 543],	// M61 deze hardcoded value is ook weer niet goed. Dit moet beter/robuuster hier.
  					],
				];
				#if ($extdebug == 1) { watchdog('php', '<pre>params_optionvalues:' . print_r($params_optionvalues, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result = civicrm_api4('OptionValue', 'get', $params_optionvalues);
				#if ($extdebug == 1) { watchdog('php', '<pre>optionValues_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$event_locatie_lang = $result[0]['label'];
   				if ($extdebug == 1) { watchdog('php', '<pre>event_locatie:' . print_r($event_locatie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				if ($extdebug == 1) { watchdog('php', '<pre>event_locatie_lang:' . print_r($event_locatie_lang, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			}

			if ($extdebug == 1) { watchdog('php', '<pre>event_plaats:' . print_r($event_plaats, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($event_plaats) {
				$params_optionvalues = [
					'checkPermissions' => FALSE,					
  					'select' => [
    					'name', 'label',
  					],
  					'where' => [
    					['value', '=', $event_plaats],	// M61 deze where moet specifieker, nu wordt in elke waarde uit elke optiongroup gezocht
  					],
				];
				#if ($extdebug == 1) { watchdog('php', '<pre>params_optionvalues:' . print_r($params_optionvalues, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result = civicrm_api4('OptionValue', 'get', $params_optionvalues);
				#if ($extdebug == 1) { watchdog('php', '<pre>optionValues_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$event_plaats_lang = $result[0]['label'];
   				if ($extdebug == 1) { watchdog('php', '<pre>event_plaats:' . print_r($event_plaats, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				if ($extdebug == 1) { watchdog('php', '<pre>event_plaats_lang:' . print_r($event_plaats_lang, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			}

			// M61: helaas 3 geet een query omdat anders de hl 1,2,3 niet in de goede volgorde terugkwamen

    		#####################################################################################################
    		// HOOFDLEIDING 1
    		#####################################################################################################
    		$params_contact = [
				'checkPermissions' => FALSE,
  				'select' => [
    				'display_name', 'first_name',
  				],
  				'where' => [
    				['id', 'IN', [$event_hoofdleiding1_id]],
  				],
			];
    		$params_phone = [
				'checkPermissions' => FALSE,
  				'select' => [
    				'phone', 'contact_id.do_not_phone',
  				],
  				'where' => [
    				['contact_id', 		 'IN', [$event_hoofdleiding1_id]],
    				['location_type_id', '=', 1],
  				],
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_contact:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result1 = civicrm_api4('Contact', 'get', $params_contact);
			$result2 = civicrm_api4('Phone', 'get', $params_phone);
			#if ($extdebug == 1) { watchdog('php', '<pre>results_contact:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 	 		if (isset($event_hoofdleiding1_id))	{
    			$event_hoofdleiding1_displname = $result1[0]['display_name'];
    			$event_hoofdleiding1_firstname = $result1[0]['first_name'];
    			$event_hoofdleiding1_phone 	   = $result2[0]['phone'];
    			$event_hoofdleiding1_dontphone = $result2[0]['contact_id.do_not_phone'];
				if ($extdebug == 1) { watchdog('php', '<pre>hoofdleiding1_displname:' . print_r($event_hoofdleiding1_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		} else {
    			$event_hoofdleiding1_displname = "";
    			$event_hoofdleiding1_firstname = "";
    			$event_hoofdleiding1_phone 	   = "";
    			$event_hoofdleiding1_dontphone = "";
    		}
    		#####################################################################################################
    		// HOOFDLEIDING 2
    		#####################################################################################################
			$params_contact = [
				'checkPermissions' => FALSE,
  				'select' => [
    				'display_name', 'first_name',
  				],
  				'where' => [
    				['id', 'IN', [$event_hoofdleiding2_id, ]],
  				],
			];
			$params_phone = [
				'checkPermissions' => FALSE,
  				'select' => [
    				'phone', 'contact_id.do_not_phone',
  				],
  				'where' => [
    				['contact_id', 		 'IN', [$event_hoofdleiding2_id]],
    				['location_type_id', '=', 1],
  				],
			];
			$result1 = civicrm_api4('Contact', 'get', $params_contact);
			$result2 = civicrm_api4('Phone', 'get', $params_phone);
			#if ($extdebug == 1) { watchdog('php', '<pre>results_contact:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 	 		if (isset($event_hoofdleiding2_id))	{
    			$event_hoofdleiding2_displname = $result1[0]['display_name'];
    			$event_hoofdleiding2_firstname = $result1[0]['first_name'];
    			$event_hoofdleiding2_phone 	   = $result2[0]['phone'];
    			$event_hoofdleiding2_dontphone = $result2[0]['contact_id.do_not_phone'];
				if ($extdebug == 1) { watchdog('php', '<pre>hoofdleiding2_displname:' . print_r($event_hoofdleiding2_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		} else {
    			$event_hoofdleiding2_displname = "";
    			$event_hoofdleiding2_firstname = "";
    			$event_hoofdleiding2_phone 	   = "";
    			$event_hoofdleiding2_dontphone = "";
    		}
    		#####################################################################################################
    		// HOOFDLEIDING 3
    		#####################################################################################################
    		$params_contact = [
				'checkPermissions' => FALSE,
  				'select' => [
    				'display_name', 'first_name',
  				],
  				'where' => [
    				['id', 'IN', [$event_hoofdleiding3_id ]],
  				],
			];
			$params_phone = [
				'checkPermissions' => FALSE,
  				'select' => [
    				'phone', 'contact_id.do_not_phone',
  				],
  				'where' => [
    				['contact_id', 		 'IN', [$event_hoofdleiding3_id]],
    				['location_type_id', '=', 1],
  				],
			];
			$result1 = civicrm_api4('Contact', 'get', $params_contact);
			$result2 = civicrm_api4('Phone', 'get', $params_phone);
			#if ($extdebug == 1) { watchdog('php', '<pre>results_contact:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 	 		if (isset($event_hoofdleiding3_id))	{
    			$event_hoofdleiding3_displname = $result1[0]['display_name'];
    			$event_hoofdleiding3_firstname = $result1[0]['first_name'];
    			$event_hoofdleiding3_phone 	   = $result2[0]['phone'];
    			$event_hoofdleiding3_dontphone = $result2[0]['contact_id.do_not_phone'];
    			if ($extdebug == 1) { watchdog('php', '<pre>hoofdleiding3_displname:' . print_r($event_hoofdleiding3_displname, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    		} else {
    			$event_hoofdleiding3_displname = "";
    			$event_hoofdleiding3_firstname = "";
    			$event_hoofdleiding3_phone 	   = "";
    			$event_hoofdleiding3_dontphone = "";
    		}

    		#####################################################################################################
    		// BEPAAL WELKKAMPLANG EN WEEKNUMMER VOOR KKBKTKJK & TOP
    		#####################################################################################################
			$welkkamplang = $event_type_label;
			$welkkampkort = $event_kamp_kort;
			if ($event_kamp_kort == 'TOP') { $welkkamplang = "$welkkamplang"." "."$evtkampjaar"; }

			# BEPAAL WELKE WEEK ADHV DE AFKORTING VAN HET KAMP
			$welkeweeknr  = substr($event_kamp_kort, -1);
			if (!in_array($welkeweeknr, array("1", "2"))) {	$welkeweeknr = "";}

			if ($extdebug == 1) { watchdog('php', '<pre>welkkampkort:'. print_r($welkkampkort, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>welkkamplang:'. print_r($welkkamplang, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>welkeweeknr:'. print_r($welkeweeknr, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    	}

		#####################################################################################################
		# 1.9 RETREIVE TAGS (DEEL & LEID)
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.9 SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if ($exttag == 1) {

			$params_tags_deel = [
  				'checkPermissions' => FALSE,
  				'select' => [
    				'tag.id', 
    				'row_count', 
    				'tag.name', 
    				'tag.description', 
    				'tag.parent_id:label',
  				],
  				'join' => [
	    			['Tag AS tag', TRUE, 'EntityTag'],
  				],
  				'where' => [
    				['id', '=', $contact_id], 
    				['tag.parent_id', '=', 37], 
    				['tag.name', 'LIKE', 'D%'],
  				],
			];

			#if ($extdebug == 1) { watchdog('php', '<pre>params_tags_deel:' . print_r($params_tags_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result_deel = civicrm_api4('Contact', 'get', $params_tags_deel);
			#if ($extdebug == 1) { watchdog('php', '<pre>params_tags_deel_result:' . print_r($result_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$tagnr_deel 		= $result_deel->count();
			$tagcv_deel_array	= $result_deel->column('tag.description');  // maakt een array met alleen de velden voor id
   			$tagcv_deel_array 	= array_unique($tagcv_deel_array);
   			asort($tagcv_deel_array);
   			$tagcv_deel 		= implode('', $tagcv_deel_array);	
			#if ($tagcv_deel == '') {$tagcv_deel = NULL; }

			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel_array:'. print_r($tagcv_deel_array, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagnr_deel:'. print_r($tagnr_deel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel:'. print_r($tagcv_deel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			$params_tags_leid = [
  				'checkPermissions' => FALSE,
  				'select' => [
    				'tag.id', 
    				'row_count', 
    				'tag.name', 
    				'tag.description', 
    				'tag.parent_id:label',
  				],
  				'join' => [
	    			['Tag AS tag', TRUE, 'EntityTag'],
  				],
  				'where' => [
    				['id', '=', $contact_id], 
    				['tag.parent_id', '=', 27], 
    				['tag.name', 'LIKE', 'L%'],
  				],
			];

			#if ($extdebug == 1) { watchdog('php', '<pre>params_tags_leid:' . print_r($params_tags_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result_leid = civicrm_api4('Contact', 'get', $params_tags_leid);
			#if ($extdebug == 1) { watchdog('php', '<pre>params_tags_deel__result:' . print_r($result_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$tagnr_leid 		= $result_leid->count();
			$tagcv_leid_array	= $result_leid->column('tag.description');  // maakt een array met alleen de velden voor id
   			$tagcv_leid_array 	= array_unique($tagcv_leid_array);
   			asort($tagcv_leid_array);
   			$tagcv_leid 		= implode('', $tagcv_leid_array);	
			#if ($tagcv_leid == '') {$tagcv_leid = NULL; }

			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid_array:'. print_r($tagcv_leid_array, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagnr_leid:'. print_r($tagnr_leid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid:'. print_r($tagcv_leid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			// CREATE TAG WIH CURRENT YEAR (IF PARTICIPATING)
			if ($diteventdeelyes) { $evttagname = 'D'.$evtkampjaarkort; }
			if ($diteventleidyes) { $evttagname = 'L'.$evtkampjaarkort; }

			if ($evttagname AND ($diteventdeelyes == 1 OR $diteventleidyes == 1)) {
				$results = civicrm_api4('EntityTag', 'create', [
					'checkPermissions' => FALSE,
  					'values' => [
    					'tag_id:name' => $evttagname,
    					'entity_id' => $contact_id,
  					],
				]);
			}

		}
		#####################################################################################################
		# 1.10 BEPAAL NIEUWE NETTO CV DEEL & LEID (& EERSTE + LAATSTE)
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.10 BEPAAL NIEUWE NETTO CV DEEL & LEID (& EERSTE + LAATSTE) [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		if ($extcv == 1) {
			######	DEEL  ###############################################################################################
			# poging om totaal aantal keren mee als leiding te berekenen obv GEANNULEERDE event registraties
			#############################################################################################################
			$part_deel = \Civi\Api4\Participant::get()
			->addSelect('contact.display_name', 'event.start_date')
  			->addWhere('event.event_type_id', 'IN', $eventypesdeel)
  			->addWhere('status_id', 'IN', [4]) // gebruik GEANNULEERDE event registraties
  			->addWhere('contact_id', '=', $contact_id)
  			->setCheckPermissions(FALSE)
  			->execute();
			foreach ($part_deel as $participant_deel) {
				$line_deel = $participant_deel['event.start_date'];
				#if ($extdebug == 1) { watchdog('php', '<pre>line_deel:' . print_r($line_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$evtcv_deel_canceled_array[] = date('Y', strtotime($line_deel));
				#if ($extdebug == 1) { watchdog('php', '<pre>participant_deel:' . print_r($participant_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
 			if ($evtcv_deel_canceled_array) 	{
 				arsort($evtcv_deel_canceled_array);
 				$evtcv_deel_canceled = implode('', $evtcv_deel_canceled_array);
 			} else {
 				$evtcv_deel_canceled_array 	= [];
 				$evtcv_deel_canceled 		= NULL;
 			}
 			#############################################################################################################
			# 1.10a bereken x mee als deelnemer obv event registraties
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.10a bereken x mee als deelnemer obv event registraties [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			######	DEEL  ###############################################################################################
   			# poging om totaal aantal keren mee als deelnemer te berekenen obv event registraties
   			#############################################################################################################
   			$part_deel = \Civi\Api4\Participant::get()
			->addSelect('contact.display_name', 'event.start_date')
  			->addWhere('event.event_type_id', 'IN', $eventypesdeel)
  			->addWhere('status_id', 'IN', [1, 2, 5, 6, 15])
  			->addWhere('contact_id', '=', $contact_id)
  			->setCheckPermissions(FALSE)
  			->execute();
			foreach ($part_deel as $participant_deel) {
				$line_deel = $participant_deel['event.start_date'];
				#if ($extdebug == 1) { watchdog('php', '<pre>line_deel:' . print_r($line_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$evtcv_deel_array[] = date('Y', strtotime($line_deel));
				#if ($extdebug == 1) { watchdog('php', '<pre>participant_deel:' . print_r($participant_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			#if ($extdebug == 1) { watchdog('php', '<pre>eventdeelarray:' . print_r($eventdeelarray, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			if ($curcv_deel_array) {
 				arsort($curcv_deel_array); // M61 not sure if this sort works 	
 				$curcv_deel 		= implode('', $curcv_deel_array);
 			} else {
 				$curcv_deel_array 	= [];
 				$curcv_deel 		= NULL;
 			}
 			if ($evtcv_deel_array) {
   				arsort($evtcv_deel_array);
				$evtcv_deel 		= implode('', $evtcv_deel_array);
 			} else {
				$evtcv_deel_array 	= [];
 				$evtcv_deel 		= NULL;
 			}
 			if ($tagcv_deel_array) {
 				arsort($tagcv_deel_array);
 				$tagcv_deel 		= implode('', $tagcv_deel_array);
 			} else {
				$tagcv_deel_array 	= [];
 				$tagcv_deel 		= NULL;
 			}

 			// MAKE SURE EVEN EMPTY ARRAYS ARE ARRAYS
			if (empty($curcv_deel_array))	{ $curcv_deel_array = []; }
			if (empty($evtcv_deel_array))	{ $evtcv_deel_array = []; }
			if (empty($tagcv_deel_array))	{ $tagcv_deel_array = []; }

			$maxcv_deel_array = array_merge($curcv_deel_array, $evtcv_deel_array, $tagcv_deel_array);

   			$maxcv_deel_array = array_unique($maxcv_deel_array);
   			asort($maxcv_deel_array);
   			$maxcv_deel = implode('', $maxcv_deel_array);	

 			#if ($extdebug == 1) { watchdog('php', '<pre>curcv_deel_0_array:' . print_r($curcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			#if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel_0_array:' . print_r($evtcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel_0_array:' . print_r($tagcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel_0_array:' . print_r($maxcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// BEPAAL OF HET HUIDIGE JAAR ER JUIST EXTRA BIJ MOET OF AF MOET
			if ($diteventdeelyes == 1) {
				array_push($maxcv_deel_array, $evtkampjaar);
				if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel (+ '.$evtkampjaar.'] want toch mee):' . print_r($maxcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// VOEG HUIDIG EVENTJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
			} elseif ($diteventdeelmss == 1 OR $diteventdeelnot == 1) {
				$maxcv_deel_array = array_diff($maxcv_deel_array, array($evtkampjaar));
				$evtcv_deel_array = array_diff($evtcv_deel_array, array($evtkampjaar)); // gevaarlijk maar terecht als er dit jaar geen deelname is. Misschien hier nog een IF voor part_status is geannulleerd
				if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel (- '.$evtkampjaar.'] want niet mee):' . print_r($maxcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// VERWIJDER HUIDIG EVENTJAAR UIT ARRAY INDIEN HET ER INZAT
			}

   			$curcv_deel_array = array_unique($curcv_deel_array);
   			asort($curcv_deel_array);
   			$curcv_deel = implode('', $curcv_deel_array);

   			$evtcv_deel_array = array_unique($evtcv_deel_array);
   			asort($evtcv_deel_array);
   			$evtcv_deel = implode('', $evtcv_deel_array);	

   			$maxcv_deel_array = array_unique($maxcv_deel_array);
   			asort($maxcv_deel_array);
   			$maxcv_deel = implode('', $maxcv_deel_array);	

   			#if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel_array:' . print_r($evtcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel:' . print_r($evtcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel_array:' . print_r($maxcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel:' . print_r($maxcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			
			// PREVENT EMPTY VALUES THAT ARE NOT ACCEPTED
			if ($evtcv_deel == '') 	{ $evtcv_deel = NULL; }
			if ($tagcv_deel == '') 	{ $tagcv_deel = NULL; }
			if ($maxcv_deel == '') 	{ $maxcv_deel = NULL; }
			if (empty($evtcv_deel)) { $evtcv_deel = NULL; }
			if (empty($tagcv_deel)) { $tagcv_deel = NULL; }
			if (empty($maxcv_deel)) { $maxcv_deel = NULL; }

			$curcv_deel_nr 	= count(array_filter($curcv_deel_array));
			$evtcv_deel_nr	= count(array_filter($evtcv_deel_array));
			$tagcv_deel_nr	= count(array_filter($tagcv_deel_array));
			$maxcv_deel_nr	= count(array_filter($maxcv_deel_array));
			$deel_nr_diff  	= abs($maxcv_deel_nr - $curcv_deel_nr);

 			#if ($extdebug == 1) { watchdog('php', '<pre>curcv_deel_array:' . print_r($curcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			#if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel_array:' . print_r($evtcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel_array:' . print_r($tagcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel_array:' . print_r($maxcv_deel_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>curcv_deel:' . print_r($curcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel:' . print_r($evtcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel_canceled:' . print_r($evtcv_deel_canceled, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel:' . print_r($tagcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel:' . print_r($maxcv_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>curcv_deel_nr:' . print_r($curcv_deel_nr, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel_nr:' . print_r($evtcv_deel_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tagcv_deel_nr:' . print_r($tagcv_deel_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel_nr:' . print_r($maxcv_deel_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>deel_nr_diff:' . print_r($deel_nr_diff, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			// M61: GEVAARLIJKE REGEL MAAR FIJN MET HET NODIGE DENKWERK
			if (($maxcv_deel_nr > $curcv_deel_nr) OR ($maxcv_deel_nr < $curcv_deel_nr AND $deel_nr_diff == 1)) {
			// VERANDER CURCV ALLEEN ALS ER BIJKOMT, VERMINDER ALLEEN ALS DE NIEUWE WAARDE SLECHTS 1 JAAR AFWIJKT
				if ($extdebug == 1) { watchdog('php', '<pre>maxcv_deel_nr:' . print_r($maxcv_deel_nr, TRUE) . ' differs with ['.$deel_nr_diff.'] from curcv_deel_nr:' . print_r($curcv_deel_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($maxcv_deel_nr == 0) { $maxcv_deel = NULL; } // gevaarlijk maar wel terecht hier ingebed
				$welkedeel 		= $maxcv_deel;
			} else {
				$welkedeel 		= $curcv_deel;
			}

			// M61: GEVAARLIJKE REGEL MAAR FIJN MET HET NODIGE DENKWERK
			if ($evtcv_deel_nr < $curcv_deel_nr AND $ditjaardeelyes == 1 AND $evtcv_deel_nr < 5 AND $curcv_deel_nr < 6 AND $maxcv_deel_nr < 7 AND $deel_nr_diff <= 1) {
			// VERANDER CURCV ALLEEN ALS ER BIJKOMT, VERMINDER ALLEEN ALS DE NIEUWE WAARDE SLECHTS 1 JAAR AFWIJKT
				if ($extdebug == 1) { watchdog('php', '<pre>evtcv_deel_nr:' . print_r($evtcv_deel_nr, TRUE) . ' differs with ['.$deel_nr_diff.'] from curcv_deel_nr:' . print_r($curcv_deel_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($maxcv_deel_nr == 0) { $maxcv_deel = NULL; } // gevaarlijk maar wel terecht hier ingebed
				$welkedeel 		= $evtcv_deel;
			}

			#asort($welkedeel);
			if ($extdebug == 1) { watchdog('php', '<pre>welkedeel:' . print_r($welkedeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			#############################################################################################################
			# 1.10b bereken x mee als kampstaf obv event registraties
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.10a bereken x mee als kampstaf obv event registraties [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			######	STAF  ###############################################################################################
			# poging om array te maken van geregistreerde events kampstaf voor leiding
			#############################################################################################################

			$params_part_staf = [
  				'select' => [
    				'event_id',
  				],
  				'where' => [
    				['event.event_type_id', 'IN', $eventypesstaf],
    				['status_id', 'IN', [1, 23, 22, 21, 24]], 
    				['event.start_date', '>', $fiscalyear_start], 
    				['contact_id', '=', $contact_id],
  				],
  				'checkPermissions' => FALSE,
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_part_staf:' . print_r($params_part_staf, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result_staf = civicrm_api4('Participant', 'get', $params_part_staf);
			#if ($extdebug == 1) { watchdog('php', '<pre>result_staf:' . print_r($result_staf, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			$stafcount 			= $result_staf->count();
			$evtcv_staf_array	= $result_staf->column('event_id');  // maakt een array met alleen de velden voor id
			asort($evtcv_staf_array); // sort by value

			#if ($extdebug == 1) { watchdog('php', '<pre>stafcount:' . print_r($stafcount, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_staf_array:' . print_r($evtcv_staf_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

 			#############################################################################################################
			# 1.10c bereken x mee als leiding obv event registraties
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.10a bereken x mee als leiding obv event registraties [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			######	LEID  ###############################################################################################
			# poging om totaal aantal keren mee als leiding te berekenen obv GEANNULEERDE event registraties
			#############################################################################################################
			$part_leid = \Civi\Api4\Participant::get()
			->addSelect('contact.display_name', 'event.start_date')
  			->addWhere('event.event_type_id', 'IN', $eventypesleid)
  			->addWhere('status_id', 'IN', [4]) // gebruik GEANNULEERDE event registraties
  			->addWhere('contact_id', '=', $contact_id)
  			->setCheckPermissions(FALSE)
  			->execute();
			foreach ($part_leid as $participant_leid) {
				$line_leid = $participant_leid['event.start_date'];
				#if ($extdebug == 1) { watchdog('php', '<pre>line_leid:' . print_r($line_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$evtcv_leid_canceled_array[] = date('Y', strtotime($line_leid));
				#if ($extdebug == 1) { watchdog('php', '<pre>participant_leid:' . print_r($participant_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
 			if ($evtcv_leid_canceled_array) 	{
 				arsort($evtcv_leid_canceled_array);
 				$evtcv_leid_canceled = implode('', $evtcv_leid_canceled_array);
 			} else {
 				$evtcv_leid_canceled_array 	= [];
 				$evtcv_leid_canceled 		= NULL;
 			}

			######	LEID  ###############################################################################################
			# poging om totaal aantal keren mee als leiding te berekenen obv event registraties
			#############################################################################################################
			$part_leid = \Civi\Api4\Participant::get()
			->addSelect('contact.display_name', 'event.start_date')
  			->addWhere('event.event_type_id', 'IN', $eventypesleid)
  			->addWhere('status_id', 'IN', [1, 2, 5, 6, 15])
  			->addWhere('contact_id', '=', $contact_id)
  			->setCheckPermissions(FALSE)
  			->execute();
			foreach ($part_leid as $participant_leid) {
				$line_leid = $participant_leid['event.start_date'];
				#if ($extdebug == 1) { watchdog('php', '<pre>line_leid:' . print_r($line_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$evtcv_leid_array[] = date('Y', strtotime($line_leid));
				#if ($extdebug == 1) { watchdog('php', '<pre>participant_leid:' . print_r($participant_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
			#if ($extdebug == 1) { watchdog('php', '<pre>eventleidarray:' . print_r($eventleidarray, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			if ($curcv_leid_array)	{
 				arsort($curcv_leid_array); // M61 not sure if this sort works 	
 				$curcv_leid 		= implode('', $curcv_leid_array);
 			} else {
 				$curcv_leid_array 	= [];
 				$curcv_leid 		= NULL;
 			}
 			if ($evtcv_leid_array) 	{
 				arsort($evtcv_leid_array);
 				$evtcv_leid 		= implode('', $evtcv_leid_array);
 			} else {
 				$evtcv_leid_array 	= [];
 				$evtcv_leid 		= NULL;
 			}
   			if ($tagcv_leid_array)	{
   				arsort($tagcv_leid_array);
   				$tagcv_leid 		= implode("", $tagcv_leid_array);
   			} else {
 				$tagcv_leid_array 	= [];
 				$tagcv_leid 		= NULL;  				
   			}
 
 			// MAKE SURE EVEN EMPTY ARRAYS ARE ARRAYS
			if (empty($curcv_leid_array))	{ $curcv_leid_array = []; }
			if (empty($evtcv_leid_array))	{ $evtcv_leid_array = []; }
			if (empty($tagcv_leid_array))	{ $tagcv_leid_array = []; }

			$maxcv_leid_array = array_merge($curcv_leid_array, $evtcv_leid_array, $tagcv_leid_array);

   			#if ($maxcv_leid_array) {
   				$maxcv_leid_array = array_unique($maxcv_leid_array);
   				asort($maxcv_leid_array);
   				$maxcv_leid = implode('', $maxcv_leid_array);
   			#} else {
   			#	$maxcv_leid = NULL;
   			#}

 			#if ($extdebug == 1) { watchdog('php', '<pre>curcv_leid_0_array:' . print_r($curcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			#if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_0_array:' . print_r($evtcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid_0_array:' . print_r($tagcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid_0_array:' . print_r($maxcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// BEPAAL OF HET HUIDIGE JAAR ER JUIST EXTRA BIJ MOET OF AF MOET
			if ($diteventleidyes == 1) {
				array_push($maxcv_leid_array, $evtkampjaar);
				if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid (+ '.$evtkampjaar.'] want toch mee):' . print_r($maxcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// VOEG HUIDIG EVENTJAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
			} elseif ($diteventleidmss == 1 OR $diteventleidnot == 1) {
				$maxcv_leid_array = array_diff($maxcv_leid_array, array($evtkampjaar));
				$evtcv_leid_array = array_diff($evtcv_leid_array, array($evtkampjaar)); // gevaarlijk maar terecht als er dit jaar geen deelname is. Misschien hier nog een IF voor part_status is geannulleerd
				if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid (- '.$evtkampjaar.'] want niet mee):' . print_r($maxcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// VERWIJDER HUIDIG EVENTJAAR UIT ARRAY INDIEN HET ER INZAT
			}

   			$curcv_leid_array = array_unique($curcv_leid_array);
   			asort($curcv_leid_array);
   			$curcv_leid = implode('', $curcv_leid_array);

   			$evtcv_leid_array = array_unique($evtcv_leid_array);
   			asort($evtcv_leid_array);
   			$evtcv_leid = implode('', $evtcv_leid_array);

			$evtcv_staf_array = array_unique($evtcv_staf_array);
			asort($evtcv_staf_array);
   			$evtcv_staf = implode('', $evtcv_staf_array);

   			$maxcv_leid_array = array_unique($maxcv_leid_array);
   			asort($maxcv_leid_array);
   			$maxcv_leid = implode('', $maxcv_leid_array);

   			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid_array:' . print_r($maxcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid:' . print_r($maxcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			
			// PREVENT EMPTY VALUES THAT ARE NOT ACCEPTED
			if ($evtcv_leid == '') 	{ $evtcv_leid = NULL; }
			if ($tagcv_leid == '') 	{ $tagcv_leid = NULL; }
			if ($maxcv_leid == '') 	{ $maxcv_leid = NULL; }
			if (empty($evtcv_leid)) { $evtcv_leid = NULL; }
			if (empty($tagcv_leid)) { $tagcv_leid = NULL; }
			if (empty($maxcv_leid)) { $maxcv_leid = NULL; }

			$curcv_leid_nr 	= count(array_filter($curcv_leid_array));
			$evtcv_leid_nr	= count(array_filter($evtcv_leid_array));
			$tagcv_leid_nr	= count(array_filter($tagcv_leid_array));
			$maxcv_leid_nr	= count(array_filter($maxcv_leid_array));
			$leid_nr_diff  	= abs($maxcv_leid_nr - $curcv_leid_nr);

 			#if ($extdebug == 1) { watchdog('php', '<pre>curcv_leid_array:' . print_r($curcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_array:' . print_r($evtcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
 			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_staf_array:' . print_r($evtcv_staf_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid_array:' . print_r($tagcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			#if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid_array:' . print_r($maxcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>curcv_leid:' . print_r($curcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid:' . print_r($evtcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_staf:' . print_r($evtcv_staf, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_canceled:' . print_r($evtcv_leid_canceled, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid:' . print_r($tagcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid:' . print_r($maxcv_leid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>curcv_leid_nr:' . print_r($curcv_leid_nr, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_nr:' . print_r($evtcv_leid_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>tagcv_leid_nr:' . print_r($tagcv_leid_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid_nr:' . print_r($maxcv_leid_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>leid_nr_diff:' . print_r($leid_nr_diff, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			// M61: GEVAARLIJKE REGEL MAAR FIJN MET HET NODIGE DENKWERK
			if (($maxcv_leid_nr > $curcv_leid_nr) OR ($maxcv_leid_nr < $curcv_leid_nr AND $leid_nr_diff == 1)) {
			// VERANDER CURCV ALLEEN ALS ER BIJKOMT, VERMINDER ALLEEN ALS DE NIEUWE WAARDE SLECHTS 1 JAAR AFWIJKT
				if ($extdebug == 1) { watchdog('php', '<pre>maxcv_leid_nr:' . print_r($maxcv_leid_nr, TRUE) . ' differs with ['.$leid_nr_diff.'] from curcv_leid_nr:' . print_r($curcv_leid_nr, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($maxcv_leid_nr == 0) { $maxcv_leid = NULL; } // gevaarlijk maar wel terecht hier ingebed
				$welkeleid 		= $maxcv_leid;
			} else {
				$welkeleid 		= $curcv_leid;
			}
			#asort($welkeleid);
			if ($extdebug == 1) { watchdog('php', '<pre>welkeleid:' . print_r($welkeleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// poging om totaal aantal keren mee als deelnemer van het Topkamp te berekenen obv event registraties
			$params_countpart_top = [
				'status_id'  	=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Partially paid"),
      			'role_id' 	 	=> "Deelnemer Topkamp",
      			'contact_id' 	=> $contact_id,
      			#'fee_amount' => ['>' => 1],
			];
			#if ($extdebug == 1) { watchdog('php', '<pre>params_countpart_deel:' . print_r($params_countpart_deel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$kereneventtop = civicrm_api3('Participant', 'getcount', $params_countpart_top);
   			if ($extdebug == 1) { watchdog('php', '<pre>kereneventtop:' . print_r($kereneventtop, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// BEPAAL EERSTE EN LAATSTE JAAR DEEL
			if (!empty($maxcv_deel_array)) {
				if ($maxcv_deel_nr > 0) {
					$eerstedeel  = min(array_filter($maxcv_deel_array));
					$laatstedeel = max(array_filter($maxcv_deel_array));
					if ($extdebug == 1) { watchdog('php', '<pre>eerstedeel:'. print_r($eerstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>laatstedeel:'. print_r($laatstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($maxcv_deel_nr == 1) {
					$eerstexdeel 			= 'eerstex';
					$part_ditjaar1stdeel 	= 'eerstex';
					if ($extdebug == 1) { watchdog('php', '<pre>eerstexdeel:'. print_r($eerstexdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				} else {
					$eerstexdeel 			= NULL;
					$part_ditjaar1stdeel 	= NULL;
				}
			}
   			// BEPAAL EERSTE EN LAATSTE JAAR LEID
			if (!empty($maxcv_leid_array)) {
				if ($maxcv_leid_nr > 0) {
					$eersteleid  = min(array_filter($maxcv_leid_array));
					$laatsteleid = max(array_filter($maxcv_leid_array));
					if ($extdebug == 1) { watchdog('php', '<pre>eersteleid:'. print_r($eersteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>laatsteleid:'. print_r($laatsteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($maxcv_leid_nr == 1) {
					$eerstexleid 			= 'eerstex';
					$part_ditjaar1stleid 	= 'eerstex';
					if ($extdebug == 1) { watchdog('php', '<pre>eerstexleid:'. print_r($eerstexleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				} else {
					$eerstexleid 			= NULL;
					$part_ditjaar1stleid 	= NULL;
				}
			}
			// BEPAAL EERSTE EN LAATSTE JAAR TOTAAL
			$totaalmee   = $maxcv_deel_nr + $maxcv_leid_nr;
			$eerstekeer  = $maxcv_deel_nr > 0 ? $eerstedeel  : $eersteleid;
			$laatstekeer = $maxcv_leid_nr > 0 ? $laatsteleid : $laatstedeel;

			#if ($extdebug == 1) { watchdog('php', '<pre>eerstedeel:'. print_r($eerstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>laatstedeel:'. print_r($laatstedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>eersteleid:'. print_r($eersteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>laatsteleid:'. print_r($laatsteleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>eerstekeer:'. print_r($eerstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>laatstekeer:'. print_r($laatstekeer, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			if ($extcv == 1) {
				$eventverschildeel	= $evtcv_deel_nr - $curcv_deel_nr;
				$eventverschilleid	= $evtcv_leid_nr - $curcv_leid_nr;
			}
			if ($exttag == 1) {
				$tagverschildeel	= $tagnr_deel - $curcv_deel_nr;
				$tagverschilleid	= $tagnr_leid - $curcv_leid_nr;
			}

			#####################################################################################################
			# 1.10b REGISTER DEZE DEELNEMER VOOR EVENT IN THE PAST EN MAAK EVT HET EVENT AAN
   			if ($extdebug == 1) { watchdog('php', '<pre>### 1.10b REGISTER DEZE DEELNEMER VOOR EVENT IN THE PAST EN MAAK EVT HET EVENT AAN [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################

			if ($curcv_leid_nr > 0) {

				#if ($extdebug == 1) { watchdog('php', '<pre>curcv_leid_array:' . print_r($curcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_array:' . print_r($evtcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				# GEBRUIK DE GEANNULEERDE KAMPJAREN ALS LEIDING OOK VOOR DEZE BEREKENING
				$evtcv_leid_totaal_array 	= array_merge($evtcv_leid_array, $evtcv_leid_canceled_array);
				$evtcv_notregistered_array 	= array_diff($curcv_leid_array, $evtcv_leid_totaal_array);

				if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_array:' . print_r($evtcv_leid_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>evtcv_leid_canceled_array:' . print_r($evtcv_leid_canceled_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>evtcv_notregistered_array:' . print_r($evtcv_notregistered_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				foreach ($evtcv_notregistered_array as $jaarleid) {
					$evt_startdate 	= date("1-1-$jaarleid");
					$evt_enddate 	= date("31-12-$jaarleid");

					$params_event = [
  						'checkPermissions' => FALSE,
  						'select' => [
    						'id', 'event_type_id', 'title', 'row_count',
  						],
  						'where' => [
    						['event_type_id', 'IN', $eventypesleid],
    						['title', 'NOT LIKE', '%TEST%'],
							['start_date', '>', $evt_startdate],
							['start_date', '<', $evt_enddate],
  						],
					];

					#if ($extdebug == 1) { watchdog('php', '<pre>params_event:' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					$result_event = civicrm_api4('Event', 'get', $params_event);
					#if ($extdebug == 1) { watchdog('php', '<pre>params_event_result:' . print_r($result_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					$old_event_id = $result_event[0]['id'];
	   				if ($extdebug == 1) { watchdog('php', '<pre>Deze persoon geregistreerd voor kampjaar '.$jaarleid.' bij eventid: '.$old_event_id.'</pre>', NULL, WATCHDOG_DEBUG); }

					$params_participant_create = [
						'checkPermissions' => FALSE,
						'values' => [
    						'contact_id' 	=> $contact_id, 
    						'event_id' 		=> $old_event_id,
							'register_date' => $evt_startdate,
    						'status_id'		=> 2,
    						'role_id' 		=> [6],	// rol = leiding
  						],
					];
					if ($extdebug == 1) { watchdog('php', '<pre>params_participant_create:' . print_r($params_participant_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($old_event_id > 0) {
						$participant_create = civicrm_api4('Participant', 'create', $params_participant_create);
					}
					#if ($extdebug == 1) { watchdog('php', '<pre>params_participant_create_result:' . print_r($participant_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
			}


			#####################################################################################################
			# 1.10c REGISTER HOOFDLEIDING VOOR FUTURE KAMPSTAF EVENS
   			if ($extdebug == 1) { watchdog('php', '<pre>### 1.10c REGISTER HOOFDLEIDING VOOR FUTURE KAMPSTAF EVENS [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################

			# REGISTREER ALLEE DE EVENT IDS DIE NOG NIET GEREGISTREERD ZIJN
			$evtcv_staf_notregistered_array = array_diff($kampidsstaf, $evtcv_staf_array);
			arsort($evtcv_staf_notregistered_array); // sort by value (reverse)

			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_staf_array:' . print_r($evtcv_staf_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>kampidsstaf:' . print_r($kampidsstaf, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>evtcv_staf_notregistered_array:' . print_r($evtcv_staf_notregistered_array, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			if ($extdebug == 1) { watchdog('php', '<pre>part_functie:' . print_r($part_functie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>group_staf:' . print_r($group_staf, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			if (in_array($part_functie, array('hoofdleiding', 'bestuurslid')) AND $group_staf == 1) { //ALLEEN INDIEN HL & IN MANUAL ACL GROUP STAF

				foreach ($evtcv_staf_notregistered_array as $kampstafeid) {
					$params_participant_create = [
						'checkPermissions' => FALSE,
						'values' => [
    						'contact_id' 	=> $contact_id, 
    						'event_id' 		=> $kampstafeid,
							'register_date' => $todaydatetime,
    						'status_id'		=> 24,	// initiele status: Nog niet bekend
    						'role_id' 		=> [6],	// rol = leiding
  						],
					];
					if ($extdebug == 1) { watchdog('php', '<pre>params_participant_create:' . print_r($params_participant_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($kampstafeid > 0) {
						$participant_create = civicrm_api4('Participant', 'create', $params_participant_create);
					}
					if ($extdebug == 1) { watchdog('php', '<pre>Deze persoon geregistreerd kampstaf eventid: '.$kampstafeid.'</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_participant_create_result:' . print_r($participant_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
			}

			#####################################################################################################
			# 1.11 BEPAAL OF DIT HET EERSTE JAAR IS DAT DEZE PERSOON ALS DEELNEMER OF LEIDING MEEGAAT
   			if ($extdebug == 1) { watchdog('php', '<pre>### 1.11 BEPAAL OF DIT HET EERSTE JAAR IS DAT DEZE PERSOON ALS DEELNEMER OF LEIDING MEEGAAT [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################

			$part_ditjaar1stdeel = CRM_Core_DAO::VALUE_SEPARATOR . $eerstexdeel . CRM_Core_DAO::VALUE_SEPARATOR;
			$part_ditjaar1stleid = CRM_Core_DAO::VALUE_SEPARATOR . $eerstexleid . CRM_Core_DAO::VALUE_SEPARATOR;

   			if ($extdebug == 1) { watchdog('php', '<pre>eerstexdeel_0:' . print_r($eerstexdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>eerstexleid_0:' . print_r($eerstexleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stdeel_0:' . print_r($part_ditjaar1stdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   			if ($extdebug == 1) { watchdog('php', '<pre>part_ditjaar1stleid_0:' . print_r($part_ditjaar1stleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			if ($maxcv_deel_nr == 1 AND $ditjaardeelyes == 1) {
	   			$part_ditjaar1stdeel 	= 'eerstex';
   				$eerstexdeel 			= 'eerstex';
   			} else {
   				$part_ditjaar1stdeel 	= NULL;
	   			$eerstexdeel 			= NULL;
   			}
   			if ($maxcv_leid_nr == 1 AND $ditjaarleidyes == 1) {
	   			$part_ditjaar1stleid 	= 'eerstex';
	   			$eerstexleid 			= 'eerstex';
	   		} else {
		   		$part_ditjaar1stleid 	= NULL;
		   		$eerstexleid 			= NULL;
		   	}

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

			$params_contact['external_identifier']	= $drupal_uid;
    		$params_contact['job_title']			= $drupal_name;
    		$params_contact['custom_647']			= $datum_belangstelling;
    		$params_contact['custom_474']			= $datum_drijf_ingevuld;
			$params_contact['custom_1010']			= "&euro; 0,00"; // mss niet gebruiken hier omdat er anders een update loop kan ontstaan

			if ($belangstelling_array) {
    			$params_contact['custom_1172']	= $belangstelling_array;
    		}

    		if ($ditjaardeelnot == 1 OR $ditjaarleidnot == 1) {
      			$params_contact['custom_993']	= "";
      			$params_contact['custom_994']	= "";
      			$params_contact['custom_995']	= "";
      		}
			if ($ditjaardeelnot == 1 OR $ditjaarleidnot == 1) {
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
    			$params_contact['custom_1157'] 	= "";
	   			$params_contact['custom_1155'] 	= "";
    			$params_contact['custom_1156'] 	= "";
	   			$params_contact['custom_1163'] 	= "";
    			$params_contact['custom_1164'] 	= "";
      		}

    		if ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1) { // alles behalve leid mss
    			$params_contact['custom_995'] 	= $part_eventid;		// M61: in dit geval eventid van leiding event
    		}

    		if ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1) { // alles behalve leid mss
    			$params_contact['custom_993'] 	= $event_type_name;
    			$params_contact['custom_994'] 	= $event_type_id;
      			$params_contact['custom_1030']	= $part_id;				// participant id (zou ook entity ID kunnen zijn, maar iig niet contact_id)
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
    			$params_contact['custom_1325'] 	= $event_hoofdleiding1_phone;
    			$params_contact['custom_1326'] 	= $event_hoofdleiding2_phone;
    			$params_contact['custom_1327'] 	= $event_hoofdleiding3_phone;
    			#$params_contact['custom_996'] 	= $eerstexdeel;
    			#$params_contact['custom_997'] 	= $eerstexleid;
			}
    		if ($ditjaardeelyes == 1 OR $ditjaardeelmss == 1) {
    			$params_contact['custom_995'] 	= $event_id;			// M61: in dit geval eventid van deelnemer event
			    $params_contact['custom_1051']  = $part_groepklas;
  			    $params_contact['custom_1208']  = $part_groepsvoorkeur;
  			    $params_contact['custom_1218']  = $part_tijdslotbrengen;
  			    $params_contact['custom_1219']  = $part_tijdslothalen;
  			    $params_contact['custom_1229']  = $part_tijdslothalen_aankomst;
  			    $params_contact['custom_1221']  = $part_groepsletter;
  			    $params_contact['custom_1228']  = $part_groepskleur;
  			    $params_contact['custom_1222']  = $part_slaapzaal;
			}
		}
		#####################################################################################################
		# 1.13 UPDATE PARAMS_PARTICIPANT MET EVENT INFO
    	if ($extdjpart == 1 AND ($diteventdeelyes == 1 OR $diteventleidyes == 1 OR $diteventdeelmss == 1)) {
		#####################################################################################################
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.13 UPDATE PARAMS_PARTICIPANT MET EVENT INFO [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

    		$params_participant2 = [
    			#'reload' 			=> TRUE,
  				#'checkPermissions' 	=> FALSE,
	  			'where' => [
    				['id', 			'=', $part_id],
    				#['event_id',	'=', $part_eventid],
    				#['contact_id',	'=', $contact_id],
  				],
  				'values' => [
    				'PART.PART_kamptype_naam' 	=> $event_type_name,
					'PART.PART_kamptype_id'		=> $event_type_id,
					'PART.PART_welkkamp_id'		=> $event_id,

    				'PART.PART_kampfunctie'		=> $part_functie, 
					'PART.PART_welkkamp_lang' 	=> $welkkamplang, 
					'PART.PART_welkkamp_kort'	=> $welkkampkort,
					'PART.PART_welk_weeknr'		=> $welkeweeknr,
  				],
			];

    		$params_participant = [
   				'id'           => $part_id,
				'event_id'	   => $part_eventid,
   				'contact_id'   => $contact_id,

      			#'custom_992'   => $event_type_name,
      			#'custom_961'   => $event_type_id,
      			#'custom_962'   => $event_id,

   				#'custom_969'   => $part_functie,
   				#'custom_949'   => $welkkamplang,
      			#'custom_950'   => $welkkampkort,
	    		#'custom_1050'  => $welkeweeknr,
    		];

    		if ($event_hoofdleiding1_displname) {
    			#$params_participant['custom_944']	= $event_hoofdleiding1_displname;
    			$params_participant2['values']['PART.PART_hoofd_1_dn']	= $event_hoofdleiding1_displname;
    			#$params_participant['custom_953']	= $event_hoofdleiding1_firstname;
    			$params_participant2['values']['PART.PART_hoofd_1_fn']	= $event_hoofdleiding1_firstname;
    		}
    		if ($event_hoofdleiding2_displname) {
    			#$params_participant['custom_945']	= $event_hoofdleiding2_displname;
    			$params_participant2['values']['PART.PART_hoofd_2_dn']	= $event_hoofdleiding2_displname;
    			#$params_participant['custom_954']	= $event_hoofdleiding2_firstname;
    			$params_participant2['values']['PART.PART_hoofd_2_fn']	= $event_hoofdleiding2_firstname;
    		}
    		if ($event_hoofdleiding3_displname) {
    			#$params_participant['custom_1046']	= $event_hoofdleiding3_displname;
    			$params_participant2['values']['PART.PART_hoofd_3_dn']	= $event_hoofdleiding3_displname;
    			#$params_participant['custom_1047']	= $event_hoofdleiding3_firstname;
    			$params_participant2['values']['PART.PART_hoofd_3_fn']	= $event_hoofdleiding3_firstname;
    		}
    		if ($extcv == 1 AND $part_ditjaar1stdeel) {
    			if ($part_ditjaar1stdeel != 1) {
    			//	$params_participant['custom_592']	= $part_ditjaar1stdeel;
    			}
    			// M61 TODO hier een check dat niet de waarde '1' kan, alleen tekst, want anders komt er een error
    			// M61 URGENT FIX NEEDED: is sometimes 1
    		}
    		if ($extcv == 1 AND $part_ditjaar1stleid) {
    			if ($part_ditjaar1stleid != 1) {
    			//	$params_participant['custom_649']	= $part_ditjaar1stleid;
    			}
    			// M61 URGENT FIX NEEDED: is sometimes 1
    		}
    		#if ($leeftijdevenement > 0) {
    			#$params_participant['custom_1150']	= $leeftijdevenement;
    		#}
    		#if ($extdebug == 1) { watchdog('php', '<pre>params_participant_dusver:' . print_r($params_participant, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#####################################################################################################
		# 1.14 UPDATE params_contact MET CV INFO
    	#if ($extcv == 1 AND ($ditjaardeelyes == 1 OR $ditjaarleidyes == 1 OR $ditjaardeelmss == 1 OR $ditjaarleidmss == 1)) { // M61: hier van maken dat het ook op voorgaande jaren werkt
   		if ($extcv == 1) {
		#####################################################################################################
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.14 UPDATE PARAMS_CONTACT MET STATISTIEKEN[groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

			$params_contact['custom_846']   = $eerstekeer;
			$params_contact['custom_847']   = $laatstekeer;
			$params_contact['custom_458']	= $totaalmee;
			$params_contact['custom_1001']  = $evtcv_deel_nr;
			$params_contact['custom_1002']  = $evtcv_leid_nr;
			$params_contact['custom_1110']  = $eventverschildeel;
			$params_contact['custom_1112']	= $eventverschilleid;

			$params_contact['custom_1209']	= $evtcv_deel;
			$params_contact['custom_1210']	= $evtcv_leid;

			$params_contact['custom_1039']	= $part_gegevensgechecked;

    		if ($exttag == 1) {
   				$params_contact['custom_856']   = $tagcv_deel;
   				$params_contact['custom_848']   = $tagnr_deel;
  				#$params_contact['custom_857']   = $tagcv_leid; // M61 URGENT TODO FIX THIS (BECOMES EMPTY SOMETIMES)
   				$params_contact['custom_849']   = $tagnr_leid;
      			$params_contact['custom_850']   = $tagverschildeel;
      			$params_contact['custom_851']	= $tagverschilleid;
    		}

    		$params_contact['custom_376']	= $welkedeel;
			$params_contact['custom_382']	= $maxcv_deel_nr;
    		$params_contact['custom_842']	= $eerstedeel;
    		$params_contact['custom_843']	= $laatstedeel;
    		$params_contact['custom_1027']	= $kereneventtop;
    		$params_contact['custom_73']	= $welkeleid;
    		$params_contact['custom_74']	= $maxcv_leid_nr;
    		$params_contact['custom_844']	= $eersteleid;
    		$params_contact['custom_845']	= $laatsteleid;

			#if ($extdebug == 1) { watchdog('php', '<pre>params_contact_dusver:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
      				'start_date' 			=> ['>=' => $fiscalyear_start],
    			];
    			try{
					#if ($extdebug == 1) { watchdog('php', '<pre>params_get_related_hoofdleiding:' . print_r($params_get_related_hoofdleiding, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				$result = civicrm_api3('Relationship', 'get', $params_get_related_hoofdleiding);
    				#if ($extdebug == 1) { watchdog('php', '<pre>params_get_related_hoofdleiding_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    				if ($result['count'] == 1) {
						$related_hoofdleiding_id	= $result['values'][0]['contact_id_b'];
						$related_hoofdleiding_relid	= $result['values'][0]['id'];
					} else {
						$related_hoofdleiding_id	= NULL;
						$related_hoofdleiding_relid	= NULL;					
					}
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
	    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.16 CREATE RELATED HOOFDLEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				$params_create_related_hoofdleiding = [
					'id' 					=> $related_hoofdleiding_relid,
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
    					$result = civicrm_api3('Relationship', 'create', $params_create_related_hoofdleiding);
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
   		if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] [entityID: '.$entityID.'] '.$part_functie.': '.$displayname.'] ***</pre>', null, WATCHDOG_DEBUG); }
		###############################################################################################################
		### 2. EXTENSION VOG/REF
		###############################################################################################################

   		if ($extvog == 1 AND $ditjaarleidyes == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {
   		// MEEDITJAAR ALS LEIDING + PART LEID + PART LEID REF + PART LEID REFERENTIE
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
		if ($extdebug == 1) { watchdog('php', '<pre>.part_referentie_ingevuld:'. print_r($part_reffeedback, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			###############################################################################################################
			// 2.1 BEPAAL OF DE VOG NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN VOG MOET WORDEN AANGEVRAAGD 
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.1 BEPAAL OF DE VOG NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN VOG MOET WORDEN AANGEVRAAGD</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
			#if ($extdebug == 1) { watchdog('php', '<pre>strtotime(vogrecent):' . print_r(strtotime($vogrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    		if ($vogrecent) {
    			if ($vogrecent AND strtotime($vogrecent) < strtotime($fiscalyear_start) AND strtotime($vogrecent) >= strtotime($grensvognoggoed)) { // Datum VOG in previous 2 fiscal years
    				$vogdatethisyear = 0;
    				$vognodig = 'noggoed';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] VAKT BINNEN AFGELOPEN 3 FISCALE JAREN (INCL.HUIDIGE) ['.$grensvognoggoed.'] DUS: ['.$vognodig.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}

    			if ($vogrecent AND strtotime($vogrecent) >= strtotime($fiscalyear_start))	{	// Datum VOG binnen het huidige fiscal year
    				$vogdatethisyear = 0;
    				if ($curcv_leid_nr > 	1) { $vognodig = 'opnieuw'; } else { $vognodig = 'eerstex'; }
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] VALT BINNEN HET HUIDIGE FISCAL YEAR ['.$fiscalyear_start.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}

    			if ($vogrecent AND strtotime($vogrecent) >= strtotime($grensvognoggoed) AND strtotime($vogrecent) < strtotime($grensvognoggoedplusone))	{ 	// Datum VOG binnen het eerste fiscal year na grensnoggoed
    				$vognodignextyear = 1;
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] MAAKT VOG VOLGEND JAAR NODIG ['.$grensvognoggoedplusone.']</pre>', NULL, WATCHDOG_DEBUG); }
    			}
    			if ($vogrecent AND strtotime($vogrecent) < strtotime($grensvognoggoed))		{ // Datum VOG ouder dan 3 fiscale jaren
    				$vogdatethisyear = 0;
    				$vognodig = 'opnieuw';
    				if ($extdebug == 1) { watchdog('php', '<pre>DATUM VOG ['.$vogrecent.'] IS OUDER DAN START VAN AFGELOPEN 3 FISCALE JAREN (INCL.HUIDIGE)['.$grensvognoggoed.'] DUS: ['.$vognodig.']</pre>', NULL, WATCHDOG_DEBUG); }
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
    		if ($vognodignextyear == 1) 			{ $refnodig = 'noggoed'; }
    		if ($part_functie == 'hoofdleiding')	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($part_functie == 'bestuurslid')	 	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($ditjaarleidnot == 1)				{ $vognodig = ''; $refnodig = ''; }

    		if ($extdebug == 1) { watchdog('php', '<pre>vognodignextyear:'. print_r($vognodignextyear, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($extdebug == 1) { watchdog('php', '<pre>vognodig_1:'. print_r($vognodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>refnodig_1:'. print_r($refnodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			###############################################################################################################
			### 2.2 BEPAAL OF DE REF NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN REF MOET WORDEN AANGEVRAAGD 
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.2 BEPAAL OF DE REF NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN REF MOET WORDEN AANGEVRAAGD</pre>', NULL, WATCHDOG_DEBUG); }
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
    				if ($curcv_leid_nr > 1) { $refnodig = 'opnieuw'; } else { $refnodig = 'eerstex'; }
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
			if ($vognodignextyear == 1) 			{ $refnodig = 'noggoed'; }
    		if ($part_functie == 'hoofdleiding')	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($part_functie == 'bestuurslid')	 	{ $vognodig = 'elkjaar'; $refnodig = 'elkjaar'; }
    		if ($ditjaarleidnot == 1)				{ $vognodig = ''; $refnodig = ''; }
    		
    		// M61: deze hier alleen tijdelijk om REF gelijk te trekken met VOG: dus pas bij VOG opnieuw ook REF opnieuw (ook indien nog nooit ingevuld)
    		#if ($vognodig == 'noggoed')	 			{ $refnodig = 'noggoed'; }
    		#if ($vognodig == 'opnieuw')	 			{ $refnodig = 'opnieuw'; }

    		if ($extdebug == 1) { watchdog('php', '<pre>refnodig_1:'. print_r($refnodig, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			#####################################################################################################
   			if (in_array($groupID, array("165", "213")) AND $ditjaarleidyes == 1) {
   			// PART DEEL + PART LEID + PART REF EN INDIEN DIT JAAR MEE ALS LEIDING
	    		if ($extdebug == 1) { watchdog('php', '<pre>### 2.3. RETRIEVE REFERENTIE INGEVULD VANUIT CUSTOM FIELD AAN REFERENTIE ACTIVITEIT [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
  			}
			###############################################################################################################
			# RETREIVE RELATED REFERENTIE EN ASSIGN DIE NAAR PARTICIPANT RECORD
			###############################################################################################################

			$params_ref = [
  				'checkPermissions' => FALSE,
  				'select' => [
  					'id',
    				'contact_id_b',
    				'contact_b.display_name',
    				'contact_b.first_name',
    				'contact_b.middle_name',
    				'contact_b.last_name',
    				'contact_b.gender_id',
    				'start_date', 
    				'end_date', 
					'ref_aanvrager.Naam_aanvrager',
					'ref_aanvrager.referentie_relatie', 
					'ref_aanvrager.referentie_motivatie', 
					'ref_aanvrager.Kamp_aanvrager',
    				'ref_aanvrager.datum_verzoek', 
    				'ref_feedback.datum_feedback',
    				'ref_feedback.Bezwaar',
				],
  				'where' => [
    				['relationship_type_id', '=', 16], 
    				['contact_id_a', '=', $contact_id], 
    				['end_date', '=', 'this.fiscal_year'],
  				],
			];

			#if ($extdebug == 1) { watchdog('php', '<pre>params_ref_get:' . print_r($params_ref, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			$result_ref = civicrm_api4('Relationship', 'get', $params_ref);
			#if ($extdebug == 1) { watchdog('php', '<pre>params_ref_get:' . print_r($result_ref, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			$refcount 	= $result_ref->count();
			if ($refcount > 0) {
				$referentie_relid		= $result_ref[0]['id'];
				$referentie_cid			= $result_ref[0]['contact_id_b'];
				$referentie_name		= $result_ref[0]['contact_b.display_name'];
				$referentie_fn			= $result_ref[0]['contact_b.first_name'];
				$referentie_mn			= $result_ref[0]['contact_b.middle_name'];
				$referentie_ln			= $result_ref[0]['contact_b.last_name'];
				$referentie_relatie		= trim($result_ref[0]['ref_aanvrager.referentie_relatie']);
				$referentie_motivatie	= trim($result_ref[0]['ref_aanvrager.referentie_motivatie']);
				$referentie_geslacht	= trim($result_ref[0]['contact_b.gender_id']);
				$referentie_start		= $result_ref[0]['start_date'];
				$referentie_end			= $result_ref[0]['end_date'];
				$referentie_cid			= $result_ref[0]['contact_id_b'];
				$referentie_gevraagd	= $result_ref[0]['ref_aanvrager.datum_verzoek'];
				$referentie_feedback	= $result_ref[0]['ref_feedback.datum_feedback'];
				$referentie_bezwaar		= $result_ref[0]['ref_feedback.Bezwaar'];


				// M61 BEDOELD OM TIJDELIJK INFO VAN REL NAAR PART TE KRIJGEN INDIEN NODIG
				if ($referentie_gevraagd AND empty($refverzocht)) {
					#$refverzocht = $referentie_gevraagd;
					if ($extdebug == 1) { watchdog('php', '<pre>refverzocht_uit_rel:' . print_r($refverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($referentie_feedback AND empty($refingevuld)) {
					#$refingevuld = $referentie_feedback;
					if ($extdebug == 1) { watchdog('php', '<pre>refingevuld_uit_rel:' . print_r($refingevuld, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}

				$part_refnaam 			= $referentie_name; 	// dit kan handiger
			
				#if ($extdebug == 1) { watchdog('php', '<pre>ditjaarreferentie:' . print_r($result_ref, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>ditjaarreferentie:' . print_r($result_ref[0], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>referentie_relid:' . print_r($referentie_relid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_cid:' . print_r($referentie_cid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_name:' . print_r($referentie_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>referentie_fn:' . print_r($referentie_fn, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_mn:' . print_r($referentie_mn, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_ln:' . print_r($referentie_ln, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>referentie_relatie:' . print_r($referentie_relatie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_motivatie:' . print_r($referentie_motivatie, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>referentie_geslacht:' . print_r($referentie_geslacht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>referentie_start:' . print_r($referentie_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_end:' . print_r($referentie_end, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_gevraagd:' . print_r($referentie_gevraagd, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_feedback:' . print_r($referentie_feedback, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>referentie_bezwaar:' . print_r($referentie_bezwaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:' . print_r($part_eventid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				$params_ref = [
  					'checkPermissions' => FALSE,
	  				'where' => [
    					['id', 				'=', $referentie_relid],
    					['contact_id_b',	'=', $referentie_cid],
  					],
  					'values' => [
						'is_active' 						=> TRUE,
    					'end_date' 							=> $fiscalyear_end,
						'ref_aanvrager.Kamp_ID'				=> $part_eventid,
						'ref_aanvrager.Kamp_aanvrager'		=> $welkkamplang,
    					'ref_aanvrager.Naam_aanvrager'		=> $displayname, 
						'ref_aanvrager.Functie_aanvrager' 	=> $part_functie, 
						'ref_feedback.Naam_referentie'		=> $referentie_name,
  					],
				];

				if ($extdebug == 1) { watchdog('php', '<pre>params_ref_upd:' . print_r($params_ref, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				$result_ref = civicrm_api4('Relationship', 'update', $params_ref);
				#if ($extdebug == 1) { watchdog('php', '<pre>params_ref_upd:' . print_r($result_ref, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

/*
				$params_participant['custom_1305'] 	= $referentie_relid;
				$params_participant['custom_1303']	= $referentie_name;
				$params_participant['custom_980']	= $referentie_fn;
				$params_participant['custom_981']	= $referentie_mn;
				$params_participant['custom_982']	= $referentie_ln;
*/
				#$params_participant['custom_984']	= $referentie_functie;
				#$params_participant['custom_1267']	= $referentie_relatie;
				#$params_participant['custom_1244']	= $referentie_geslacht;

				#$params_participant['custom_1295']	= $referentie_gevraagd;
				#$params_participant['custom_1296']	= $referentie_feedback;
				#$params_participant['custom_1246']	= $referentie_bezwaar;
			}

			###############################################################################################################
			// 2.4a CHECK OF ER EEN VOG VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.4a CHECK OF ER EEN VOG VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR</pre>', NULL, WATCHDOG_DEBUG); }
  			$vogverzoekditjaar = 0;
			if (strtotime($part_vogverzocht) >= strtotime($fiscalyear_start) AND strtotime($part_vogverzocht) <= strtotime($fiscalyear_end)) {
				// alleen indien vogverzocht binnen huidige fiscale jaar valt
				$vogverzoekditjaar = 1;
			}
			if ($extdebug == 1) { watchdog('php', '<pre>vogverzoekditjaar:' . print_r($vogverzoekditjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
			// 2.4b CHECK OF ER EEN REF VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.4b CHECK OF ER EEN REF VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR</pre>', NULL, WATCHDOG_DEBUG); }
  			$refverzoekditjaar = 0;
			if (strtotime($part_refgevraagd) >= strtotime($fiscalyear_start) AND strtotime($part_refgevraagd) <= strtotime($fiscalyear_end)) {
				// alleen indien vogverzocht binnen huidige fiscale jaar valt
				$refverzoekditjaar = 1;
			}
			if ($extdebug == 1) { watchdog('php', '<pre>refverzoekditjaar:' . print_r($refverzoekditjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
			// 2.5 WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ (indien er een part_vog_datum is)
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.5 WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ (indien er een part_vog_datum is</pre>', NULL, WATCHDOG_DEBUG); }
    		if ($extvog == 1) {
				$params_contact['custom_998']		= $vognodig;	 # TAB NODIG VOG
   				if (($part_vogdatum AND !$vogrecent) OR strtotime($part_vogdatum) > strtotime($vogrecent)) {
   				// alleen overschrijven als er een nieuwere VOG datum is OF $vogrecent leeg is
					$params_contact['custom_56']	= $part_vogdatum;
					$params_contact['custom_68']	= $part_vogkenmerk;
				}
				$params_contact['custom_1019']		= $refnodig;	 # TAB NODIG REF
   				if (($part_reffeedback AND !$refrecent) OR strtotime($part_reffeedback) >= strtotime($fiscalyear_start)) {
   				// alleen overschrijven als er een nieuwere REF datum is OF $refrecent leeg is
					$params_contact['custom_1004']	= $part_reffeedback;
					$params_contact['custom_1003']	= $part_refnaam;
				}
				if (($referentie_feedback AND !$refrecent) OR strtotime($referentie_feedback) >= strtotime($fiscalyear_start)) {
   				// alleen overschrijven als er een nieuwere REF datum is OF $refrecent leeg is
					$params_contact['custom_1004']	= $referentie_feedback;
					$params_contact['custom_1003']	= $part_refnaam;
				}
   			}
			###############################################################################################################
			// 2.6 WERK DE GEGEVENS IN PART LEID VOG BIJ
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.6 WERK DE GEGEVENS IN PART LEID VOG BIJ</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
    		if ($extvog == 1 AND ($ditjaarleidmss == 1 OR $ditjaarleidyes == 1)) {

    			#$params_participant['id']  		= $part_id;
    			#$params_participant['event_id']	= $part_eventid;
    			#$params_participant['contact_id']  = $contact_id;

    			#$params_participant['custom_990']  = $vognodig;	#PART NODIG VOG
				#$params_participant['custom_1018'] = $refnodig;	#PART NODIG REF

				$params_participant2['values']['PART.PART_vognodig:name']	= $vognodig;	#PART NODIG VOG
				$params_participant2['values']['PART.PART_refnodig:name']	= $refnodig;	#PART NODIG REF

				if ($extdebug == 1) { watchdog('php', '<pre>.part_vogontvangst:'. print_r($part_vogontvangst, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>.part_vogdatum:'. print_r($part_vogdatum, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

				// WERK DATUM ONTVANGST BIJ INDIEN (HANDMATIG) ALLEEN DATUM VOG WAS INGEVULD
				if ($part_vogdatum AND empty($part_vogontvangst)) {
					#$params_participant['custom_601']	= $part_vogdatum;
					$params_participant2['values']['PART_LEID_VOG.Datum_nieuwe_VOG']	= $part_vogdatum;
					if ($extdebug == 1) { watchdog('php', '<pre>VOGONTVANGST LEEG MAAR VOGDATUM INGEVULD:'. print_r($part_vogdatum, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// WERK DATUM VOG BIJ INDIEN (HANDMATIG) ALLEEN DATUM ONTVANGST WAS INGEVULD
				if (empty($part_vogdatum) AND $part_vogontvangst) {
					#$params_participant['custom_603']	= $part_vogingediend;
					$params_participant2['values']['PART_LEID_VOG.Datum_aanvraag']		= $part_vogingediend;
					if ($extdebug == 1) { watchdog('php', '<pre>VOGDATUM LEEG MAAR VOGONTVANGST INGEVULD:'. print_r($part_vogontvangst, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
   			}
			###############################################################################################################
			// 2.7 WERK DE GEGEVENS IN PART LEID REF BIJ
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.7 WERK DE GEGEVENS IN PART LEID REF BIJ</pre>', NULL, WATCHDOG_DEBUG); }
			###############################################################################################################
				// WERK DATUM REF ONTVANGST BIJ INDIEN (HANDMATIG) ALLEEN DATUM VOG WAS INGEVULD
				if (empty($part_refpersoon) AND $todaydatetime) {
					#$params_participant['custom_1301']  = $todaydatetime;
					$params_participant2['values']['PART_LEID_REF.REF_persoon']		= $todaydatetime;
					if ($extdebug == 1) { watchdog('php', '<pre>REFPERSOON DATUM LEEG NAAR WEL DATUM ACTIVITEIT:'. print_r($todatdatetime, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// WERK DATUM REF ONTVANGST BIJ INDIEN (HANDMATIG) ALLEEN DATUM VOG WAS INGEVULD
				if (empty($part_refgevraagd) AND $referentie_gevraagd) {
					#$params_participant['custom_1295']  = $referentie_gevraagd;
					$params_participant2['values']['PART_LEID_REF.REF_gevraagd']	= $referentie_gevraagd;
					if ($extdebug == 1) { watchdog('php', '<pre>REFVERZOCHT LEEG MAAR REL_GEVRAAGD INGEVULD:'. print_r($part_refgevraagd, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// WERK DATUM REF BIJ INDIEN (HANDMATIG) ALLEEN DATUM ONTVANGST WAS INGEVULD
				if (empty($part_reffeedback) AND $referentie_feedback) {
					#$params_participant['custom_1296']  = $referentie_feedback;
					$params_participant2['values']['PART_LEID_REF.REF_feedback']	= $referentie_feedback;
					if ($extdebug == 1) { watchdog('php', '<pre>REFINGEVULD LEEG MAAR REL_FEEDBACK INGEVULD:'. print_r($part_reffeedback, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// WERK DATUM REF BIJ INDIEN (HANDMATIG) ALLEEN DATUM ONTVANGST WAS INGEVULD
				if (empty($part_refbezwaar) AND $referentie_bezwaar) {
					$params_participant2['values']['PART_LEID_REF.REF_bezwaar']						= $referentie_bezwaar;
					if ($extdebug == 1) { watchdog('php', '<pre>REFBEZWAAR LEEG MAAR REL_BEZWAAR INGEVULD:'. print_r($part_refbezwaar, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
				}			
					$params_participant2['values']['PART_LEID_REFERENTIE.REFERENTIE_naam']			= $referentie_name;
					$params_participant2['values']['PART_LEID_REFERENTIE.REFERENTIE_voornaam']		= $referentie_fn;
					$params_participant2['values']['PART_LEID_REFERENTIE.REFERENTIE_tussenvoegsel']	= $referentie_mn;
					$params_participant2['values']['PART_LEID_REFERENTIE.REFERENTIE_achternaam']	= $referentie_ln;
					$params_participant2['values']['PART_LEID_REFERENTIE.REFERENTIE_relatie']		= $referentie_relatie;
					$params_participant2['values']['PART_LEID_REFERENTIE.REFERENTIE_motivatie']		= $referentie_motivatie;
					$params_participant2['values']['PART_LEID_REFERENTIE.Relatie_ID']				= $referentie_relid;
				
   			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {	// PART LEID & TAB INTAKE EN INDIEN DIT JAAR MEE ALS LEIDING
			###############################################################################################################
			### 3 GET ACTIVITIES MBT. VOG & REF
			###############################################################################################################
   				if ($extdebug == 1) { watchdog('php', '<pre>### 3. VOG & REF ACTIVITIES [GET] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
   				// ************************************************************************************************************
				// 3.1 GET ACTIVITIES 'REF PERSOON'
				// ************************************************************************************************************
   				$params_activity_refpersoon_get = [		// zoek activities 'REF persoon'
  					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id' 		=> 139, // REF_verzoek
  					'activity_date_time' 	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_get:' . print_r($params_activity_vogverzoek_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_refpersoon = civicrm_api3('Activity', 'get', $params_activity_refpersoon_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_get_result:' . print_r($result_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_verzoek:' . print_r($result_verzoek['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_refpersoon['count'] == 1) {
  					$refpersoon_activity_id		= $result_refpersoon['values'][0]['id'];
  					$refpersoon_activity_status	= $result_refpersoon['values'][0]['status_id'];
  					$refpersoon_activity_datum	= $result_refpersoon['values'][0]['activity_date_time'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_id:' . print_r($refpersoon_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_status:' . print_r($refpersoon_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_datum:' . print_r($refpersoon_activity_datum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$refpersoon_activity_id		= NULL;
  					$refpersoon_activity_status	= NULL;
  					$refpersoon_activity_datum	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_id: No Activity Found (REF persoon: ' . print_r($part_refpersoon, TRUE) . ')</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 3.2 GET ACTIVITIES 'REF VERZOEK'
				// ************************************************************************************************************
   				$params_activity_refverzoek_get = [		// zoek activities 'REF verzoek'
  					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id' 		=> 117, // REF_verzoek
  					'activity_date_time' 	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_get:' . print_r($params_activity_vogverzoek_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_refverzoek = civicrm_api3('Activity', 'get', $params_activity_refverzoek_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_get_result:' . print_r($result_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_verzoek:' . print_r($result_verzoek['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_refverzoek['count'] == 1) {
  					$refverzoek_activity_id		= $result_refverzoek['values'][0]['id'];
  					$refverzoek_activity_status	= $result_refverzoek['values'][0]['status_id'];
  					$refverzoek_activity_datum	= $result_refverzoek['values'][0]['activity_date_time'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>refverzoek_activity_id:' . print_r($refverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>refverzoek_activity_status:' . print_r($refverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$refverzoek_activity_id		= NULL;
  					$refverzoek_activity_status	= NULL;
					$refverzoek_activity_datum	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>refverzoek_activity_id: No Activity Found (REF verzocht: ' . print_r($part_refgevraagd, TRUE) . ')</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 3.3 GET ACTIVITIES 'VOG VERZOEK'
				// ************************************************************************************************************
   				$params_activity_vogverzoek_get = [		// zoek activities 'VOG verzoek'
  					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG_verzoek",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_get:' . print_r($params_activity_vogverzoek_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_vogverzoek = civicrm_api3('Activity', 'get', $params_activity_vogverzoek_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_get_result:' . print_r($result_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				#if ($extdebug == 1) { watchdog('php', '<pre>result_count_vogverzoek:' . print_r($result_vogverzoek['count'], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($result_vogverzoek['count'] == 1) {
  					$vogverzoek_activity_id		= $result_vogverzoek['values'][0]['id'];
  					$vogverzoek_activity_status	= $result_vogverzoek['values'][0]['status_id'];
	  				if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				} else {
					$vogverzoek_activity_id		= NULL;
  					$vogverzoek_activity_status	= NULL;
  					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id: No Activity Found (VOG verzocht: ' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				}
				// ************************************************************************************************************
				// 3.4 GET ACTIVITIES 'VOG AANVRAAG'
				// ************************************************************************************************************
  				$params_activity_vogaanvraag_get = [		// zoek activities 'VOG aanvraag'
   					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG_aanvraag",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_get:' . print_r($params_activity_vogaanvraag_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$result_aanvraag = civicrm_api3('Activity', 'get', $params_activity_vogaanvraag_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_get_result:' . print_r($result_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
				// 3.5 GET ACTIVITIES 'VOG ONTVANGST'
				// ************************************************************************************************************
  				$params_activity_vogontvangst_get = [		// zoek activities 'VOG ontvangst'
   					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG_ontvangst",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_get:' . print_r($params_activity_vogontvangst_get, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_ontvangst = civicrm_api3('Activity', 'get', $params_activity_vogontvangst_get);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_get_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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

			#if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213"))) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {
			###############################################################################################################
			### 4. BEPAAL DE JUISTE DATUMS VOOR ACTIVITIES AANVRAAG & ONTVANGST
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 4.1 REF ACTIVITIES [DEFINE NEW DATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>1. prt_refpersoon:' . print_r($part_refpersoon, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. act_refpersoon:' . print_r($refpersoon_activity_datum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. rel_refpersoon:' . print_r($referentie_start, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>3. prt_refverzoek:' . print_r($part_refgevraagd, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. act_refverzoek:' . print_r($refverzoek_activity_datum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. rel_refverzoek:' . print_r($referentie_gevraagd, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($extdebug == 1) { watchdog('php', '<pre>3. prt_reffeedback:' . print_r($part_reffeedback, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. rel_reffeedback:' . print_r($referentie_feedback, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 4.1a BEPAAL (NIEUWE) DATUM ACTIVITY REF PERSOON
				// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 4.1a BEPAAL (NIEUWE) DATUM ACTIVITY REF PERSOON ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($part_refpersoon) {
				#	$datum_refpersoon 	= $part_refpersoon;											// ZET DATUM AANVRAAG VAN ACTIVITY GELIJK AAN AANVRAAGDATUM
				#} else {
					#$datum_refpersoon	= $todaydatetime;
					$newdate		 	= strtotime ( '+42 day' , strtotime ($part_refpersoon) ) ;	// ZET DEADLINE AANVRAAG OP 30 DAGEN NA VERZOEK
					$datum_refpersoon  	= date ( 'Y-m-d H:i:s' , $newdate );
				}
				// ************************************************************************************************************
				// 4.1ba BEPAAL (NIEUWE) DATUM ACTIVITY REF VERZOEK
				// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 4.1b BEPAAL (NIEUWE) DATUM ACTIVITY REF VERZOEK ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($part_refgevraagd) {
				#	$datum_refverzoek 	= $part_refgevraagd;										// ZET DATUM AANVRAAG VAN ACTIVITY GELIJK AAN AANVRAAGDATUM
				#} else {
					#$datum_refverzoek	= $todaydatetime;
					$newdate		 	= strtotime ( '+42 day' , strtotime ($part_refgevraagd) ) ;	// ZET DEADLINE AANVRAAG OP 30 DAGEN NA VERZOEK
					$datum_refverzoek  	= date ( 'Y-m-d H:i:s' , $newdate );
				}
				// ************************************************************************************************************
				// 4.1 BEPAAL (NIEUWE) DATUM ACTIVITY VOG AANVRAAG
				// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 4.2 VOG ACTIVITIES [DEFINE NEW DATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>1. part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>3. part_vogontvangst:' . print_r($part_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>### 4.2a BEPAAL (NIEUWE) DATUM ACTIVITY VOG AANVRAAG ###</pre>', NULL, WATCHDOG_DEBUG); }
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
				// 4.2 BEPAAL (NIEUWE) DATUM ACTIVITY VOG ONTVANGST
				// ************************************************************************************************************

				if ($extdebug == 1) { watchdog('php', '<pre>### 4.2b BEPAAL (NIEUWE) DATUM ACTIVITY VOG ONTVANGST ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($part_vogontvangst) { // VOG-ontvangst is datum van ontvangst, indien leeg dan datum ingediend of datum verzocht (zou ook datum vog kunnen zijn)
					$datum_ontvangst = $part_vogontvangst;											// ZET DATUM ONTVANGST VAN ACTIVITY GELIJK AAN ONTVANGSTDATUM
				} elseif ($part_vogingediend) {
					$newdate		 = strtotime ( '+49 day' , strtotime ($part_vogingediend) ) ;	// ZET 'DEADLINE' ONTVANGST OP 7 WEKEN NA INDIENEN AANVRAAG
					$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
				} else {																			// ZET EEN FICTIEVE DATUM VOOR ACTIVITY ONTVANGST 10 WEKEN NA VERZOEKDATUM
					$newdate		 = strtotime ( '+70 day' , strtotime ($part_vogverzocht) ) ;
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

			// if ($extact == 1 AND (in_array($groupID, array("190", "140", "165", "213"))) AND ($vogverzoekditjaar == 1 OR $refverzoekditjaar == 1)) {
			if ($extact == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {
			###############################################################################################################
			### 5. CREATE ACTIVITIES
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 5. VOG ACTIVITIES [CREATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 5.1 CREATE AN ACTIVITY 'REFPERSOON' ALS REF PERSOON IS GEVRAAGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ************************************************************************************************************
  					if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_id: ' . print_r($refpersoon_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extdebug == 1) { watchdog('php', '<pre>part_refpersoon: ' . print_r($part_refpersoon, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if (empty($refpersoon_activity_id) AND $part_refpersoon) {
					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.1 CREATE AN ACTIVITY REFPERSOON ALS REF PERSOON IS GEVRAAGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }

  					if ($extref == 1 AND in_array($refnodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$params_activity_refpersoon_create = [
  							'checkPermissions' => FALSE,
  							'values' => [
						    	'source_contact_id' 	=> 1,
    							'target_contact_id' 	=> $contact_id,
    							'activity_type_id' 		=> 139,
    							'activity_date_time' 	=> $todaydatetime,
    							'subject' 				=> 'REF persoon verzocht',
    							'status_id' 			=> 1, // initial status ingepland
  							],
						];
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refpersoon_create:' . print_r($params_activity_refpersoon_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extref == 1)	{
							$result = civicrm_api4('Activity', 'create', $params_activity_refpersoon_create);
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refpersoon_create EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
							#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refpersoon_create RESULTS:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					  	if (empty($refpersoon_activity_id))		{ $refpersoon_activity_id		= $result[0][id]; }
						if (empty($refpersoon_activity_status))	{ $refpersoon_activity_status	= $result[0][status_id]; }
						if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_id2:' . print_r($refpersoon_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>refpersoon_activity_status2:' . print_r($refpersoon_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				// ************************************************************************************************************
				// 5.2 CREATE AN ACTIVITY 'REFVERZOEK' ALS REF FEEDBACK IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ************************************************************************************************************
  					if ($extdebug == 1) { watchdog('php', '<pre>refgevraagd_activity_id: ' . print_r($refverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extdebug == 1) { watchdog('php', '<pre>part_refgevraagd: ' . print_r($part_refgevraagd, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if (empty($refverzoek_activity_id) AND $part_refgevraagd) {
					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.2 CREATE AN ACTIVITY VERZOEK ALS REF FEEDBACK IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }

  					if ($extref == 1 AND in_array($refnodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$params_activity_refverzoek_create = [
  							'checkPermissions' => FALSE,
  							'values' => [
						    	'source_contact_id' 	=> 1, 
    							'target_contact_id' 	=> $contact_id, 
    							'assignee_contact_id' 	=> $referentie_cid, 
    							'activity_type_id' 		=> 117, 
    							'activity_date_time' 	=> $part_refgevraagd,
    							'subject' 				=> 'REF feedback gevraagd', 
    							'status_id' 			=> 1, // initial status ingepland
  							],
						];
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_create:' . print_r($params_activity_refverzoek_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extref == 1)	{
							$result = civicrm_api4('Activity', 'create', $params_activity_refverzoek_create);
							#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_create EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
							#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_create RESULTS:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_create RESULTS 0:' . print_r($result[0], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
							#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_create RESULTS 0 ID:' . print_r($result[0][id], TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					  	if (empty($refverzoek_activity_id))		{ $refverzoek_activity_id		= $result[0][id]; }
						if (empty($refverzoek_activity_status))	{ $refverzoek_activity_status	= $result[0][status_id]; }
						if ($extdebug == 1) { watchdog('php', '<pre>refgevraagd_activity_id2:' . print_r($refverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>refgevraagd_activity_status2:' . print_r($refverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				// ************************************************************************************************************
				// 5.3 CREATE AN ACTIVITY 'VOGVERZOEK' ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ************************************************************************************************************
				if (empty($vogverzoek_activity_id) AND $part_vogverzocht) {
					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.3 CREATE AN ACTIVITY VERZOEK ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }

  					if ($extvog == 1 AND in_array($refnodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$params_activity_vogverzoek_create = [
  							'checkPermissions' => FALSE,
  							'values' => [
						    	'source_contact_id' 	=> 1, 
    							'target_contact_id' 	=> $contact_id, 
    							'activity_type_id' 		=> 118, 
    							'activity_date_time' 	=> $part_vogverzocht, 
    							'subject' 				=> 'VOG aanvraag verzocht', 
    							'status_id' 			=> 2, // initial status completed
  							],
						];
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_create:' . print_r($params_activity_vogverzoek_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extvog == 1)	{
							$result = civicrm_api4('Activity', 'create', $params_activity_vogverzoek_create);
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_create EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_create RESULTS:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					  	if (empty($vogverzoek_activity_id))		{ $vogverzoek_activity_id		= $result[0][id]; }
						if (empty($vogverzoek_activity_status))	{ $vogverzoek_activity_status	= $result[0][status_id]; }
						if ($extdebug == 1) { watchdog('php', '<pre>refverzoek_activity_id2:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>refverzoek_activity_status2:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				// ************************************************************************************************************
				// 5.4 CREATE AN ACTIVITY 'VOGAANVRAAG' ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ***********************************************************************************************************
  				if (empty($vogaanvraag_activity_id) AND $datum_aanvraag AND ($part_vogverzocht OR $part_vogingediend)) {
  					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.4 CREATE AN ACTIVITY AANVRAAG ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }

  					if ($extvog == 1 AND in_array($refnodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$params_activity_vogaanvraag_create = [
  							'checkPermissions' => FALSE,
  							'values' => [
						    	'source_contact_id' 	=> 1, 
    							'target_contact_id' 	=> $contact_id, 
    							'activity_type_id' 		=> 119, 
    							'activity_date_time' 	=> $datum_aanvraag, 
    							'subject' 				=> 'VOG aanvraag ingediend', 
    							'status_id' 			=> 7, // initial status draft
  							],
						];
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_create:' . print_r($params_activity_vogaanvraag_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extvog == 1)	{
							$result = civicrm_api4('Activity', 'create', $params_activity_vogaanvraag_create);
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_create EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_create RESULTS:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					  	if (empty($vogaanvraag_activity_id))		{ $vogaanvraag_activity_id		= $result[0][id]; }
						if (empty($vogaanvraag_activity_status))	{ $vogaanvraag_activity_status	= $result[0][status_id]; }
						if ($extdebug == 1) { watchdog('php', '<pre>refaanvraag_activity_id2:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>refaanvraag_activity_status2:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}

				// ************************************************************************************************************
				// 5.5 CREATE AN ACTIVITY 'VOGONTVANGST' ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				// ************************************************************************************************************
				if (empty($vogontvangst_activity_id) AND $datum_ontvangst AND ($part_vogverzocht OR $part_vogingediend)) {
					if ($extdebug == 1) { watchdog('php', '<pre>--- 5.5 CREATE AN ACTIVITY ONTVANGST ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }

  					if ($extvog == 1 AND in_array($refnodig, array("eerstex", "elkjaar", "opnieuw"))) {
						$params_activity_vogontvangst_create = [
  							'checkPermissions' => FALSE,
  							'values' => [
						    	'source_contact_id' 	=> 1, 
    							'target_contact_id' 	=> $contact_id, 
    							'activity_type_id' 		=> 120, 
    							'activity_date_time' 	=> $datum_ontvangst, 
    							'subject' 				=> 'VOG ontvangst bevestigd', 
    							'status_id' 			=> 7, // initial status draft
  							],
						];
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_create:' . print_r($params_activity_vogontvangst_create, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extvog == 1)	{
							$result = civicrm_api4('Activity', 'create', $params_activity_vogontvangst_create);
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_create EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
							if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_create RESULTS:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						}
					  	if (empty($vogontvangst_activity_id))		{ $vogontvangst_activity_id		= $result[0]['id']; }
						if (empty($vogontvangst_activity_status))	{ $vogontvangst_activity_status	= $result[0][status_id]; }
						if ($extdebug == 1) { watchdog('php', '<pre>refontvangst_activity_id2:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
						if ($extdebug == 1) { watchdog('php', '<pre>refontvangst_activity_status2:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
			}

//			if ($extact == 1 AND (in_array($groupID, array("190", "140", "165", "213"))) AND ($vogverzoekditjaar == 1 OR $refverzoekditjaar == 1)) {
			if ($extact == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {
			###############################################################################################################
			### 6. UPDATE ACTIVITIES
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 6. VOG/REF ACTIVITIES [UPDATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT REF PERSOON
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT REF PERSOON</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				#if ($part_refpersoon) { $act_refpersoon = $part_refpersoon; } else { $act_refpersoon = $todaydatetime; }
				$diffsince_refpersoon	= date_diff(date_create($part_refpersoon),date_create($todaydatetime));
				$dayssince_refpersoon	= $diffsince_refpersoon->format('%a');
				if ($dayssince_refpersoon >= 0  AND $dayssince_refpersoon < 7)			{ $status_refpersoon = "Pending"; 		} // AFWACHTING
				if ($dayssince_refpersoon >= 7  AND $dayssince_refpersoon < 21)			{ $status_refpersoon = "Left Message";  } // HERINNERD
				if ($dayssince_refpersoon >= 21 AND $dayssince_refpersoon < 35)			{ $status_refpersoon = "Unreachable"; 	} // ONBEREIKBAAR
				if ($dayssince_refpersoon >= 35)										{ $status_refpersoon = "No_show"; 		} // VERLOPEN
				if ($dayssince_refpersoon >= 300)										{ $status_refpersoon = "Failed"; 		} // GEFAALD
				if (strtotime($todaydatetime) > strtotime($event_startdate) AND $status_refpersoon != "Completed")	{ $status_refpersoon = "Bounced"; }
				// Bounced nadat de startdag van kamp gepasseerd is
				if ($refpersoon_activity_id AND $referentie_cid) {
					$status_refpersoon 	= 'Completed';
				}
				// M61 zorg dat de REF persoon activitiy en mails pas starten zodra de VOG klaargezet is
				if (in_array($vognodig, array("eerstex", "elkjaar", "opnieuw")) AND $vogverzoekditjaar != 1) {
					$status_refpersoon 	= 'Scheduled';
				}
				// M61 vraag alleen om REF persoon als die niet volgend jaar sws gevraagd zou worden
				if (in_array($vognodig, array("noggoed")) AND in_array($refnodig, array("eerstex")) AND $vognodignextyear == 1) {
					$status_refpersoon 	= 'Not Required';
				}
				// M61 vraag alleen om REF persoon bij leiding <5 keer mee in jaren die niet gelijklopen met VOGNODIG
				if (in_array($vognodig, array("noggoed")) AND in_array($refnodig, array("eerstex")) AND $curcv_leid_nr > 5) {
					$status_refpersoon 	= 'Not Required';
				}
				if ($vognodignextyear == 1 AND in_array($refnodig, array("noggoed"))) {
					$status_refpersoon 	= 'Not Required';
				}
				if ($refpersoon_activity_id AND $part_refgevraagd) {
					$status_refpersoon = 'Completed';
				}
				if ($extdebug == 1) { watchdog('php', '<pre>dayssince_refpersoon:' . print_r($dayssince_refpersoon, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>vognodignextyear:' . print_r($vognodignextyear, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>(new) act_status ref_persoon:' . print_r($status_refpersoon, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 6.2 BEPAAL (NIEUWE) STATUS ACTIVITEIT REF VERZOEK
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT REF GEVRAAGD</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				#if ($part_refpersoon) { $act_refpersoon = $part_refpersoon; } else { $act_refpersoon = $todaydatetime; }
				$diffsince_refverzoek	= date_diff(date_create($part_refgevraagd),date_create($todaydatetime));
				$dayssince_refverzoek	= $diffsince_refverzoek->format('%a');
				if ($dayssince_refverzoek >= 0  AND $dayssince_refverzoek < 7)			{ $status_refverzoek = "Pending"; 		} // AFWACHTING
				if ($dayssince_refverzoek >= 7  AND $dayssince_refverzoek < 21)			{ $status_refverzoek = "Left Message";  } // HERINNERD
				if ($dayssince_refverzoek >= 21 AND $dayssince_refverzoek < 35)			{ $status_refverzoek = "Unreachable"; 	} // ONBEREIKBAAR
				if ($dayssince_refverzoek >= 35)										{ $status_refverzoek = "No_show"; 		} // VERLOPEN
				if ($dayssince_refverzoek >= 300)										{ $status_refverzoek = "Failed"; 		} // GEFAALD
				if (strtotime($todaydatetime) > strtotime($event_startdate) AND $status_refaanvraag != "Completed")	{ $status_refaanvraag = "Bounced"; }
				// Bounced nadat de startdag van kamp gepasseerd is
				if ($vognodignextyear == 1 AND in_array($refnodig, array("noggoed"))) {
					$status_refverzoek 	= 'Not Required';
				}
				if ($refverzoek_activity_id AND $part_reffeedback) {
					$status_refverzoek = 'Completed';
				}
				if ($extdebug == 1) { watchdog('php', '<pre>dayssince_refverzoek:' . print_r($dayssince_refverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>(new) act_status ref_verzoek:' . print_r($status_refverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 6.3 BEPAAL (NIEUWE) STATUS ACTIVITEIT VOG AANVRAAG
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.3 BEPAAL (NIEUWE) STATUS ACTIVITEIT VOG AANVRAAG</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				$diffsince_vogverzoek	= date_diff(date_create($part_vogverzocht),date_create($todaydatetime));
				$dayssince_vogverzoek	= $diffsince_vogverzoek->format('%a');
				if ($dayssince_vogverzoek >= 0  AND $dayssince_vogverzoek < 14)			{ $status_vogaanvraag = "Pending"; 		} // AFWACHTING
				if ($dayssince_vogverzoek >= 14 AND $dayssince_vogverzoek < 21)			{ $status_vogaanvraag = "Left Message"; } // HERINNERD
				if ($dayssince_vogverzoek >= 21 AND $dayssince_vogverzoek < 30)			{ $status_vogaanvraag = "Unreachable"; 	} // ONBEREIKBAAR
				if ($dayssince_vogverzoek >= 30)										{ $status_vogaanvraag = "No_show"; 		} // VERLOPEN
				if ($dayssince_vogverzoek >= 300)										{ $status_vogaanvraag = "Failed"; 		} // GEFAALD
				if (strtotime($todaydatetime) > strtotime($event_startdate) AND $status_vogaanvraag != "Completed")			{ $status_vogaanvraag = "Bounced"; 		} // Bounced nadat de startdag van kamp gepasseerd is

				// LET OP: DE VOLGENDE 2 REGELS NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if (strtotime($part_vogingediend) >= strtotime($fiscalyear_start))	{ // hier een between van maken (of liever nog een functie)
					#if ($extdebug == 1) { watchdog('php', '<pre>A. part_vogingediend:' . print_r(strtotime($part_vogingediend), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>B. fiscalyear_start:' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>STATUS VOG AANVRAAG AANGEPAST VAN: '.$status_vogaanvraag.' NAAR Completed (omdat part_vogingediend: '.$part_vogingediend.' >= '.$fiscalyear_start.')</pre>', NULL, WATCHDOG_DEBUG); }
					$status_vogaanvraag  = "Completed";
				}
				if (strtotime($part_vogdatum)	  >= strtotime($fiscalyear_start) AND $status_vogaanvraag != 'Completed')	{ // hier een between van maken (of liever nog een functie)
					#if ($extdebug == 1) { watchdog('php', '<pre>A. part_vogdatum:' . print_r(strtotime($part_vogdatum), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>B. fiscalyear_start:' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>STATUS VOG AANVRAAG AANGEPAST VAN: '.$status_vogaanvraag.' NAAR Completed (omdat part_vogdatum: '.$part_vogdatum.' >= '.$fiscalyear_start.')</pre>', NULL, WATCHDOG_DEBUG); }
					$status_vogaanvraag  = "Completed";
				}
				if (strtotime($part_vogingediend) < strtotime($fiscalyear_start) AND strtotime($part_vogdatum) < strtotime($fiscalyear_start))	{
					// als part_vogdatum binnen huidige fiscal year valt kan activity op completed (nu alleen later dan fiscal year start)
					if ($extdebug == 1) { watchdog('php', '<pre>a. part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>b. diffsinceverzoek:' . print_r($diffsince_vogverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>b. dagen_sinds_verzoek:' . print_r($dayssince_vogverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				if ($extdebug == 1) { watchdog('php', '<pre>c. (new) status_aanvraag:' . print_r($status_vogaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 6.4 BEPAAL (NIEUWE) STATUS ACTIVITEIT VOG ONTVANGST
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.4 BEPAAL (NIEUWE) STATUS ACTIVITEIT VOG ONTVANGST</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				$diffsinceaanvraag	= date_diff(date_create($part_vogingediend),date_create($todaydatetime));
				$dayssinceaanvraag	= $diffsinceaanvraag->format('%a');
				if ($dayssinceaanvraag >= 0  AND $dayssinceaanvraag < 21)			{ $status_vogontvangst = "Pending"; 		} // AFWACHTING
				if ($dayssinceaanvraag >= 21 AND $dayssinceaanvraag < 35)			{ $status_vogontvangst = "Left Message"; 	} // HERINNERD
				if ($dayssinceaanvraag >= 35)										{ $status_vogontvangst = "Unreachable"; 	} // ONBEREIKBAAR
				//if ($dayssince_vogverzoek  >= 21 AND $status_vogontvangst == "Pending")	{ $status_vogontvangst = "Left Message"; 	} // Als aanvraag Unreachable of Bounced is -> schedule dan alsnog een reminder rond geplande ontvangstdatum
				if (strtotime($todaydatetime) > strtotime($event_startdate) AND $status_vogontvangst != "Completed")			{ $status_vogontvangst = "No_show"; 		} // Bounced nadat de startdag van kamp gepasseerd is
				if ($dayssinceaanvraag >= 300)										{ $status_vogontvangst = "Failed"; 		}  // GEFAALD
				// LET OP: DE VOLGENDE 1 REGEL NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if ($status_vogaanvraag != "Completed")								{ $status_vogontvangst	= "Available"; 		} // Maak status Activiteit ONTVANGST = Available als status AANVRAAG nog niet Completed is (civirules proof?)
				// LET OP: DE VOLGENDE 1 REGEL NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if (strtotime($part_vogdatum) >= strtotime($fiscalyear_start))		{ $status_vogontvangst = "Completed"; 		} else {
					if ($extdebug == 1) { watchdog('php', '<pre>a. part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>b. diffsinceaanvraag:' . print_r($diffsinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>b. dagen_sinds_aanvraag:' . print_r($dayssinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				} // als part_vogdatum binnen huidige fiscal year valt kan activity op completed
				if ($extdebug == 1) { watchdog('php', '<pre>c. (new) status_ontvangst:' . print_r($status_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// *****************************************************************************************************************
				// 7.1 UPDATE ACTIVITY REF PERSOON
				if ($extdebug == 1) { watchdog('php', '<pre>--- 7.1 UPDATE ACTIVITY REF PERSOON</pre>', NULL, WATCHDOG_DEBUG); }	
				// *****************************************************************************************************************
				if ($refpersoon_activity_id AND $part_refpersoon) {

					$params_activity_refpersoon_change = [
  						'checkPermissions' => FALSE,
	  					'where' => [
    						['id', 					'=', $refpersoon_activity_id],
  						],
  						'values' => [
  							'activity_date_time' 	=> $datum_refpersoon,
  							'status_id:name' 		=> $status_refpersoon,
  							'subject'				=> 'REF persoon gevraagd',
  						],
					];
					#if ($vogverzoekditjaar == 1) { // M61 indien VOG verzoek trek deze datum REF persoon dan gelijk (= vog verzoek + 30 dagen)
					#	$params_activity_refpersoon_change['values']['activity_date_time']	= $datum_aanvraag;
					#}

  					if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refpersoon_change:' . print_r($params_activity_refpersoon_change, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extref == 1)	{
						$result = civicrm_api4('Activity', 'update', $params_activity_refpersoon_change);
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refpersoon_change EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refpersoon_change_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}				
				// *****************************************************************************************************************
				// 7.2 UPDATE ACTIVITY REF VERZOEK
				if ($extdebug == 1) { watchdog('php', '<pre>--- 7.2 UPDATE ACTIVITY REF VERZOEK</pre>', NULL, WATCHDOG_DEBUG); }	
				// *****************************************************************************************************************
  				if ($extdebug == 1) { watchdog('php', '<pre>refgevraagd_activity_id: ' . print_r($refverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				if ($extdebug == 1) { watchdog('php', '<pre>part_refgevraagd: ' . print_r($part_refgevraagd, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($refverzoek_activity_id AND $part_refgevraagd) {

					$params_activity_refverzoek_change = [
  						'checkPermissions' => FALSE,
	  					'where' => [
    						['id', 					'=', $refverzoek_activity_id],
  						],
  						'values' => [
							'ACT_REF.Naam_aanvrager'			=> $displayname,
  							'ACT_REF.Voornaam_aanvrager'		=> $first_name,
  							'ACT_REF.Functie_aanvrager'			=> $part_functie,
  							'ACT_REF.Kamp_aanvrager'			=> $welkkamplang,
  							'ACT_REF.ID_aanvrager'				=> $contact_id,
  							'ACT_REF.ID_Kamp'					=> $part_eventid,
  							'ACT_REF.REF_nodig' 				=> $refnodig,
  							'activity_date_time' 				=> $datum_refverzoek,
  							'status_id:name' 					=> $status_refverzoek,
  							'subject'							=> 'REF feedback verzocht',
  						],
					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_change:' . print_r($params_activity_refverzoek_change, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extref == 1)	{
						$result = civicrm_api4('Activity', 'update', $params_activity_refverzoek_change);
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_change EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_refverzoek_change_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				// *****************************************************************************************************************
				// 7.3 UPDATE ACTIVITY VOG VERZOEK
				if ($extdebug == 1) { watchdog('php', '<pre>--- 7.3 UPDATE ACTIVITY VOG VERZOEK STATUS TO COMPLETED</pre>', NULL, WATCHDOG_DEBUG); }	
				// *****************************************************************************************************************
				if ($vogverzoek_activity_id AND $part_vogverzocht) {
  					$params_activity_vogverzoek_change = [
  						'id'					=> $vogverzoek_activity_id,
  						'activity_date_time'	=> $part_vogverzocht,
  						'status_id'				=> 'Completed',
  						'custom_1261'			=> $part_vogverzocht,
  						'custom_1274'			=> $event_startdate,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_change:' . print_r($params_activity_vogverzoek_change, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api3('Activity', 'create', $params_activity_vogverzoek_change);
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_change EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_change_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}

				// ************************************************************************************************************
				// 7.4 UPDATE ACTIVITY VOG AANVRAAG STATUS N.A.V. DAYS SINCE...	
				if ($extdebug == 1) { watchdog('php', '<pre>--- 7.4 UPDATE ACTIVITY VOG AANVRAAG STATUS N.A.V. DAYS SINCE ('.$dayssince_vogverzoek.')</pre>', NULL, WATCHDOG_DEBUG); }	
				// ************************************************************************************************************
				#if ((in_array($vogaanvraag_activity_status, array("1", "4", "5", "8")) AND in_array($vogontvangst_activity_status, array("2"))) AND $part_vogingediend) {
				#if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>datum_aanvraag:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>status_aanvraag:' . print_r($status_vogaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				if ($vogaanvraag_activity_id AND $datum_aanvraag AND $status_vogaanvraag) {
  					$params_activity_vogaanvraag_change = [
  						'id'					=> $vogaanvraag_activity_id,
  						'activity_date_time'	=> $datum_aanvraag,
  						'status_id'				=> $status_vogaanvraag,
  						'custom_1274'			=> $event_startdate,
  						'custom_1261'			=> $part_vogingediend,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_change:' . print_r($params_activity_vogaanvraag_change, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api3('Activity', 'create', $params_activity_vogaanvraag_change);
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_change EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_change_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
				// *****************************************************************************************************************
				// 7.5 UPDATE ACTIVITY VOG ONTVANGST STATUS NAV DAYS SINCE...
				if ($extdebug == 1) { watchdog('php', '<pre>--- 7.5 UPDATE ACTIVITY VOG ONTVANGST STATUS NAV DAYS SINCE ('.$dayssinceaanvraag.')</pre>', NULL, WATCHDOG_DEBUG); }	
				// *****************************************************************************************************************
				#if ((in_array($vogontvangst_activity_status, array("1", "4", "5", "8")) AND in_array($vogaanvraag_activity_status, array("2"))) AND $part_vogdatum AND $vognodig != 'noggoed') {
				if ($vogontvangst_activity_id AND $datum_ontvangst AND $status_vogontvangst) {
  					$params_activity_vogontvangst_change = [
  						'id'					=> $vogontvangst_activity_id,
  						'activity_date_time'	=> $datum_ontvangst,
  						'status_id'				=> $status_vogontvangst,
  						'custom_1274'			=> $event_startdate,
  						'custom_1261'			=> $part_vogontvangst,
  					];
  					if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_change:' . print_r($params_activity_vogontvangst_change, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extvog == 1)	{
						$result = civicrm_api3('Activity', 'create', $params_activity_vogontvangst_change);
						if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_change EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
						#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_change_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					}
				}
			}

			if ($extvog == 1 AND (in_array($groupID, array("190", "140", "165", "213")))) {
			###############################################################################################################
			### 8. DELETE ACTIVITIES (indien: 1. ze waren aangemaakt maar VOG nog goed was 2. er dit jaar geen verzoek was 3. de status niet completed was
			###############################################################################################################
				if ($extdebug == 1) { watchdog('php', '<pre>### 8. VOG ACTIVITIES [DELETE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1 AND (in_array($vognodig, array("noggoed")) OR $ditjaarleidnot == 1 OR $vogverzoekditjaar == 0)) {
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
			### 9 GET ACTIVITIES MBT. VOG
			###############################################################################################################
   				if ($extdebug == 1) { watchdog('php', '<pre>### 9. VOG ACTIVITIES [GET] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 9.1 GET ACTIVITIES 'VOG VERZOEK'
				// ************************************************************************************************************
   				$params_activity_vogverzoek_get2 = [		// zoek activities 'VOG verzoek'
  					'sequential' 			=> 1,
  					'return' 				=> array("id", "activity_date_time", "status_id"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id' 		=> "VOG_verzoek",
  					'activity_date_time' 	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_get:' . print_r($params_activity_vogverzoek_get2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_verzoek2 = civicrm_api3('Activity', 'get', $params_activity_vogverzoek_get2);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogverzoek_get_result:' . print_r($result_verzoek2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
				// 9.2 GET ACTIVITIES 'VOG AANVRAAG'
				// ************************************************************************************************************
  				$params_activity_vogaanvraag_get2 = [		// zoek activities 'VOG aanvraag'
   					'sequential' 			=> 1,
  					'return' 				=> array("id", "activity_date_time", "status_id"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id'		=> "VOG_aanvraag",
  					'activity_date_time' 	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_get2:' . print_r($params_activity_vogaanvraag_get2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$result_aanvraag2 = civicrm_api3('Activity', 'get', $params_activity_vogaanvraag_get2);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogaanvraag_get_result2:' . print_r($result_aanvraag2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
				// 9.3 GET ACTIVITIES 'VOG ONTVANGST'
				// ************************************************************************************************************
  				$params_activity_vogontvangst_get2 = [		// zoek activities 'VOG ontvangst'
   					'sequential'			=> 1,
  					'return' 				=> array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' 	=> $contact_id,
  					'activity_type_id' 		=> "VOG_ontvangst",
  					'activity_date_time'	=> array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				];
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_get2:' . print_r($params_activity_vogontvangst_get2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  				$result_ontvangst2 = civicrm_api3('Activity', 'get', $params_activity_vogontvangst_get2);
  				#if ($extdebug == 1) { watchdog('php', '<pre>params_activity_vogontvangst_get_result2:' . print_r($result_ontvangst2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
    	}
	}
	if (!empty($params_contact)) {
		if ($extdebug == 1) { watchdog('php', '<pre>params_contact:' . print_r($params_contact, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($contact_id) {
			$result = civicrm_api3('Contact', 'create', $params_contact);
			if ($extdebug == 1) { watchdog('php', '<pre>params_contact EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		}
	}
/*
	if (!empty($params_participant)) {
		if ($extdebug == 1) { watchdog('php', '<pre>params_participant:' . print_r($params_participant, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($part_eventid) {
			$result = civicrm_api3('Participant', 'create', $params_participant);
			if ($extdebug == 1) { watchdog('php', '<pre>params_participant EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
		} 
	}
*/

	if (!empty($params_participant2)) {
		$params_participant2['reload']				= TRUE;
		$params_participant2['checkPermissions']	= FALSE;
		if ($extdebug == 1) { watchdog('php', '<pre>params_participant2:' . print_r($params_participant2, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($part_eventid) {
			$result = civicrm_api4('Participant', 'update', $params_participant2);
			if ($extdebug == 1) { watchdog('php', '<pre>params_participant2 EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
			#if ($extdebug == 1) { watchdog('php', '<pre>params_participant2_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		}
	}
   	if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION VOG [groupID: '.$groupID.'] [op: '.$op.'] [entityID: '.$entityID.'] '.$part_functie.': '.$displayname.'] ***</pre>', null, WATCHDOG_DEBUG); }
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
