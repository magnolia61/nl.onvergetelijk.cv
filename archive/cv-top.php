<?php

if ($welkkampkort == 'TOP' AND $diteventdeelyes == 1 AND $ditjaardeelyes == 1) {
	#####################################################################################################
	# 3.5a CHECK IF DEELNEMER TOP AL IN DE ACL GROEP ZIT VAN DITJAAR DEEL TOP
	if ($extdebug >= 0) { watchdog('php','<pre>### 3.5a CHECK IF DEELNEMER TOP AL IN DE ACL GROEP ZIT VAN DITJAAR DEEL TOP ###</pre>',NULL,WATCHDOG_DEBUG); }
	#####################################################################################################
	if ($welkkampkort == 'TOP')	{ $aclgrouptopkamp = 1756;}
	// M61: hardcoded id of ACL group ditjaar_deel_top
	$params_groupcontact_get = [
		'checkPermissions' => FALSE,
		'debug' => $apidebug,				
		'select' => [
			'row_count',
			'id', 
			'group_id', 
			'group_id:name',
		],
		'where' => [
			['group_id', 	'=', $aclgrouptopkamp],
			['contact_id', 	'=', $contact_id],
		],
	];
	if ($extdebug >= 4) { watchdog('php','<pre>params_groupcontact_get: '.print_r($params_groupcontact_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_get);
	if ($extdebug >= 4) { watchdog('php','<pre>result_groupcontact_get: '.print_r($result,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	$group_topkamp = $result->count();
	if ($extdebug >= 2) { watchdog('php','<pre>Deel van acl groep ditjaar_deel_topkamp: '.$group_topkamp.'</pre>',NULL,WATCHDOG_DEBUG); }
	#####################################################################################################
	# 3.5b VOEG DEELNEMERS TOPKAMP TOE AAN ACL GROEP DITJAAR DEEL TOPKAMP
	if ($extdebug >= 0) { watchdog('php','<pre>### 3.5b VOEG DEELNEMERS TOPKAMP TOE AAN ACL GROEP DITJAAR DEEL TOPKAMP ###</pre>',NULL,WATCHDOG_DEBUG); }
	#####################################################################################################	
	if ($welkkampkort == 'TOP' AND $ditjaardeelyes == 1 AND $group_topkamp == 0 AND in_array($part_functie, array('deelnemer'))) {
		$params_groupcontact_create = [
			'checkPermissions' => FALSE,
			'debug' => $apidebug,
			'values' => [
				'group_id' 		=> $aclgrouptopkamp,
				'contact_id' 	=> $contact_id,
				'status' 		=> 'Added',
			],
		];
		if ($extdebug >= 4) { watchdog('php','<pre>params_groupcontact_create: '.print_r($params_groupcontact_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
  		if ($extwrite == 1) {
			$result = civicrm_api4('GroupContact', 'create', $params_groupcontact_create);
		}
		if ($extdebug >= 2) { watchdog('php','<pre>Toegevoegd aan ACL groep ditjaar_deel_topkamp</pre>',NULL,WATCHDOG_DEBUG); }
	}
	#####################################################################################################
	# 3.5c INDIEN (WEER) MEE MET TOPKAMP VOEG DAN OOK TOE AAND ACL GROEP DITJAAR DEEL TOPKAMP
	if ($extdebug >= 0) { watchdog('php','<pre>### 3.5c INDIEN (WEER) MEE MET TOPKAMP VOEG DAN OOK TOE AAND ACL GROEP DITJAAR DEEL TOPKAMP ###</pre>',NULL,WATCHDOG_DEBUG); }
	#####################################################################################################
	if ($welkkampkort == 'TOP' AND $ditjaardeelyes == 1 AND $group_topkamp == 1 AND in_array($part_functie, array('deelnemer'))) {
		$params_groupcontact_update = [
			'checkPermissions' => FALSE,
			'debug' => $apidebug,
			'values' => [
				'status' => 'Added',
			],
			'where' => [
				['group_id', 	'=', $aclgrouptopkamp], 
				['contact_id', 	'=', $contact_id],
			],
		];
		if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_update: '.print_r($params_groupcontact_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
  		if ($extwrite == 1) {
			$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_update);
		}
		if ($extdebug >= 2) { watchdog('php','<pre>(weer) Toegevoegd aan ACL groep ditjaar_deel_topkamp</pre>',NULL,WATCHDOG_DEBUG); }
	}
	#####################################################################################################
	# 3.5d INDIEN NIET MEE MET TOPKAMP DIT JAAR
	if ($extdebug >= 0) { watchdog('php','<pre>### 3.5d INDIEN NIET MEE MET TOPKAMP DIT JAAR ###</pre>',NULL,WATCHDOG_DEBUG); }
	#####################################################################################################
	if ($ditjaardeelnot == 1 AND $group_topkamp == 1) {
		$params_groupcontact_remove = [
			'checkPermissions' => FALSE,
			'debug' => $apidebug,
			'values' => [
				'status' => 'Removed',
			],
			'where' => [
				['group_id', 	'=', $aclgrouptopkamp],
				['contact_id', 	'=', $contact_id],
			],
		];
		if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_remove: '.print_r($params_groupcontact_remove,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
  		if ($extwrite == 1) {
			$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_remove);
		}
		if ($extdebug >= 2) { watchdog('php','<pre>Verwijderd uit ACL groep ditjaar_deel_topkamp</pre>',NULL,WATCHDOG_DEBUG); }
	}	
}