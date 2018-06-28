<?php

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once 'curriculum.civix.php';

function curriculum_civicrm_validateprofile($profileName)
{
    $processkampleeftijd = 0;
    if ($profileName === 'Verjaardag_en_geslacht_68' or
        $profileName === 'Verjaardag_en_geslacht_97' or
        $profileName === 'Verjaardag_en_geslacht_19'
    ) {
        watchdog('php', '<pre>---STARTKAMPLEEFTIJD---</pre>', null, WATCHDOG_DEBUG);
        $processkampleeftijd = 1;
        watchdog('php', '<pre>validateprofile: profile_name:' . print_r($profileName, true) . '</pre>', null, WATCHDOG_DEBUG);
        watchdog('php', '<pre>set_processkampleeftijd:' . print_r($processkampleeftijd, true) . '</pre>', null, WATCHDOG_DEBUG);
        #watchdog('php', '<pre>gid:' . print_r($gid, true) . '</pre>', null, WATCHDOG_DEBUG);
        #watchdog('php', '<pre>id:' . print_r($id, true) . '</pre>', null, WATCHDOG_DEBUG);
        #watchdog('php', '<pre>group_id:' . print_r($groupID, true) . '</pre>', null, WATCHDOG_DEBUG);
        #watchdog('php', '<pre>entityid:' . print_r($entityID, true) . '</pre>', null, WATCHDOG_DEBUG);
        watchdog('php', '<pre>---ENDKAMPLEEFTIJD---</pre>', null, WATCHDOG_DEBUG);
    }
}

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

	//if (!in_array($groupID, array("101", "103", "139", "190", "181"))) {
	if (!in_array($groupID, array("139","190"))) { // ALLEEN PART PROFILES
		// 101  EVENT KENMERKEN
		// 103	TAB  CURRICULUM
		// 139	PART DEEL
		// 190	PART LEID
		// (140	PART LEID VOG)
		// 181	TAB  INTAKE
		#if ($extdebug == 1) { watchdog('php', '<pre>--- SKIP EXTENSION CV (not in proper group) [groupID: '.$groupID.'] [op: '.$op.']---</pre>', null, WATCHDOG_DEBUG); }
		return; //   if not, get out of here
	}

	if (in_array($groupID, array("101"))) {
		if ($extdebug == 1) { watchdog('php', '<pre>*** START EXTENSION EVENT KENMERKEN [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>entityID:' . print_r($entityID, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		#$event_plekken_jongens = NULL:
		#$event_plekken_meisjes = NULL:

    	$result = civicrm_api3('Event', 'get', array(
      		'sequential' => 1,
      		'return' => array("event_type_id", "custom_658", "custom_657", "has_waitlist", "waitlist_text", "event_full_text", "max_participants", "custom_516"),
      		'id' => $entityID,
    	));

		#if ($extdebug == 1) { watchdog('php', '<pre>geteventinfo_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    	#$event_type_id			= $result['values'][0]['event_type_id'];
    	$event_plekken_jongens	= $result['values'][0]['custom_658'];
    	$event_plekken_meisjes	= $result['values'][0]['custom_657'];
    	$event_haswaitlist		= $result['values'][0]['has_waitlist'];
    	$event_waitlisttext		= $result['values'][0]['waitlist_text'];
   	    $event_fulltext			= $result['values'][0]['full_text'];
    	$event_max_participants = $result['values'][0]['max_participants'];
    	$event_lastminute		= $result['values'][0]['custom_516'];

   	    if ($extdebug == 1) { watchdog('php', '<pre>event_plekken_jongens:' . print_r($event_plekken_jongens, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	if ($extdebug == 1) { watchdog('php', '<pre>event_plekken_meisjes:' . print_r($event_plekken_meisjes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>event_haswaitlist:' . print_r($event_haswaitlist, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>event_waitlisttext:' . print_r($event_waitlisttext, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>event_fulltext:' . print_r($event_fulltext, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>event_max_participants:' . print_r($event_max_participants, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	#if ($extdebug == 1) { watchdog('php', '<pre>event_lastminute:' . print_r($event_lastminute, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

    	$event_waitlist_beiden	= "Dit kamp is op dit moment zo goed als vol. Per deelnemer moeten we bekijken of er nog plek is. Dit heeft met de indeling van de groepjes en de slaapzalen te maken. U kunt uw kind aanmelden voor de wachtlijst. We zullen u vervolgens op de hoogte stellen of, en zo ja, wanneer de aanmelding alsnog doorgang kan vinden.";
    	$event_waitlist_jongens	= "LET OP: Voor dit kamp is op dit moment alleen nog plek voor meisjes. Jongens komen op de wachtlijst. Meisjes komen trouwens eerst ook op de wachtlijst maar we sturen u vrij snel na de aanmelding een linkje om de aanmelding van uw dochter alsnog af te kunnen ronden.";
    	$event_waitlist_meisjes	= "LET OP: Voor dit kamp is op dit moment alleen nog plek voor jongens. Meisjes komen op de wachtlijst. Jongens komen trouwens eerst ook op de wachtlijst maar we sturen u vrij snel na de aanmelding een linkje om de aanmelding van uw zoon alsnog af te kunnen ronden.";
    	$event_waitlist_naarjk	= "Hierboven ziet u wat de beschikbaarheid is voor jongens en voor meisjes. Op dit moment komt iedereen sowieso eerst op de wachtlijst. Voor wie er toch plek is sturen we een email om de aanmelding af te ronden. Jongens en meiden die rond december 16 worden zijn van harte welkom om mee te gaan met het Jeugdkamp in plaats van het Tienerkamp.";

    	$event_fulltext 		= "Helaas zijn er voor dit kamp geen plekken meer beschikbaar. De aanmeldingen voor 2019 gaan op 1 januari weer open. We verwijzen u voor deze zomer graag naar onze collega kamporganisaties zoals o.a. Kaleb, YoY kampen, Camps4kids, Oase kampen, Wegwijzerkampen en Geloofshelden.";

    	$params_event = array(
   			'id' 				=> $entityID,
      		'has_waitlist' 		=> 1, // default: wachtlijst is aan (indien aanmeldingen > max_participants)
      		'max_participants'	=> 1,
      		'waitlist_text' 	=> $event_waitlist_beiden,
      		'event_full_text'	=> $event_fulltext,
      		#'custom_516'		=> 0,
    	);

    	// WACHTLIJST VOOR JONGENS & PLEK VOOR MEISJES
    	if (in_array($event_plekken_jongens, array(":-|"), true)) {
    		$params_event['has_waitlist'] 		= 0;
     		$params_event['max_participants'] 	= 1;
    		$params_event['waitlist_text']		= $event_waitlist_jongens;
    		#$params_event['custom_516'] 		= 1; // op de lastminutelijst?
    	}
    	// WACHTLIJST VOOR MEISJES & PLEK VOOR JONGENS
    	if (in_array($event_plekken_meisjes, array(":-|"), true)) {
    		$params_event['has_waitlist'] 		= 0;
     		$params_event['max_participants'] 	= 1;
    		$params_event['waitlist_text']		= $event_waitlist_meisjes;
    		#$params_event['custom_516'] 		= 1; // op de lastminutelijst?
    	}
    	// WACHTLIJST VOOR JONGENS & MEISJES
    	if (in_array($event_plekken_jongens, array(":-|"), true) 		AND in_array($event_plekken_meisjes, array(":-|"), true)) {
    		$params_event['has_waitlist'] 		= 0;
     		$params_event['max_participants'] 	= 1;
    		$params_event['waitlist_text']		= $event_waitlist_beiden;
    		#$params_event['custom_516'] 		= 0; // op de lastminutelijst?
    	}
    	// PLEK VOOR JONGENS & MEISJES
    	if (in_array($event_plekken_meisjes, array(";-)",":-)"), true) 	AND in_array($event_plekken_jongens, array(";-)",":-)"), true)) {
    		$params_event['has_waitlist'] 		= 0;
    		$params_event['max_participants'] 	= 200;
    		#$params_event['custom_516']		= 1; // op de lastminutelijst?
    	}
    	// VOL VOOR JONGENS & MEISJES
    	if (in_array($event_plekken_jongens, array(":-("), true) 		AND in_array($event_plekken_meisjes, array(":-("), true)) {
    		$params_event['has_waitlist'] 		= 0;
     		$params_event['max_participants'] 	= 1;
    		#$params_event['custom_516'] 		= 0; // op de lastminutelijst?
    	}

		if ($extdebug == 1) { watchdog('php', '<pre>params_event:' . print_r($params_event, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		$result = civicrm_api3('Event', 'create', $params_event);
		if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION EVENT KENMERKEN [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG); }
		return; //   if not, get out of here
	}

	if (in_array($groupID, array("103", "139", "190", "181"))) {

    	if ($extdebug == 1) { watchdog('php', '<pre>*** 1. START EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG); }

		if ($op != 'create' && $op != 'edit') { //    did we just create or edit a custom object?
    		if ($extdebug == 1) { watchdog('php', '<pre>EXIT: op != create OR op != edit</pre>', NULL, WATCHDOG_DEBUG); }
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
		$partstatusyes 	= array(1,2,5,6,15,16);							//	PARTICIPANT STATUS BETEKENT DEELNAME = YES
		$eventypesdeel 	= array(11, 12, 13, 14, 21, 22, 23, 24, 33);	//	EVENT_TYPE_ID'S VAN DE KAMPEN VAN DIT JAAR
		$eventypesleid 	= array(0 => 1);								//	EVENT_TYPE_ID VAN HET LEIDING EVENT VAN DIT JAAR
		$vognodig 		= NULL;

		#####################################################################################################
		# 1.1 VIND ALLE EVENT LEIDING & DEELNEMERS VOOR DIT JAAR
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.1 VIND ALLE EVENT LEIDING & DEELNEMERS VOOR DIT JAAR [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
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
		$grensvognoggoed = date("$fiscalYearStart[d]-$fiscalYearStart[M]-Y", strtotime("-3 year")); // VOG noggoed als datum binnen priveous 2 fiscal year valt
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
		# 1.2 GET PARTICIPANT INFO FOR ALL KAMPIDS
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.2. GET PARTICIPANT INFO FOR ALL KAMPIDS [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

   		if (in_array($groupID, array("139", "190"))) {	// PART DEEL + PART LEID
			$where = 'id';
		}
		if (in_array($groupID, array("103", "181"))) {	// TAB CV + TAB INTAKE
			$where = 'contact_id';
		}
    	$params_partinfo = array(
      		'sequential' 	=> 1,
      		'return' 		=> array("id","contact_id","first_name","display_name","event_id","participant_status_id", "custom_592","custom_649","custom_567","custom_568","custom_56","custom_68","custom_599","custom_600","custom_959","custom_603","custom_602"),
      		'status_id' 	=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Pending from waitlist", "Partially paid", "Pending refund","Cancelled"),
      		$where 			=> $entityID,
      		'event_id' 		=> array('IN' => $kampids_all),	// gebruik de gevonden event_id's van de kampen van dit jaar 
      		// LET OP: mogelijk komen hier meerdere kampen als resultaat uit. Er moet dan een exception zijn om te bepalen wat we doen (bv. ene kamp geannuleerd, andere geregistreerd)
      		// FEITELIJK gebeurt dat niet en zou het juist moeten. Zodat indien een geannulleerd event wordt geedit (maar er is ook een geregisiteerd event) op het CV toch dat jaar als meegegaan komt.
      		// INDIEN STATUS_ID = 4 (geannuleerd) dan zou er een nieuwe query naar alle events van dit/dat jaar moeten komen, op zoek naar eentje wel met positieve status.
    	);
   		#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo:' . print_r($params_partinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  		$result = civicrm_api3('Participant', 'get', $params_partinfo);
   		#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		$contact_id				= $result['values'][0]['contact_id'];	
		$part_id 				= $result['values'][0]['id'];
   		$part_eventid 			= $result['values'][0]['event_id'];
   		$part_welkkamp 			= $result['values'][0]['custom_567'];
   		$part_functie 			= $result['values'][0]['custom_568'];
   		$part_status_id			= $result['values'][0]['participant_status_id'];

   		$vogrecent 				= $result['values'][0]['custom_56'];
   		$vogkenmerk 			= $result['values'][0]['custom_68'];
   		$part_vogverzocht 		= $result['values'][0]['custom_599'];
		$part_vogingediend		= $result['values'][0]['custom_600'];
		$part_vogontvangst		= $result['values'][0]['custom_959'];
   		$part_vogdatum			= $result['values'][0]['custom_603'];
   		$part_vogkenmerk 		= $result['values'][0]['custom_602'];

		if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:'. print_r($part_eventid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>part_welkkamp:'. print_r($part_welkkamp, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>part_status_id:'. print_r($part_status_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>part_vogingediend:'. print_r($part_vogingediend, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>part_vogontvangst:'. print_r($part_vogontvangst, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($part_status_id == 4) {
			#####################################################################################################
			# 1.2 GET PARTICIPANT INFO VAN KAMPEN DIE WEL EEN POSITIEVE DEELNEMERSTATUS HEBBEN
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.2. GET PARTICIPANT INFO VAN KAMPEN DIE WEL EEN POSITIEVE DEELNEMERSTATUS HEBBEN [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################

   			$params_partinfoyes = array(
      			'sequential' 	=> 1,
      			'return' 		=> array("id","contact_id","first_name","display_name","event_id","participant_status_id", "custom_592","custom_649","custom_567","custom_568","custom_56","custom_68","custom_599","custom_600","custom_959","custom_603","custom_602"),
      			'status_id' 	=> array("Registered", "Deelgenomen", "Pending from pay later", "Pending from incomplete transaction", "Pending from waitlist", "Partially paid", "Pending refund"),
      			'contact_id' 	=> $contact_id,
      			'event_id' 		=> array('IN' => $kampids_all),	// gebruik de gevonden event_id's van de kampen van dit jaar 
      			// LET OP: mogelijk komen hier meerdere kampen als resultaat uit. Er moet dan een exception zijn om te bepalen wat we doen (bv. ene kamp geannuleerd, andere geregistreerd)
      			// FEITELIJK gebeurt dat niet en zou het juist moeten. Zodat indien een geannulleerd event wordt geedit (maar er is ook een geregisiteerd event) op het CV toch dat jaar als meegegaan komt.
      			// INDIEN STATUS_ID = 4 (geannuleerd) dan zou er een nieuwe query naar alle events van dit/dat jaar moeten komen, op zoek naar eentje wel met positieve status.
    		);
   			#if ($extdebug == 1) { watchdog('php', '<pre>params_partinfo:' . print_r($params_partinfoyes, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  			$result = civicrm_api3('Participant', 'get', $params_partinfoyes);
   			if ($extdebug == 1) { watchdog('php', '<pre>params_partinfoyes_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

   			$part_eventid 			= $result['values'][0]['event_id'];
			$part_id 				= $result['values'][0]['id'];
	   		$part_status_id			= $result['values'][0]['participant_status_id'];

			if ($extdebug == 1) { watchdog('php', '<pre>new_part_eventid:'. print_r($part_eventid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>new_part_id:'. print_r($part_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>new_part_status_id:'. print_r($part_status_id, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#####################################################################################################
		# 1.3 GET CONTACT INFO FOR SPECIFIC CONTACT_ID
		if ($extdebug == 1) { watchdog('php', '<pre>### 1.3 GET CONTACT INFO FOR SPECIFIC CONTACT_ID [contact_id: '.$contact_id.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
  
    	$params_contactinfo = array(
      		'sequential'	=> 1,
      		'return' 		=> array("id", "contact_id", "first_name", "display_name", "custom_647", "custom_474", "custom_663", "custom_376", "custom_73", "custom_74"),
      		'id' 			=> $contact_id,
    	);
   		#if ($extdebug == 1) { watchdog('php', '<pre>params_contactinfo:' . print_r($params_contactinfo, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	$result = civicrm_api3('Contact', 'get', $params_contactinfo);
   		#if ($extdebug == 1) { watchdog('php', '<pre>params_contactinfo_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		$contact_id				= $result['values'][0]['contact_id'];	
		$first_name				= $result['values'][0]['first_name'];
 		$displayname 			= $result['values'][0]['display_name'];	// displayname van contact

   		$datum_belangstelling 	= $result['values'][0]['custom_647'];
		$datum_drijf_ingevuld	= $result['values'][0]['custom_474'];
		$datum_drijf_gechecked	= $result['values'][0]['custom_663'];

		$arraydeel	 			= $result['values'][0]['custom_376'];	// welke jaren deel
		$arrayleid	 			= $result['values'][0]['custom_73'];	// welke jaren leid
		$hoevaakleid			= $result['values'][0]['custom_74'];	// hoe vaak leid

		if ($extdebug == 1) { watchdog('php', '<pre>first_name:' . print_r($first_name, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>displayname:'. print_r($displayname, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

   		if ($extdebug == 1) { watchdog('php', '<pre>datum_belangstelling:'. print_r($datum_belangstelling, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_ingevuld:'. print_r($datum_drijf_ingevuld, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>datum_drijf_gechecked:'. print_r($datum_drijf_gechecked, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel 0:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid 0:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

   		#$part_eerstexdeel 	= $result['values'][0]['custom_592']['eerstekeer'];
   		#$part_eerstexleid 	= $result['values'][0]['custom_649']['eerstekeer'];
   		#if (in_array("eerstekeer", $part_eerstexdeel)) 	{ $eerstexdeel = 'eerstekeer'; }
   		#if (in_array("eerstekeer", $part_eerstexleid)) 	{ $eerstexleid = 'eerstekeer'; }
   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eerstexdeel:' . print_r($part_eerstexdeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		#if ($extdebug == 1) { watchdog('php', '<pre>part_eerstexleid:' . print_r($part_eerstexleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		// HIER MOETEN WAT MEER CONDITIONALS WANT WORDT NU OOK UITGEVOERD VOOR DEELNEMERS
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
  			#return; //    if not, get out of here
		}
		#####################################################################################################
		# 1.4 RETRIEVE THE EVENT TYPE ID OF THE EVENT
   		if ($extdebug == 1) { watchdog('php', '<pre>### 1.4 RETRIEVE THE EVENT TYPE ID OF THE EVENT [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
   		$result = civicrm_api3('Event', 'get', array(
    		'sequential' => 1,
      		'return' => array("event_type_id"),
			'event_id' => $part_eventid, 								// eventid of specific kamp
    	));
    	$part_eventtypeid 	= $result['values'][0]['event_type_id'];
   		if ($extdebug == 1) { watchdog('php', '<pre>part_eventid:' . print_r($part_eventid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   		if ($extdebug == 1) { watchdog('php', '<pre>part_eventtypeid:' . print_r($part_eventtypeid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
    	###################################################################################################
	}

	if (in_array($groupID, array("139", "190"))) { 	// PART DEEL + PART LEID
		$entity_id = $contact_id;
	}
	if (in_array($groupID, array("103", "181"))) {	// TAB CURICULUM + TAB INTAKE
		$entity_id = $entityID;
	}
	if (in_array($groupID, array("103", "139", "190", "181"))) {

		#####################################################################################################
		# 1.5 CHECK OF DEZE PERSOON DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.5. CHECK OF '.$displayname.' DIT JAAR MEEGAAT ALS DEELNEMER OF LEIDING [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		if ($extdebug == 1) { watchdog('php', '<pre>contact_id:' . print_r($contact_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>entityID:' . print_r($entityID, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
		if ($extdebug == 1) { watchdog('php', '<pre>entity_id:' . print_r($entity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

		if (in_array($part_eventid, $kampidsdeel) AND in_array($part_status_id, $partstatusyes)) {	// INDIEN EVENTID = EVENT VOOR DEELNEMER + PART_STATUS = positief  
			$ditjaardeel = 1;
			if ($extdebug == 1) { watchdog('php', '<pre>FOUND EVENT_id ('.$part_eventid.') ARRAY! - DITJAAR MEE ALS DEEL MET '.$part_eventid.' [ditjaardeel = '.$ditjaardeel.'] [status = '.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }
		} else { $ditjaardeel = 0; }
		if (in_array($part_eventid, $kampidsleid) AND in_array($part_status_id, $partstatusyes)) {	// INDIEN EVENTID = EVENT VOOR LEIDING 	+ PART_STATUS = positief
			$ditjaarleid = 1;
			if ($extdebug == 1) { watchdog('php', '<pre>FOUND EVENT_id ('.$part_eventid.') ARRAY! - DITJAAR MEE ALS '.$part_functie.' OP '.$part_welkkamp.' [ditjaarleid = '.$ditjaarleid.'] [status = '.$part_status_id.']</pre>', NULL, WATCHDOG_DEBUG); }
		} else { $ditjaarleid = 0; }

		#####################################################################################################
		# 1.6 GET EVENT INFO TO RETREIVE HOOFDLEIDING
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.6. GET EVENT INFO TO RETREIVE HOOFDLEIDING [part_eventid: '.$part_eventid.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
    	if ($ditjaardeel == 1 OR $ditjaarleid == 1) {
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
    	}
		
		#####################################################################################################
		# 1.7 SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.7 SYNCRONISE TAGS WITH CV WHEN CV IS EMPTY [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
		if ($exttag == 1) {    	
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

			if ($extdebug == 1) { watchdog('php', '<pre>tgdeel:'. print_r($tgdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>cvdeel:'. print_r($cvdeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>tgleid:'. print_r($tgleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
			if ($extdebug == 1) { watchdog('php', '<pre>cvleid:'. print_r($cvleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		}
		#####################################################################################################
		# 1.8 DESTILATE FIRST, LAST, COUNT ETC FROM CV AND UPDATE DB
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.8 DESTILATE FIRST, LAST, COUNT ETC FROM CV AND UPDATE DB [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################

		$eerstedeel  = "";
		$laatstedeel = "";
		$eersteleid  = "";
		$laatsteleid = "";

		#$arraydeel	 = explode("", $arraydeel);
		#$arrayleid	 = explode("", $arrayleid);
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarendeel0:'. print_r($arraydeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>welkejarenleid0:'. print_r($arrayleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
		#if ($extdebug == 1) { watchdog('php', '<pre>ditkaljaar:'. print_r($ditkaljaar, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

		if ($ditjaardeel == 1) {
			$part_functie = 'deelnemer';
   			if (empty($arraydeel)) {
   				$arraydeel = array($ditkaljaar);			// ZOU EIGENLIJK NIET KALJAAR MOETEN ZIJN MAAR JAAR VAN EVENT DAT GEEDIT WORDT 
   			} else {
   				if (!in_array($ditkaljaar, $arraydeel)) {
   					array_push($arraydeel, $ditkaljaar);	// VOEG HUIDIG HAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
   				}
   			}
   		} else {
			if (in_array($ditkaljaar, $arraydeel)) {		// VERWIJDER HUIDIG HAAR TOE AAN ARRAY INDIEN HET ER INZAT
				if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_org:' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$arraydeel = array_diff($arraydeel, array($ditkaljaar));
   				if ($extdebug == 1) { watchdog('php', '<pre>arraydeel_new:' . print_r($arraydeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}
   		}
		if ($ditjaarleid == 1) {
   			if (empty($arrayleid)) { 
   				$arrayleid = array($ditkaljaar);			// ZOU EIGENLIJK NIET KALJAAR MOETEN ZIJN MAAR JAAR VAN EVENT DAT GEEDIT WORDT
   			} else {
   				if (!in_array($ditkaljaar, $arrayleid)) {
   					array_push($arrayleid, $ditkaljaar);	// VOEG HUIDIG HAAR TOE AAN ARRAY INDIEN HET ER NOG NIET INZAT
   				}
   			}
   		} else {
   			if (!in_array($ditkaljaar, $arrayleid)) {		// VERWIJDER HUIDIG HAAR TOE AAN ARRAY INDIEN HET ER INZAT
   				if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_org:' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
   				$arrayleid = array_diff($arrayleid, array($ditkaljaar));
   				if ($extdebug == 1) { watchdog('php', '<pre>arrayleid_new:' . print_r($arrayleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
			unset($welkedeel);
			$welkedeel 		= array(); 	// NOT SURE IF "" IS VALID FOR THIS FIELD
			$eerstedeel 	= "";
			$laatstedeel 	= "";
		}
		if ($welkedeel = "") {
			unset($welkedeel);
			$welkedeel 		= array();
			if ($extdebug == 1) { watchdog('php', '<pre>!!! $welkedeel van "" naar empty array:'. print_r($welkedeel, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
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
			unset($welkeleid);
			$welkeleid 		= array();	// NOT SURE IF "" IS VALID FOR THIS FIELD
			$eersteleid 	= "";
			$laatsteleid 	= "";
		}
		if ($welkeleid = "") {
			unset($welkeleid);
			$welkeleid 		= array();
			if ($extdebug == 1) { watchdog('php', '<pre>!!! $welkeleid van "" naar empty array:'. print_r($welkeleid, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }
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
    	if ($ditjaardeel == 1 OR $ditjaarleid == 1) {
 			#####################################################################################################
			# 1.9 UPDATE PARAMS_CONTACT MET EVENT INFO
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.9 UPDATE PARAMS_CONTACT MET EVENT INFO [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################
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
			#####################################################################################################
			# 1.10 UPDATE PARAMS_PARTICIPANT MET EVENT INFO
    		if ($extdebug == 1) { watchdog('php', '<pre>### 1.10 UPDATE PARAMS_PARTICIPANT MET EVENT INFO [groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
			#####################################################################################################
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
		}
		#####################################################################################################
		# 1.11 UPDATE PARAMS_CV MET EVENT INFO
    	if ($extdebug == 1) { watchdog('php', '<pre>### 1.11 UPDATE PARAMS_CV MET STATISTIEKEN[groupID: '.$groupID.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
		#####################################################################################################
    	$params_cv = array(
			'contact_type' => 'Individual',
	   		'id'		   => $contact_id,
			'first_name'   => $first_name,

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

#    	if (isset($welkedeel) AND $hoevaakdeel > 0)	{ 	// voeg welkedeel alleen toe als het niet leeg is
#    	if (isset($welkedeel))	{ 	// voeg welkedeel alleen toe als het niet leeg is
	    	$params_cv['custom_376']	= $welkedeel;
	    	$params_cv['custom_382']	= $hoevaakdeel;
	    	$params_cv['custom_842']	= $eerstedeel;
	    	$params_cv['custom_843']	= $laatstedeel;
			if ($extdebug == 1) { watchdog('php', '<pre>array_add_welkedeel_376:' . print_r($welkedeel, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
#  		}
#   		if (isset($welkeleid) AND $hoevaakleid > 0)	{ 	// voeg welkeleid alleen toe als het niet leeg is
#   		if (isset($welkeleid))	{ 	// voeg welkeleid alleen toe als het niet leeg is
	    	$params_cv['custom_73']		= $welkeleid;
	    	$params_cv['custom_74']		= $hoevaakleid;
	    	$params_cv['custom_844']	= $eersteleid;
	    	$params_cv['custom_845']	= $laatsteleid;
			if ($extdebug == 1) { watchdog('php', '<pre>array_add_welkeleid_073:' . print_r($welkeleid, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
#    	}

    	if (in_array($groupID, array("139", "190"))) {	// PART DEEL + PART LEID
			if ($extcv == 1) {
				$result = civicrm_api3('Contact', 'create', $params_cv);
   				if ($extdebug == 1) { watchdog('php', '<pre>params_cv:' . print_r($params_cv, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>params_cv EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
			}
   		}
   		if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION CV [groupID: '.$groupID.'] [op: '.$op.'] ***</pre>', null, WATCHDOG_DEBUG); }
		// ************************************************************************************************************
		// 2. EXTENTION VOG
		// ************************************************************************************************************
   		if ($extvog == 1 AND $ditjaarleid AND ($groupID == 190 OR $groupID == 181)) {	// PART LEID & TAB INTAKE

			if ($extdebug == 1) { watchdog('php', '<pre>### 2. START EXTENSION VOG [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
      		#if ($extdebug == 1) { watchdog('php', '<pre>vogrecent:'. print_r($vogrecent, TRUE) .'</pre>', NULL, WATCHDOG_DEBUG); }

			// ************************************************************************************************************
			// 2.1 BEPAAL OF DE VOG NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN VOG MOET WORDEN AANGEVRAAGD 
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.1 BEPAAL OF DE VOG NOG GOED IS, HET DE EERSTE KEER IS, OF ER OPNIEUW EEN VOG MOET WORDEN AANGEVRAAGD</pre>', NULL, WATCHDOG_DEBUG); }
			// ************************************************************************************************************
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

       			if ($extdebug == 1) { watchdog('php', '<pre>strtotime(vogrecent):' . print_r(strtotime($vogrecent), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
       			if ($extdebug == 1) { watchdog('php', '<pre>strtotime(grensvognoggoed):' . print_r(strtotime($grensvognoggoed), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
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
    		
			// ************************************************************************************************************
			// 2.2 CHECK OF ER EEN VOG VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.2 CHECK OF ER EEN VOG VERZOEK IS DAT VALT BINNEN HET HUIDIGE FISCALE JAAR</pre>', NULL, WATCHDOG_DEBUG); }
			// ************************************************************************************************************
      		$verzoekditjaar = 0;
			if (strtotime($part_vogverzocht) >= strtotime($fiscalyear_start) AND strtotime($part_vogverzocht) <= strtotime($fiscalyear_end)) {
				// alleen indien vogverzocht binnen huidige fiscale jaar valt
				$verzoekditjaar = 1;
			}
			if ($extdebug == 1) { watchdog('php', '<pre>verzoekditjaar:' . print_r($verzoekditjaar, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

			// ************************************************************************************************************
			// 2.3 WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ (indien er een part_vog_datum is)
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.3 WERK DE GEGEVENS IN TAB INTAKE OVER DE VOG BIJ (indien er een part_vog_datum is</pre>', NULL, WATCHDOG_DEBUG); }
			// ************************************************************************************************************
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
   			// ************************************************************************************************************
			// 2.4 WERK DE GEGEVENS IN PART LEID VOG BIJ
			if ($extdebug == 1) { watchdog('php', '<pre>--- 2.4 WERK DE GEGEVENS IN PART LEID VOG BIJ</pre>', NULL, WATCHDOG_DEBUG); }
			// ************************************************************************************************************
   			if ($extvog == 1 AND $vognodig) { // vognodig zou altijd gevuld moeten zijn
				$params_vog_part = array(
      				'debug'        => 1,
   					'id'           => $part_id,
					'event_id'	   => $part_eventid,
   					#'contact_id'   => $contact_id,
   					#'custom_586'   => $vognodig,
   					#'custom_605'   => $vognodig,
   					'custom_990'   => $vognodig,
    			);
    			if ($vognodig == 'noggoed') { 
					#$params_vog_part['custom_603'] = $vogrecent;
					#$params_vog_part['custom_602'] = $vogkenmerk;
				}
   				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_part:' . print_r($params_vog_part, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1)	{ $result = civicrm_api3('Participant', 'create', $params_vog_part); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_part_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>params_vog_part EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
    			#$params_participant['custom_586'] = $vognodig;
    			#$result = civicrm_api3('Participant', 'create', $params_participant);
   				#if ($extdebug == 1) { watchdog('php', '<pre>params_participant_nodig:' . print_r($params_participant, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_participant nodig EXECUTED [groupID: '.$groupID.']</pre>', NULL, WATCHDOG_DEBUG); }
   			}

			if ($extvog == 1 AND ($groupID == 190 OR $groupID == 181) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw","noggoed"))) {
			// ************************************************************************************************************
			// 3 GET ACTIVITIES MBT. VOG
			// ************************************************************************************************************
   				if ($extdebug == 1) { watchdog('php', '<pre>### 3. VOG ACTIVITIES [GET] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 3.1 GET ACTIVITIES 'VOG VERZOEK'
				// ************************************************************************************************************
   				$params_vog_activity_verzoek_get = array(		// zoek activities 'VOG verzoek'
  					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG verzoek",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				);
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
  				}
				// ************************************************************************************************************
				// 3.2 GET ACTIVITIES 'VOG AANVRAAG'
				// ************************************************************************************************************
  				$params_vog_activity_aanvraag_get = array(		// zoek activities 'VOG aanvraag'
   					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG aanvraag",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				);
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
  				}
				// ************************************************************************************************************
				// 3.3 GET ACTIVITIES 'VOG ONTVANGST'
				// ************************************************************************************************************
  				$params_vog_activity_ontvangst_get = array(		// zoek activities 'VOG ontvangst'
   					'sequential' => 1,
  					'return' => array("id", "activity_date_time", "status_id", "subject"),
  					'target_contact_id' => $contact_id,
  					'activity_type_id' => "VOG ontvangst",
  					'activity_date_time' => array('BETWEEN' => array("$fiscalyear_start", "$fiscalyear_end")),
  				);
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
  				}
			}

			if ($extvog == 1 AND ($groupID == 190 OR $groupID == 181) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
			// ************************************************************************************************************
			// 4. BEPAAL DE JUISTE DATUMS VOOR ACTIVITIES AANVRAAG & ONTVANGST
			// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 4. VOG ACTIVITIES [DEFINE NEW DATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 4.1 BEPAAL (NIEUWE) DATUM ACTIVITY AANVRAAG
				// ************************************************************************************************************
				if ($part_vogingediend) {
					$datum_aanvraag  = $part_vogingediend;											// ZET DATUM AANVRAAG VAN ACTIVITY GELIJK AAN AANVRAAGDATUM
				} else {
					$newdate		 = strtotime ( '+30 day' , strtotime ( $part_vogverzocht ) ) ;	// ZET DEADLINE AANVRAAG OP 30 DAGEN NA VERZOEK
					$datum_aanvraag  = date ( 'Y-m-d H:i:s' , $newdate );
				}
				// *********** zet even tijdelijk alle datumaanvraag deadlines op 13 dagen na nu (om herinneringen te laten sturen)
				if (in_array($vogaanvraag_activity_status, array(4,5,8))) {
					if ($extdebug == 1) { watchdog('php', '<pre>datum_aanvraag_0:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if (in_array($vogaanvraag_activity_status, array(4))) {							// ZET ACTIVITEITEN MET STATUS HERINNERD OP 14 DAGEN VANAF NU
						$newdate 	= strtotime ('+14 day' , strtotime ($todaydatetime) ) ;
					}
					if (in_array($vogaanvraag_activity_status, array(5))) {							// ZET ACTIVITEITEN MET STATUS ONBEREIKBAAR OP 7 DAGEN VANAF NU
						$newdate 	= strtotime ('+7 day' , strtotime ($todaydatetime) ) ;
					}
					if (in_array($vogaanvraag_activity_status, array(8))) {							// ZET ACTIVITEITEN MET STATUS VERLOPEN OP VANDAAG
						$newdate 	= strtotime ( '-1 hour' , strtotime ($todaydatetime) ) ;
					}
					$datum_aanvraag = date ( 'Y-m-d H:i:s' , $newdate );
					if ($extdebug == 1) { watchdog('php', '<pre>datum_aanvraag_1:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// als datum aanvraag > start kamp is, dan scheduled datum aanvraag = start kamp - 1wk 
				if (strtotime($datum_aanvraag) >= strtotime($event_startdate))	{ $datum_aanvraag  = date($event_startdate, strtotime("-1 week")); } 

				// ************************************************************************************************************
				// 4.2 BEPAAL (NIEUWE) DATUM ACTIVITY AANVRAAG
				// ************************************************************************************************************
				if ($part_vogontvangst) { // VOG-ontvangst is datum van ontvangst, indien leeg dan datum ingediend of datum verzocht (zou ook datum vog kunnen zijn)
					$datum_ontvangst = $part_vogontvangst;											// ZET DATUM ONTVANGST VAN ACTIVITY GELIJK AAN ONTVANGSTDATUM
				} elseif ($part_vogingediend) {
					$newdate		 = strtotime ( '+30 day' , strtotime ( $part_vogingediend ) ) ;	// ZET 'DEADLINE' ONTVANGST OP 30 DAGEN NA INDIENEN AANVRAAG
					$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
				} else {																			// ZET EEN FICTIEVE DATUM VOOR ACTIVITY ONTVANGST 6 WEKEN NA VERZOEKDATUM
					$newdate		 = strtotime ( '+42 day' , strtotime ( $part_vogverzocht ) ) ;
					$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
				}
				// ***********  zet even tijdelijk alle datumontvangst deadlines op 20 dagen na nu (om herinneringen te laten sturen)
				if (in_array($vogontvangst_activity_status, array(4,5,8))) {
					if ($extdebug == 1) { watchdog('php', '<pre>datum_ontvangst_0:' . print_r($datum_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if (in_array($vogontvangst_activity_status, array(4))) {						// ZET ACTIVITEITEN MET STATUS HERINNERD OP 21 DAGEN VANAF NU
						$newdate 	= strtotime ('+21 day' , strtotime ($todaydatetime) ) ;
					}
					if (in_array($vogontvangst_activity_status, array(5,8))) {						// ZET ACTIVITEITEN MET STATUS ONBEREIKBAAR OP 14 DAGEN VANAF NU
						$newdate 	= strtotime ('+14 day' , strtotime ($todaydatetime) ) ;
					}
					if (!in_array($vogaanvraag_activity_status, array(2))) {						// INDIEN NOG GEEN AANVRAAG DAN STATUS ACTIVITEIT ONTVANGST 30 DAGEN NA NU
						$newdate 	= strtotime ('+30 day' , strtotime ($todaydatetime) ) ;
					}
					$datum_ontvangst = date ( 'Y-m-d H:i:s' , $newdate );
					if ($extdebug == 1) { watchdog('php', '<pre>datum_ontvangst_1:' . print_r($datum_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// als datum aanvraag > start kamp is, dan scheduled datum ontvangst = start kamp - 1wk
				if (strtotime($datum_ontvangst)	>= strtotime($event_startdate))	{ $datum_ontvangst  = date($event_startdate, strtotime("-1 week")); }
				if ($extdebug == 1) { watchdog('php', '<pre>1. part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>2. part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>3. part_vogontvangst:' . print_r($part_vogontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>4. part_vogdatum:' . print_r($part_vogdatum, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>*. scheduled_datum_aanvraag:' . print_r($datum_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>*. scheduled_datum_ontvangst:' . print_r($datum_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			}

			if ($extvog == 1 AND ($groupID == 190 OR $groupID == 181) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
			// ************************************************************************************************************
			// 5. CREATE ACTIVITIES
			// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 5. VOG ACTIVITIES [CREATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 5.1 CREATE AN ACTIVITY 'VERZOEK' ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				if ($extdebug == 1) { watchdog('php', '<pre>--- 5.1 CREATE AN ACTIVITY VERZOEK ALS VOG AANVRAAG IS VERZOCHT EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				if (empty($vogverzoek_activity_id) AND  $part_vogverzocht) {
  					$params_vog_activity_create_verzoek = array(		// update VOG aanvraag naar Completed als VOG ontvangst Completed is
  						"source_contact_id"		=> 1,
  						"target_id"				=> $contact_id,
  						'status_id'				=> "Completed",
  						'activity_type_id' 		=> "VOG verzoek",
  						'subject' 				=> "VOG aanvraag verzocht",
  						'activity_date_time'	=> $part_vogverzocht,
  					);
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek:' . print_r($params_vog_activity_create_verzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extvog == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) { $result = civicrm_api3('Activity', 'create', $params_vog_activity_create_verzoek); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_verzoek_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if (empty($vogverzoek_activity_id))		{ $vogverzoek_activity_id		= key($result['values']); }
					if (empty($vogverzoek_activity_status))	{ $vogverzoek_activity_status	= 1; }
					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_id2:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogverzoek_activity_status2:' . print_r($vogverzoek_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// ************************************************************************************************************
				// 5.2 CREATE AN ACTIVITY 'AANVRAAG' ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				if ($extdebug == 1) { watchdog('php', '<pre>--- 5.2 CREATE AN ACTIVITY AANVRAAG ALS VOG AANVRAAG IS VERZOCHT OF INGEDIEND EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
  				if (empty($vogaanvraag_activity_id) AND $datum_aanvraag AND ($part_vogverzocht OR $part_vogingediend)) {
  					$params_vog_activity_create_aanvraag = array(
  						"source_contact_id"		=> 1,
   						"target_id"				=> $contact_id,
  						'status_id'				=> "Pending",
  						'activity_type_id' 		=> "VOG aanvraag",
  						'subject' 				=> "VOG aanvraag ingediend",
  						'activity_date_time'	=> $datum_aanvraag,
  					);
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag:' . print_r($params_vog_activity_create_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extvog == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) { $result = civicrm_api3('Activity', 'create', $params_vog_activity_create_aanvraag); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_aanvraag_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if (empty($vogaanvraag_activity_id))		{ $vogaanvraag_activity_id		= key($result['values']); }
					if (empty($vogaanvraag_activity_status))	{ $vogaanvraag_activity_status	= 1; }
					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_id2:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogaanvraag_activity_status2:' . print_r($vogaanvraag_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// ************************************************************************************************************
				// 5.3 CREATE AN ACTIVITY 'ONTVANGST' ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY
				if ($extdebug == 1) { watchdog('php', '<pre>--- 5.3 CREATE AN ACTIVITY ONTVANGST ALS VOG AANVRAAG IS INGEDIEND OF ONTVANGST BEVESTIGD EN ER IS NOG GEEN BIJBEHORENDE ACTIVITY</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				if (empty($vogontvangst_activity_id) AND $datum_ontvangst AND ($part_vogverzocht OR $part_vogingediend)) {
  					$params_vog_activity_create_ontvangst = array(		// update VOG aanvraag naar Completed als VOG ontvangst Completed is
  						"source_contact_id"		=> 1,
  						"target_id"				=> $contact_id,
  						'status_id'				=> "Pending",
  						'activity_type_id' 		=> "VOG ontvangst",
  						'subject' 				=> "VOG ontvangst bevestigd",
  						'activity_date_time'	=> $datum_ontvangst,
  					);
  					if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst:' . print_r($params_vog_activity_create_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
  					if ($extvog == 1 AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) { $result = civicrm_api3('Activity', 'create', $params_vog_activity_create_ontvangst); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_create_ontvangst_result_values:' . print_r(key($result['values']), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if (empty($vogontvangst_activity_id))		{ $vogontvangst_activity_id			= key($result['values']); }
					if (empty($vogontvangst_activity_status))	{ $vogontvangst_activity_status		= 1; }
					if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_id2:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>vogontvangst_activity_status2:' . print_r($vogontvangst_activity_status, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
			}

			if ($extvog == 1 AND ($groupID == 190 OR $groupID == 181) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw"))) {
			// ************************************************************************************************************
			// 6. UPDATE ACTIVITIES
			// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 6. VOG ACTIVITIES [UPDATE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT AANVRAAG
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.1 BEPAAL (NIEUWE) STATUS ACTIVITEIT AANVRAAG</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				$diffsinceverzoek	= date_diff(date_create($part_vogverzocht),date_create($todaydatetime));
				$dayssinceverzoek	= $diffsinceverzoek->format('%a');
				if ($dayssinceverzoek >= 0  AND $dayssinceverzoek < 14)				{ $status_aanvraag  = "Pending"; }
				if ($dayssinceverzoek >= 14 AND $dayssinceverzoek < 21)				{ $status_aanvraag  = "Left Message"; }
				if ($dayssinceverzoek >= 21 AND $dayssinceverzoek < 30)				{ $status_aanvraag  = "Unreachable"; }
				if ($dayssinceverzoek >= 30)										{ $status_aanvraag  = "No_show"; }
				#if ($dayssinceverzoek >= 30)										{ $status_aanvraag  = "No_Show"; }
				#if ($dayssinceverzoek >= 30)										{ $status_aanvraag  = "No Show"; }
				#if ($dayssinceverzoek >= 30)										{ $status_aanvraag  = "Pending"; }

				// LET OP: DE VOLGENDE 2 REGELS NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if (strtotime($part_vogingediend) >= strtotime($fiscalyear_start))	{ // hier een between van maken (of liever nog een functie)
					#if ($extdebug == 1) { watchdog('php', '<pre>A. part_vogingediend:' . print_r(strtotime($part_vogingediend), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>B. fiscalyear_start:' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>STATUS AANVRAAG AANGEPAST VAN: '.$status_aanvraag.' NAAR Completed (omdat part_vogingedien: '.$part_vogingediend.' >= '.$fiscalyear_start.') ---</pre>', NULL, WATCHDOG_DEBUG); }
					$status_aanvraag  = "Completed";
				}
				if (strtotime($part_vogdatum)	  >= strtotime($fiscalyear_start))	{ // hier een between van maken (of liever nog een functie)
					#if ($extdebug == 1) { watchdog('php', '<pre>A. part_vogdatum:' . print_r(strtotime($part_vogdatum), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					#if ($extdebug == 1) { watchdog('php', '<pre>B. fiscalyear_start:' . print_r(strtotime($fiscalyear_start), TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
					if ($extdebug == 1) { watchdog('php', '<pre>STATUS AANVRAAG AANGEPAST VAN: '.$status_aanvraag.' NAAR Completed (omdat part_vogdatum: '.$part_vogdatum.' >= '.$fiscalyear_start.') ---</pre>', NULL, WATCHDOG_DEBUG); }
					$status_aanvraag  = "Completed";
				}	// als part_vogdatum binnen huidige fiscal year valt kan activity op completed (nu alleen later dan fiscal year start)
				if ($extdebug == 1) { watchdog('php', '<pre>a. part_vogverzocht:' . print_r($part_vogverzocht, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>b. diffsinceverzoek:' . print_r($diffsinceverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>b. dagen_sinds_verzoek:' . print_r($dayssinceverzoek, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>c. (new) status_aanvraag:' . print_r($status_aanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				// 6.2 BEPAAL (NIEUWE) STATUS ACTIVITEIT ONTVANGST
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.2 BEPAAL (NIEUWE) STATUS ACTIVITEIT ONTVANGST</pre>', NULL, WATCHDOG_DEBUG); }
				// ************************************************************************************************************
				$diffsinceaanvraag	= date_diff(date_create($part_vogingediend),date_create($todaydatetime));
				$dayssinceaanvraag	= $diffsinceaanvraag->format('%a');
				if ($dayssinceaanvraag >= 0  AND $dayssinceaanvraag < 28)			{ $status_ontvangst = "Pending"; }
				if ($dayssinceaanvraag >= 28 AND $dayssinceaanvraag < 42)			{ $status_ontvangst = "Left Message"; }
				if ($dayssinceaanvraag >= 42)										{ $status_ontvangst = "Unreachable"; }
				if ($dayssinceverzoek  >= 21 AND $status_ontvangst == "Pending")	{ $status_ontvangst = "Left Message"; } // Als aanvraag Unreachable of No Show is -> schedule dan alsnog een reminder rond geplande ontvangstdatum
				if (strtotime($todaydatetime) > strtotime($event_startdate))		{ $status_ontvangst = "No_show"; }		// No show nadat de startdag van kamp gepasseerd is
				if (!in_array($vogaanvraag_activity_status, array(2))) 				{ $status_ontvangst	= "Draft"; }		// Maak status Activiteit ONTVANGST = Draft als status AANVRAAG nog niet Completed is (civirules proof?) 
				// LET OP: DE VOLGENDE 1 REGEL NOG EVEN HEEL ERG GOED DUBBELCHECKEN!!!
				if (strtotime($part_vogdatum) >= strtotime($fiscalyear_start))		{ $status_ontvangst = "Completed"; } 	// als part_vogdatum binnen huidige fiscal year valt kan activity op completed

				if ($extdebug == 1) { watchdog('php', '<pre>a. part_vogingediend:' . print_r($part_vogingediend, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				#if ($extdebug == 1) { watchdog('php', '<pre>b. diffsinceaanvraag:' . print_r($diffsinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>b. dagen_sinds_aanvraag:' . print_r($dayssinceaanvraag, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extdebug == 1) { watchdog('php', '<pre>c. (new) status_ontvangst:' . print_r($status_ontvangst, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }

				// ************************************************************************************************************
				// 6.3 UPDATE ACTIVITY AANVRAAG STATUS N.A.V. DAYS SINCE...	
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.3 UPDATE ACTIVITY AANVRAAG STATUS N.A.V. DAYS SINCE...</pre>', NULL, WATCHDOG_DEBUG); }	
				// ************************************************************************************************************
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
				#if ($extvog == 1)	{ $result = civicrm_api3('Activity', 'create', $params_vog_activity_change_aanvraag); }
				#if ($extdebug == 1) { watchdog('php', '<pre>params_vog_activity_change_aanvraag_result:' . print_r($result, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
				}
				// ************************************************************************************************************
				// 6.4 UPDATE ACTIVITY ONTVANGST STATUS NAV DAYS SINCE...
				if ($extdebug == 1) { watchdog('php', '<pre>--- 6.4 UPDATE ACTIVITY ONTVANGST STATUS NAV DAYS SINCE...</pre>', NULL, WATCHDOG_DEBUG); }	
				// ************************************************************************************************************
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
			}

			if ($extvog == 1 AND ($groupID == 190 OR $groupID == 181) AND in_array($vognodig, array("eerstex", "elkjaar", "opnieuw","noggoed"))) {
			// ************************************************************************************************************
			// 7. DELETE ACTIVITIES (indien ze waren aangemaakt maar VOG nog goed was - deze actie zou niet nodig hoeven zijn)
			// ************************************************************************************************************
				if ($extdebug == 1) { watchdog('php', '<pre>### 7. VOG ACTIVITIES [DELETE] [groupID: '.$groupID.'] [op: '.$op.'] ###</pre>', NULL, WATCHDOG_DEBUG); }
				if ($extvog == 1 AND in_array($vognodig, array("noggoed"))) {
			    	if ($vogverzoek_activity_status != 2 	AND $vogverzoek_activity_id)	{
			    		// delete een evt aangemaakte acvitity verzoek nooit
			    		#$result = civicrm_api3('Activity', 'delete', array('id' => $vogverzoek_activity_id,));
			    		#if ($extdebug == 1) { watchdog('php', '<pre>ACTIVITY VERWIJDERD vogverzoek:' . print_r($vogverzoek_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	}
			    	if ($vogaanvraag_activity_status != 2 	AND $vogaanvraag_activity_id)	{
			    		$result = civicrm_api3('Activity', 'delete', array('id' => $vogaanvraag_activity_id,));
			    		if ($extdebug == 1) { watchdog('php', '<pre>ACTIVITY VERWIJDERD vogaanvraag:' . print_r($vogaanvraag_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	}
			    	if ($vogontvangst_activity_status != 2 	AND $vogontvangst_activity_id)	{
			    		$result = civicrm_api3('Activity', 'delete', array('id' => $vogontvangst_activity_id,));
			    		if ($extdebug == 1) { watchdog('php', '<pre>ACTIVITY VERWIJDERD vogontvangst:' . print_r($vogontvangst_activity_id, TRUE) . '</pre>', NULL, WATCHDOG_DEBUG); }
			    	}
				}

			}
			if ($extdebug == 1) { watchdog('php', '<pre>*** END EXTENSION VOG [groupID: '.$groupID.'] [op: '.$op.'] [kampleider: '.$displayname.'] ***</pre>', NULL, WATCHDOG_DEBUG); }
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