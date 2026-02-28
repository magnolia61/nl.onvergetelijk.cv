<?php

			#####################################################################################################
			# 6.1 VOEG HOOFDLEIDING TOE AAN ACL GROEP VAN HET KAMP INDIEN HANDMATIG TOEGEVOEGD AAN KAMPSTAF ACL
			if ($extdebug >= 0) { watchdog('php','<pre>### 6.1 VOEG HOOFDLEIDING TOE AAN ACL GROEP VAN HET KAMP INDIEN HANDMATIG TOEGEVOEGD AAN KAMPSTAF ACL ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################			
			$params_groupcontact_staf_get = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
				'select' => [
					'row_count',
					'id', 
					'group_id', 
					'group_id:name',
				],
				'where' => [
					['group_id', '=', 456], 			// M61: hardcoded id of (manual) ACL group ditjaar_kampstaf
					['contact_id', '=', $contact_id],
					['status', '=', 'Added'],
				],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_staf_get: '.print_r($params_groupcontact_staf_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_staf_get);
			$group_staf = $result->count();
			if ($extdebug >= 2) { watchdog('php','<pre>Deel van group staf: '.$group_staf.'</pre>',NULL,WATCHDOG_DEBUG); }

			if ($part_welkkampleid == 'KK1')			{ $aclgroupkamp = 941;}
			if ($part_welkkampleid == 'KK2')			{ $aclgroupkamp = 942;}
			if ($part_welkkampleid == 'BK1')			{ $aclgroupkamp = 943;}
			if ($part_welkkampleid == 'BK2')			{ $aclgroupkamp = 944;}
			if ($part_welkkampleid == 'TK1')			{ $aclgroupkamp = 945;}
			if ($part_welkkampleid == 'TK2')			{ $aclgroupkamp = 946;}
			if ($part_welkkampleid == 'JK1')			{ $aclgroupkamp = 947;}
			if ($part_welkkampleid == 'JK2')			{ $aclgroupkamp = 948;}
			if ($part_welkkampleid == 'TOP')			{ $aclgroupkamp = 949;}
			if ($part_welkkampleid == 'bestuurstaken')	{ $aclgroupkamp = 455;}

			$params_groupcontact_kamp_get = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
				'select' => [
					'row_count',
					'id', 
					'group_id', 
					'group_id:name',
				],
				'where' => [
					['group_id', 	'=', $aclgroupkamp], // M61: hardcoded id of ACL group Ditjaar_kampstaf
					['contact_id', 	'=', $contact_id],
	// 				['status', 		'=', 'Added'],
				],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_kamp_get: '.print_r($params_groupcontact_kamp_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_kamp_get);
			if ($extdebug >= 4) { watchdog('php','<pre>result_groupcontact_kamp_get: '.print_r($result,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$group_kamp = $result->count();
			if ($extdebug >= 2) { watchdog('php','<pre>Deel van group kamp: '.$group_kamp.'</pre>',NULL,WATCHDOG_DEBUG); }

			#####################################################################################################
			# INDIEN IN KAMPSTAF EN LEIDING VAN DIT KAMP VOEG DAN OOK TOE AAN ACL GROEP VAN DIT KAMP
			#####################################################################################################
			if ($group_staf == 1 AND $group_kamp == 0 AND in_array($part_functie, array('hoofdleiding','bestuurslid','kampstaf'))) {
				$params_groupcontact_kamp_create = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'group_id' 		=> $aclgroupkamp, 
						'contact_id' 	=> $contact_id,
						'status' 		=> 'Added',
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_kamp_create: '.print_r($params_groupcontact_kamp_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'create', $params_groupcontact_kamp_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>Toegevoegd aan ACL groep hoofdleiding van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}

			#####################################################################################################
			# INDIEN IN KAMPSTAF EN (WEER) HL VAN DIT KAMP VOEG DAN OOK TOE AAN ACL GROEP VAN DIT KAMP
			#####################################################################################################
			if ($group_staf == 1 AND $group_kamp == 1 AND in_array($part_functie, array('hoofdleiding','bestuurslid','kampstaf'))) {
				$params_groupcontact_kamp_update = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Added',
						],
					'where' => [
						['group_id', 	'=', $aclgroupkamp], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_kamp_update: '.print_r($params_groupcontact_kamp_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_kamp_update);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>(weer) Toegevoegd aan ACL groep hoofdleiding van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# INDIEN NIET IN KAMPSTAF EN LEIDING VAN DIT KAMP VERWIJDER DAN OOK UIT ACL GROEP VAN DIT KAMP
			#####################################################################################################
			if ($group_staf == 0 AND $group_kamp == 1 AND in_array($part_functie, array('hoofdleiding','bestuurslid','kampstaf'))) {
				$params_groupcontact_kamp_remove = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Removed',
						],
					'where' => [
						['group_id', 	'=', $aclgroupkamp], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_kamp_remove: '.print_r($params_groupcontact_kamp_remove,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_kamp_remove);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>Verwijderd uit ACL groep hoofdleiding van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			#####################################################################################################
