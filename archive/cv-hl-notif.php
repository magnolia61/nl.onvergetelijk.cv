<?php

		if (in_array($part_functie, array('hoofdleiding', 'bestuurslid', 'kampstaf'))) {

			#####################################################################################################
			# 6.3 VOEG VOOR HOOFDLEIDING DOORGEGEVEN EMAIL TOE AAN GROEP DIE KAMPSTAF EMAIL ONTVANGT
			if ($extdebug >= 0) { watchdog('php','<pre>### 6.3 VOEG VOOR HOOFDLEIDING DOORGEGEVEN EMAIL TOE AAN GROEP DIE KAMPSTAF EMAIL ONTVANGT ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################	

				// M61: FIRST DELETE EXISTING NOTIF EMAILS (BECAUSE DOUBLES WERE CREATED: THIS SHOULD NOT HAPPEN > TODO)
				$results = civicrm_api4('Email', 'delete', [
						'where' => [
						['location_type_id', 'IN', [16, 17, 18, 19]],
						['contact_id', '=', $contact_id],
						],
				]);

			#if ($extdebug >= 2) { watchdog('php','<pre>home_id: '.print_r($email_home_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>othr_id: '.print_r($email_othr_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>email_notif_deel_id: '.print_r($notif_deel_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	#if ($extdebug >= 2) { watchdog('php','<pre>email_notif_leid_id: '.print_r($notif_leid_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	#if ($extdebug >= 2) { watchdog('php','<pre>email_notif_kamp_id: '.print_r($notif_kamp_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	#if ($extdebug >= 2) { watchdog('php','<pre>email_notif_staf_id: '.print_r($notif_staf_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			if ($extdebug >= 2) { watchdog('php','<pre>home_email       : '.print_r($email_home_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>othr_email       : '.print_r($email_othr_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>email_notif_deel : '.print_r($notif_deel_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>email_notif_leid : '.print_r($notif_leid_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>email_notif_kamp : '.print_r($notif_kamp_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>email_notif_staf : '.print_r($notif_staf_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			#if ($extdebug >= 2) { watchdog('php','<pre>cont_notificatie_deel: '.print_r($cont_notificatie_deel,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>cont_notificatie_leid: '.print_r($cont_notificatie_leid,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>cont_notificatie_kamp: '.print_r($cont_notificatie_kamp,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>cont_notificatie_staf: '.print_r($cont_notificatie_staf,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			#if ($extdebug >= 2) { watchdog('php','<pre>part_notificatie_deel: '.print_r($part_notificatie_deel,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>part_notificatie_leid: '.print_r($part_notificatie_leid,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>part_notificatie_kamp: '.print_r($part_notificatie_kamp,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			#if ($extdebug >= 2) { watchdog('php','<pre>part_notificatie_staf: '.print_r($part_notificatie_staf,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			if (!empty($cont_notificatie_deel) AND empty($part_notificatie_deel)) 	{ $part_notificatie_deel = $cont_notificatie_deel; }
			if (!empty($cont_notificatie_leid) AND empty($part_notificatie_leid)) 	{ $part_notificatie_leid = $cont_notificatie_leid; }
			if (!empty($cont_notificatie_kamp) AND empty($part_notificatie_kamp)) 	{ $part_notificatie_kamp = $cont_notificatie_kamp; }
			if (!empty($cont_notificatie_staf) AND empty($part_notificatie_staf))	{ $part_notificatie_staf = $cont_notificatie_staf; }

			if ($part_notificatie_deel == 1) {
				# ALLEEN IN DE MAILBOX VAN JOUW KAMP (SWS)
				$notif_deel_new = NULL;
				$results = civicrm_api4('Email', 'delete', [ 'where' => [ ['id', '=', $notif_deel_id], ],]);
			} elseif ($part_notificatie_deel == 2) {
				# OOK OP JOUW PERSOONLIJKE @ONVERGETELIJK
				$notif_deel_new = $email_onvr_email;
			} elseif ($part_notificatie_deel == 3) {
				# OOK OP JE PRIVE EMAILADRES
				$notif_deel_new = $email_home_email;
			} else {
				$part_notificatie_deel	= 3; 
				$notif_deel_new 		= $email_home_email;
			}

			if ($part_notificatie_leid == 1) {
				# ALLEEN IN DE MAILBOX VAN JOUW KAMP (SWS)
				$notif_leid_new = NULL;
				$results = civicrm_api4('Email', 'delete', [ 'where' => [ ['id', '=', $notif_leid_id], ],]);
			} elseif ($part_notificatie_leid == 2) {
				# OOK OP JOUW PERSOONLIJKE @ONVERGETELIJK
				$notif_leid_new = $email_onvr_email;
			} elseif ($part_notificatie_leid == 3) {
				# OOK OP JE PRIVE EMAILADRES
				$notif_leid_new = $email_home_email;
			} else {
				$part_notificatie_leid	= 3; 
				$notif_leid_new 		= $email_home_email;
			}

			if ($part_notificatie_kamp == 1) {
				# ALLEEN IN DE MAILBOX VAN JOUW KAMP (SWS)
				$notif_kamp_new = NULL;
				$results = civicrm_api4('Email', 'delete', [ 'where' => [ ['id', '=', $notif_kamp_id], ],]);
			} elseif ($part_notificatie_kamp == 2) {
				# OOK OP JOUW PERSOONLIJKE @ONVERGETELIJK
				$notif_kamp_new = $email_onvr_email;
			} elseif ($part_notificatie_kamp == 3) {
				# OOK OP JE PRIVE EMAILADRES
				$notif_kamp_new = $email_home_email;
			} else {
				$part_notificatie_kamp	= 3; 
				$notif_kamp_new 		= $email_home_email;
			}

			if ($part_notificatie_staf == 1) {
				$notif_staf_new = $email_onvr_email;
			} elseif ($part_notificatie_staf == 2) {
				$notif_staf_new = $email_home_email;
			} else {
				$part_notificatie_staf	= 2; 
				$notif_staf_new 		= $email_home_email;
			}

		  	if ($extdebug >= 2) { watchdog('php','<pre>notif_deel_new  : '.print_r($notif_deel_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>notif_leid_new  : '.print_r($notif_leid_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>notif_kamp_new  : '.print_r($notif_kamp_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		  	if ($extdebug >= 2) { watchdog('php','<pre>notif_staf_new  : '.print_r($notif_staf_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			if ($part_notificatie_deel AND $notif_deel_new AND in_array($part_functie, array('hoofdleiding'))) {
				$params_email_notif_deel_create = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
							'values' => [
							'location_type_id:name' => 'notif_deel', 
							'email' 	=> $notif_deel_new,
							'contact_id'=> $contact_id, 
							'is_primary'=> FALSE,
							],
					];
				if ($notif_deel_id AND $notif_deel_email != $notif_deel_new) {
					//M61: Het lukt niet om bij een bestaand email-id het email adres zelf via api te wijzigen. 
					//M61: Wel via de explorer, niet hier in code. Dus daarom verwijderen en aanmaken
					$results = civicrm_api4('Email', 'delete', [ 'where' => [ 
							['location_type_id:label', 	'=', 'notif_deel'],
							['contact_id', 		 		'=', $contact_id],
						],
					]);
					if ($extdebug >= 2) { watchdog('php','<pre>notif_deel verwijderd (tbv opnieuw aanmaken): '.print_r($notif_deel_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
				if ($extdebug >= 3) { watchdog('php','<pre>params_email_notif_deel_create: '.print_r($params_email_notif_deel_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	  		if ($extwrite == 1 AND $privacy_voorkeuren != 'Verwijder contactgegevens' AND $privacy_voorkeuren != '44') {
					$result = civicrm_api4('Email', 'create', $params_email_notif_deel_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>2 notif_deel aangemaakt voor: '.print_r($notif_deel_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }	
			}
			if ($part_notificatie_leid AND $notif_leid_new AND in_array($part_functie, array('hoofdleiding'))) {
				$params_email_notif_leid_create = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
							'values' => [
							'location_type_id:name' => 'notif_leid', 
							'email' 	=> $notif_leid_new,
							'contact_id'=> $contact_id, 
							'is_primary'=> FALSE,
							],
					];
				if ($notif_leid_id AND $notif_leid_email != $notif_leid_new) {
					$results = civicrm_api4('Email', 'delete', [ 'where' => [ 
							['location_type_id:label', 	'=', 'notif_leid'],
							['contact_id', 		 		'=', $contact_id],
						],
					]);
					if ($extdebug >= 2) { watchdog('php','<pre>notif_leid verwijderd (tbv opnieuw aanmaken): '.print_r($notif_leid_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
				if ($extdebug >= 3) { watchdog('php','<pre>params_email_notif_leid_create: '.print_r($params_email_notif_leid_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	  		if ($extwrite == 1 AND $privacy_voorkeuren != 'Verwijder contactgegevens' AND $privacy_voorkeuren != '44') {
					$result = civicrm_api4('Email', 'create', $params_email_notif_leid_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>2 notif_leid aangemaakt voor: '.print_r($notif_leid_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }	
			}
			if ($part_notificatie_kamp AND $notif_kamp_new AND in_array($part_functie, array('hoofdleiding'))) {
				$params_email_notif_kamp_create = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
							'values' => [
							'location_type_id:name' => 'notif_kamp', 
							'email' 	=> $notif_kamp_new,
							'contact_id'=> $contact_id, 
							'is_primary'=> FALSE,
							],
					];
				if ($notif_kamp_id AND $notif_kamp_email != $notif_kamp_new) {
									$results = civicrm_api4('Email', 'delete', [ 'where' => [ 
							['location_type_id:label', 	'=', 'notif_kamp'],
							['contact_id', 		 		'=', $contact_id],
						],
					]);
					if ($extdebug >= 2) { watchdog('php','<pre>notif_kamp verwijderd (tbv opnieuw aanmaken): '.print_r($notif_kamp_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
				if ($extdebug >= 3) { watchdog('php','<pre>params_email_notif_kamp_create: '.print_r($params_email_notif_kamp_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	  		if ($extwrite == 1 AND $privacy_voorkeuren != 'Verwijder contactgegevens' AND $privacy_voorkeuren != '44') {
					$result = civicrm_api4('Email', 'create', $params_email_notif_kamp_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>2 notif_kamp aangemaakt voor: '.print_r($notif_kamp_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }	
			}
			if ($part_notificatie_staf AND $notif_staf_new AND in_array($part_functie, array('hoofdleiding'))) {
				$params_email_notif_staf_create = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
							'values' => [
							'location_type_id:name' => 'notif_staf', 
							'email' 	=> $notif_staf_new,
							'contact_id'=> $contact_id, 
							'is_primary'=> FALSE,
							],
					];
				if ($notif_staf_id AND $notif_staf_email != $notif_staf_new) {
					$results = civicrm_api4('Email', 'delete', [ 'where' => [
							['location_type_id:label', 	'=', 'notif_staf'],
							['contact_id', 		 		'=', $contact_id],
						],
					]);
					if ($extdebug >= 2) { watchdog('php','<pre>notif_staf verwijderd (tbv opnieuw aanmaken): '.print_r($notif_staf_email,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
				if ($extdebug >= 3) { watchdog('php','<pre>params_email_notif_staf_create: '.$params_email_notif_staf_create.'</pre>',NULL,WATCHDOG_DEBUG); }
	  		if ($extwrite == 1 AND $privacy_voorkeuren != 'Verwijder contactgegevens' AND $privacy_voorkeuren != '44') {
					$result = civicrm_api4('Email', 'create', $params_email_notif_staf_create);
				}
				if ($extdebug >= 2) { watchdog('php','<pre>2 notif_staf aangemaakt voor: '.print_r($notif_staf_new,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }	
			}

			//M61: TODO Dit zou eigenlijk mee moeten met de parameters params_part maar lijkt op die manier niet te updaten.
			if (in_array($part_functie, array('hoofdleiding'))) {
				$params_part_notif = [
						'checkPermissions' 	=> FALSE,
					'debug' => $apidebug,  				
					'reload' 			=> TRUE,
		 			'where' => [
						['id', 	'=', $part_id],
						],
						'values' => [
						'PART_LEID_HOOFD.notificatie_deel' => $part_notificatie_deel, 
						'PART_LEID_HOOFD.notificatie_leid' => $part_notificatie_leid, 
						'PART_LEID_HOOFD.notificatie_kamp' => $part_notificatie_kamp, 
						'PART_LEID_HOOFD.notificatie_staf' => $part_notificatie_staf,
						],
				];
				if ($part_id) {
					#if ($extdebug >= 3) { watchdog('php','<pre>params_part_notif: '.print_r($params_part_notif,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					#$result_part_notif = civicrm_api4('Participant', 'update', $params_part_notif);
					#if ($extdebug >= 4) { watchdog('php','<pre>results_part_notif '.print_r($result_part_notif,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
			}
