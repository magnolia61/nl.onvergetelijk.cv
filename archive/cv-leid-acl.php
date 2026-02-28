<?php

			#####################################################################################################
			# 5.1 VOEG LEIDING TOE AAN ACL GROEP GEDRAG VAN DIT KAMP INDIEN AANGEGEVEN DAT DEZE PERSOON HOOFD GEDRAG IS
			if ($extdebug >= 0) { watchdog('php','<pre>### 5.1 VOEG LEIDING TOE AAN ACL GROEP GEDRAG VAN DIT KAMP INDIEN AANGEGEVEN DAT DEZE PERSOON HOOFD GEDRAG IS ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################			
			if ($part_welkkampleid == 'KK1')	{ $aclgroupgedrag = 1766;}
			if ($part_welkkampleid == 'KK2')	{ $aclgroupgedrag = 1767;}
			if ($part_welkkampleid == 'BK1')	{ $aclgroupgedrag = 1768;}
			if ($part_welkkampleid == 'BK2')	{ $aclgroupgedrag = 1769;}
			if ($part_welkkampleid == 'TK1')	{ $aclgroupgedrag = 1770;}
			if ($part_welkkampleid == 'TK2')	{ $aclgroupgedrag = 1771;}
			if ($part_welkkampleid == 'JK1')	{ $aclgroupgedrag = 1772;}
			if ($part_welkkampleid == 'JK2')	{ $aclgroupgedrag = 1773;}
			if ($part_welkkampleid == 'TOP')	{ $aclgroupgedrag = 1774;}

			$params_groupcontact_gedrag_get = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
					'select' => [
						'row_count',
					'id', 
					'group_id', 
					'group_id:name',
					],
					'where' => [
					['group_id', 	'=', $aclgroupgedrag], // M61: hardcoded id of ACL group Ditjaar_kampstaf
					['contact_id', 	'=', $contact_id],
					],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_gedrag_get: '.print_r($params_groupcontact_gedrag_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_gedrag_get);
			if ($extdebug >= 4) { watchdog('php','<pre>result_groupcontact_gedrag_get: '.print_r($result,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$group_gedrag = $result->count();
			if ($extdebug >= 2) { watchdog('php','<pre>Deel van groep gedrag van kamp: '.$group_gedrag.'</pre>',NULL,WATCHDOG_DEBUG); }

			#####################################################################################################
			# INDIEN IN EVENT ROL GEDRAG VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP GEDRAG VAN DIT KAMP
			#####################################################################################################
			if ($event_hoofd_gedrag_id == $contact_id AND $group_gedrag == 0) {
				$params_groupcontact_gedrag_create = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'group_id' 		=> $aclgroupgedrag, 
						'contact_id' 	=> $contact_id,
						'status' 		=> 'Added',
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_gedrag_create: '.print_r($params_groupcontact_gedrag_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'create', $params_groupcontact_gedrag_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>Toegevoegd aan ACL groep gedrag van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# INDIEN (WEER) IN EVENT ROL GEDRAG VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP GEDRAG VAN DIT KAMP
			#####################################################################################################
			if ($event_hoofd_gedrag_id == $contact_id AND $group_gedrag == 1) {
				$params_groupcontact_gedrag_update = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Added',
						],
					'where' => [
						['group_id', 	'=', $aclgroupgedrag], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_gedrag_update: '.print_r($params_groupcontact_gedrag_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_gedrag_update);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>(weer) Toegevoegd aan ACL groep gedrag van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# INDIEN NIET IN EVENT ROL GEDRAG VAN DIT KAMP VERWIJDER DAN OOK UIT GROEP GEDRAG VAN DIT KAMP
			#####################################################################################################
			if ($event_hoofd_gedrag_id != $contact_id AND $group_gedrag == 1) {
				$params_groupcontact_gedrag_remove = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Removed',
						],
					'where' => [
						['group_id', 	'=', $aclgroupgedrag], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_gedrag_remove: '.print_r($params_groupcontact_gedrag_remove,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_gedrag_remove);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>Verwijderd uit ACL groep gedrag van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}

			#####################################################################################################
			# 5.2 VOEG LEIDING TOE AAN ACL GROEP DRUKWERK VAN DIT KAMP INDIEN AANGEGEVEN DAT DEZE PERSOON HOOFD DRUKWERK IS
			if ($extdebug >= 0) { watchdog('php','<pre>### 5.2 VOEG LEIDING TOE AAN ACL GROEP DRUKWERK VAN DIT KAMP INDIEN AANGEGEVEN DAT DEZE PERSOON HOOFD DRUKWERK IS ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################			
			if ($part_welkkampleid == 'KK1')	{ $aclgroupdrukwerk = 1666;}
			if ($part_welkkampleid == 'KK2')	{ $aclgroupdrukwerk = 1667;}
			if ($part_welkkampleid == 'BK1')	{ $aclgroupdrukwerk = 1668;}
			if ($part_welkkampleid == 'BK2')	{ $aclgroupdrukwerk = 1669;}
			if ($part_welkkampleid == 'TK1')	{ $aclgroupdrukwerk = 1670;}
			if ($part_welkkampleid == 'TK2')	{ $aclgroupdrukwerk = 1671;}
			if ($part_welkkampleid == 'JK1')	{ $aclgroupdrukwerk = 1672;}
			if ($part_welkkampleid == 'JK2')	{ $aclgroupdrukwerk = 1673;}
			if ($part_welkkampleid == 'TOP')	{ $aclgroupdrukwerk = 1674;}

			$params_groupcontact_drukwerk_get = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
					'select' => [
						'row_count',
					'id', 
					'group_id', 
					'group_id:name',
					],
					'where' => [
					['group_id', 	'=', $aclgroupdrukwerk], // M61: hardcoded id of ACL group Ditjaar_kampstaf
					['contact_id', 	'=', $contact_id],
					],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_drukwerk_get: '.print_r($params_groupcontact_drukwerk_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_drukwerk_get);
			if ($extdebug >= 4) { watchdog('php','<pre>result_groupcontact_drukwerk_get: '.print_r($result,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$group_drukwerk = $result->count();
			if ($extdebug >= 2) { watchdog('php','<pre>Deel van groep drukwerk van kamp: '.$group_drukwerk.'</pre>',NULL,WATCHDOG_DEBUG); }

			#####################################################################################################
			# INDIEN IN EVENT ROL DRUKWERK VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP DRUKWERK VAN DIT KAMP
			#####################################################################################################
			if ($event_hoofd_boekje_id == $contact_id AND $group_drukwerk == 0) {
				$params_groupcontact_drukwerk_create = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'group_id' 		=> $aclgroupdrukwerk, 
						'contact_id' 	=> $contact_id,
						'status' 		=> 'Added',
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_drukwerk_create: '.print_r($params_groupcontact_drukwerk_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'create', $params_groupcontact_drukwerk_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>Toegevoegd aan ACL groep drukwerk van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# INDIEN (WEER) IN EVENT ROL DRUKWERK VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP DRUKWERK VAN DIT KAMP
			#####################################################################################################
			if ($event_hoofd_boekje_id == $contact_id AND $group_drukwerk == 1) {
				$params_groupcontact_drukwerk_update = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Added',
						],
					'where' => [
						['group_id', 	'=', $aclgroupdrukwerk], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_drukwerk_update: '.print_r($params_groupcontact_drukwerk_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_drukwerk_update);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>(weer) Toegevoegd aan ACL groep drukwerk van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			#####################################################################################################
			# INDIEN NIET IN EVENT ROL DRUKWERK VAN DIT KAMP VERWIJDER DAN OOK UIT GROEP DRUKWERK VAN DIT KAMP
			#####################################################################################################
			if ($event_hoofd_boekje_id != $contact_id AND $group_drukwerk == 1) {
				$params_groupcontact_drukwerk_remove = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Removed',
						],
					'where' => [
						['group_id', 	'=', $aclgroupdrukwerk], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_drukwerk_remove: '.print_r($params_groupcontact_drukwerk_remove,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_drukwerk_remove);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>Verwijderd uit ACL groep drukwerk van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}

			#####################################################################################################
			# 5.3 VOEG HOOFD KEUKEN TOE AAN ACL GROEP HOOFD KEUKEN VAN DIT KAMP (INDIEN AANGEGEVEN BIJ EVENT ROLLEN)
			if ($extdebug >= 0) { watchdog('php','<pre>### 5.3 VOEG HOOFD KEUKEN TOE AAN ACL GROEP HOOFD KEUKEN VAN DIT KAMP (INDIEN AANGEGEVEN BIJ EVENT ROLLEN) ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################			
			if ($part_welkkampleid == 'KK1')	{ $aclgroupchef = 1675;}	// hoofdkeuken-kk1
			if ($part_welkkampleid == 'KK2')	{ $aclgroupchef = 1676;}	// hoofdkeuken-kk2
			if ($part_welkkampleid == 'BK1')	{ $aclgroupchef = 1677;}	// hoofdkeuken-bk1
			if ($part_welkkampleid == 'BK2')	{ $aclgroupchef = 1678;}	// etc
			if ($part_welkkampleid == 'TK1')	{ $aclgroupchef = 1679;}
			if ($part_welkkampleid == 'TK2')	{ $aclgroupchef = 1680;}
			if ($part_welkkampleid == 'JK1')	{ $aclgroupchef = 1681;}
			if ($part_welkkampleid == 'JK2')	{ $aclgroupchef = 1682;}
			if ($part_welkkampleid == 'TOP')	{ $aclgroupchef = 1683;}

			$params_groupcontact_chef_get = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
				'select' => [
					'row_count',
					'id', 
					'group_id', 
					'group_id:name',
				],
				'where' => [
					['group_id', 	'=', $aclgroupchef], // M61: hardcoded id of ACL group ditjaar_hoofdkeuken
					['contact_id', 	'=', $contact_id],
				],
			];

			if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_chef_get: '.print_r($params_groupcontact_chef_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result_chef_get 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_chef_get);
			if ($extdebug >= 4) { watchdog('php','<pre>result_groupcontact_chef_get: '.print_r($result_chef_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$group_chef = $result->count();
			if ($extdebug >= 1) { watchdog('php','<pre>Deel van groep chef van kamp: '.$group_chef.'</pre>',NULL,WATCHDOG_DEBUG); }

			############################################################################################################
			# INDIEN IN EVENT ROL HOOFDKEUKEN VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP HOOFDKEUKEN VAN DIT KAMP
			############################################################################################################

//			if ($group_staf == 1 AND in_array($part_functie, array('hoofdkeuken'))) {

			if ($event_hoofd_keuken_id == $contact_id AND $group_keuken == 0) {
				$params_groupcontact_chef_create = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
					'values' => [
						'group_id' 		=> $aclgroupchef,
						'contact_id' 	=> $contact_id,
						'status' 		=> 'Added',
					],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_chef_create: '.print_r($params_groupcontact_chef_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result_chef_create = civicrm_api4('GroupContact', 'create', $params_groupcontact_chef_create);
				}
				if ($extdebug >= 1) { watchdog('php','<pre>Toegevoegd aan ACL groep chef van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			############################################################################################################
			# INDIEN (WEER) IN EVENT ROL HOOFDKEUKEN VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP HOOFDKEUKEN VAN DIT KAMP
			############################################################################################################
			if ($event_hoofd_keuken_id == $contact_id AND $group_keuken == 1) {
				$params_groupcontact_chef_update = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
					'values' => [
						'status' => 'Added',
					],
					'where' => [
						['group_id', 	'=', $aclgroupchef], 
						['contact_id', 	'=', $contact_id],
					],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_chef_update: '.print_r($params_groupcontact_chef_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result_chef_update = civicrm_api4('GroupContact', 'update', $params_groupcontact_chef_update);
				}
				if ($extdebug >= 1) { watchdog('php','<pre>(weer) Toegevoegd aan ACL groep chef van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			############################################################################################################
			# INDIEN NIET IN EVENT ROL chef VAN DIT KAMP VERWIJDER DAN OOK UIT GROEP chef VAN DIT KAMP
			############################################################################################################
			if ($event_hoofd_keuken_id != $contact_id AND $group_keuken == 1) {
				$params_groupcontact_chef_remove = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
					'values' => [
						'status' => 'Removed',
					],
					'where' => [
						['group_id', 	'=', $aclgroupchef], 
						['contact_id', 	'=', $contact_id],
					],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_chef_remove: '.print_r($params_groupcontact_chef_remove,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result_chef_remove = civicrm_api4('GroupContact', 'update', $params_groupcontact_chef_remove);
				}
				if ($extdebug >= 1) { watchdog('php','<pre>Verwijderd uit ACL groep chef van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}

			#####################################################################################################
			# 5.4 VOEG ALLE LEIDING MET FUNCTIE (HOOFD)KEUKEN TOE AAN ALGEMENE ACL GROEP DITJAAR KEUKENTEAM
			if ($extdebug >= 0) { watchdog('php','<pre>### 5.4 VOEG ALLE LEIDING MET FUNCTIE (HOOFD)KEUKEN TOE AAN ALGEMENE ACL GROEP DITJAAR KEUKENTEAM ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################			

			if (in_array($part_functie, array('hoofdkeuken', 'keukenteamlid'))) {
				$aclgroupkeuken = 1778;	// ALGEMENE ACL GROEP VOOR ALLE KEUKENTEAMLEDEN
			}

			$params_groupcontact_keuken_get = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
				'select' => [
					'row_count',
					'id', 
					'group_id', 
					'group_id:name',
				],
				'where' => [
					['group_id', 	'=', $aclgroupkeuken], // M61: hardcoded id of ACL group ditjaar_hoofdkeuken
					['contact_id', 	'=', $contact_id],
				],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_keuken_get: '.print_r($params_groupcontact_keuken_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result 	= civicrm_api4('GroupContact', 'get', $params_groupcontact_keuken_get);
			if ($extdebug >= 4) { watchdog('php','<pre>result_groupcontact_keuken_get: '.print_r($result,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$group_keuken = $result->count();
			if ($extdebug >= 1) { watchdog('php','<pre>Deel van groep keuken van kamp: '.$group_keuken.'</pre>',NULL,WATCHDOG_DEBUG); }

			############################################################################################################
			# INDIEN IN EVENT ROL HOOFDKEUKEN VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP HOOFDKEUKEN VAN DIT KAMP
			############################################################################################################
			if ($event_hoofd_keuken_id == $contact_id AND $group_keuken == 0) {
				$params_groupcontact_keuken_create = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
					'values' => [
						'group_id' 		=> $aclgroupkeuken,
						'contact_id' 	=> $contact_id,
						'status' 		=> 'Added',
					],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_keuken_create: '.print_r($params_groupcontact_keuken_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'create', $params_groupcontact_keuken_create);
				}
				if ($extdebug >= 1) { watchdog('php','<pre>Toegevoegd aan ACL groep keuken van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			############################################################################################################
			# INDIEN (WEER) IN EVENT ROL HOOFDKEUKEN VAN DIT KAMP VOEG DAN OOK TOE AAN GROEP HOOFDKEUKEN VAN DIT KAMP
			############################################################################################################
			if ($event_hoofd_keuken_id == $contact_id AND $group_keuken == 1) {
				$params_groupcontact_keuken_update = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
						'values' => [
						'status' => 'Added',
						],
					'where' => [
						['group_id', 	'=', $aclgroupkeuken], 
						['contact_id', 	'=', $contact_id],
						],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_keuken_update: '.print_r($params_groupcontact_keuken_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_keuken_update);
				}
				if ($extdebug >= 1) { watchdog('php','<pre>(weer) Toegevoegd aan ACL groep keuken van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
			############################################################################################################
			# INDIEN NIET IN EVENT ROL KEUKEN VAN DIT KAMP VERWIJDER DAN OOK UIT GROEP KEUKEN VAN DIT KAMP
			############################################################################################################
			if ($event_hoofd_keuken_id != $contact_id AND $group_keuken == 1) {
				$params_groupcontact_keuken_remove = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
					'values' => [
						'status' => 'Removed',
					],
					'where' => [
						['group_id', 	'=', $aclgroupkeuken], 
						['contact_id', 	'=', $contact_id],
					],
				];
				if ($extdebug >= 3) { watchdog('php','<pre>params_groupcontact_keuken_remove: '.print_r($params_groupcontact_keuken_remove,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  		if ($extwrite == 1) {
					$result = civicrm_api4('GroupContact', 'update', $params_groupcontact_keuken_remove);
				}
				if ($extdebug >= 1) { watchdog('php','<pre>Verwijderd uit ACL groep keuken van kamp: '.$part_welkkampleid.' ('.$part_welkkampleid.')</pre>',NULL,WATCHDOG_DEBUG); }
			}
		} // EINDE SEGMENT DIT JAAR EVENT & JAAR LEID
