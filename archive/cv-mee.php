<?php

		#####################################################################################################
		# 3.4 WERVING MEE VOLGEND JAAR
		if ($extdebug >= 0) { watchdog('php','<pre>### 3.4 WERVING MEE VOLGEND JAAR: '.$part_eventid.'] ###</pre>',NULL,WATCHDOG_DEBUG); }
		#####################################################################################################

		if ($werving_mee_update) {
			//NEEM DATUM VAN LAATSTE UPDATE OVER 'MEE KOMEND KAMP' EN LEIDT DAARUIT HET KAMPJAAR AF

			$mee_fiscalyear_array	= curriculum_civicrm_fiscalyear($werving_mee_update);
			$mee_fiscalyear_start	= $mee_fiscalyear_array['fiscalyear_start'];
			$mee_fiscalyear_einde	= $mee_fiscalyear_array['fiscalyear_einde'];

			if ($extdebug >= 2) {watchdog('php','<pre>mee_fiscalyear_start      : '.print_r($mee_fiscalyear_start,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);}
			if ($extdebug >= 2) {watchdog('php','<pre>mee_fiscalyear_einde      : '.print_r($mee_fiscalyear_einde,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);}
			if ($extdebug >= 2) {watchdog('php','<pre>datum_mee_ingevuld        : '.print_r($werving_mee_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);  }
		}

		if ($part_evaluatie > empty($werving_mee_update)) {	// M61: repair voor als werving update niet was ingevuld maar evaluatie wel
			$werving_mee_update = $part_evaluatie;
			if ($werving_mee_update) 	{ $params_contact['values']['Werving_promotie.mee_update']	= $werving_mee_update; 	}
		}

		if ($werving_mee_update) {
			### NEXTV FROM EVENT
			$mee_update_year			= date('Y', $werving_mee_update);
			$mee_update_next_date		= date('Y-m-d H:i:s', strtotime ( '+1 year' , strtotime ($werving_mee_update)));
			$mee_update_next_year		= date('Y',           strtotime ( '+1 year' , strtotime ($werving_mee_update)));	
			if ($mee_update_next_year == $laatstekeer) {$mee_update_next_year = $mee_update_next_year + 1;}
			// M61 KLEINE SAFEGUARD: ALS komendkampjaar zelfde is als laatstekeer dan + 1 jaar

			$params_nextkampfromthisdate = [
				'checkPermissions' => FALSE,
				'debug' 	=> $apidebug,
				'limit' 	=> 1,
					'select' 	=> [
						'title', 
						'start_date',
					],
					'where' => [
						['start_date', 		'>', $werving_mee_update], 
						['start_date', 		'<', $mee_update_next_date],
	//	   				['event_type_id', 	'=', 1],
	   					['event_type_id', 	'=', $part_eventtypeid], // M61: zoek next event van zelfde event_type_id (todo: kan evt missen)
					],
			];
	  		if ($extdebug >= 3) { watchdog('php','<pre>params_nextkampfromthisdate: '.print_r($params_nextkampfromthisdate,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$nextevent = civicrm_api4('Event', 'get', $params_nextkampfromthisdate);
	  		if ($extdebug >= 4) { watchdog('php','<pre>result_nextkampfromthisdate: '.print_r($nextevent,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			$mee_event_next_date = $nextevent[0]['start_date'];
			$mee_event_next_year = date('Y', strtotime($mee_event_next_date));
			$mee_event_past_date = date('Y-m-d', strtotime ( '-12 months' , strtotime ($mee_event_next_year)));
		}

		$params_activity_mee_get = [
			'checkPermissions' => FALSE,
			'debug' => $apidebug,
			'select' => [
				'id', 'activity_date_time', 'status_id', 'status_id:name', 'subject', 'activity_contact.contact_id',
			],
			'join' => [
				['ActivityContact AS activity_contact', 'INNER'],
			],
			'where' => [
				['activity_contact.contact_id', 	'=', $contact_id],
				['activity_contact.record_type_id', '=', 3],
				['activity_type_id:name', 			'=', 'werving_mee_ditjaar'],
				['activity_date_time', 				'>=', $mee_fiscalyear_start],
				['activity_date_time', 				'<=', $mee_fiscalyear_einde],
			],
			'limit' => 1,
		];

		if ($extdebug >= 3) { watchdog('php','<pre>params_activity_mee_get : '.print_r($params_activity_mee_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	  	$result_mee_get 	  = civicrm_api4('Activity', 'get', $params_activity_mee_get);
	  	$result_mee_get_count = $result_mee_get->count();
	  	if ($extdebug >= 4) { watchdog('php','<pre>result_activity_mee_get : '.print_r($result_mee_get,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }  	
	  	if ($extdebug >= 2) { watchdog('php','<pre>result_mee_count          : '.print_r($result_mee_get_count,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

	  	if ($result_mee_get_count == 1) {
	  		$mee_activity_id			= $result_mee_get[0]['id'];
	  		$mee_activity_status_id		= $result_mee_get[0]['status_id'];
	  		$mee_activity_status_name	= $result_mee_get[0]['status_id:name'];
	  		$mee_activity_datum			= $result_mee_get[0]['activity_date_time'];
			if ($extdebug >= 1) { watchdog('php','<pre>mee_activity_id          : '.print_r($mee_activity_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>mee_activity_status_id   : '.print_r($mee_activity_status_id,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>mee_activity_status      : '.print_r($mee_activity_status_name,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>mee_activity_datum       : '.print_r($mee_activity_datum,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	  	} else {
			$mee_activity_id		= NULL;
	  		$mee_activity_status	= NULL;
			$mee_activity_datum		= NULL;
			if ($extdebug >= 1) { watchdog('php','<pre>mee_activity_id         : No Activity Found</pre>',NULL,WATCHDOG_DEBUG); }
		}

		if ($werving_mee_update AND $result_mee_get_count == 0) {

			if ($extdebug >= 1) { watchdog('php', '<pre>################################################################</pre>',NULL,WATCHDOG_DEBUG);		}
	   		if ($extdebug >= 2) { watchdog('php','<pre>CREATE werving_mee_verwachting   : '.print_r($werving_mee_verwachting,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 2) { watchdog('php','<pre>CREATE werving_mee_toelichting   : '.print_r($werving_mee_toelichting,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php', '<pre>################################################################</pre>',NULL,WATCHDOG_DEBUG);		}

			$params_activity_mee_create = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
				'values' => [
	    			'source_contact_id' 		=> $contact_id,
					'target_contact_id' 		=> $contact_id,
					'activity_type_id:name' 	=> 'werving_mee_ditjaar',
					'activity_date_time' 		=> $werving_mee_update,					
					'subject' 					=> 'Mee in '. $werving_mee_komendkamp.' : '.$werving_mee_verwachting,
					'status_id:name' 			=> 'Completed',
				    'ACT_MEE.kampjaar' 			=> $mee_event_next_year,
				    'ACT_MEE.komendkamp' 		=> $mee_event_next_date,
				    'ACT_MEE.rol' 				=> $werving_mee_rol,
				    'ACT_MEE.verwachting' 		=> $werving_mee_verwachting, 
				    'ACT_MEE.toelichting' 		=> $werving_mee_toelichting, 
				    'ACT_MEE.update' 			=> $werving_mee_update,
					'ACT_ALG.modified' 			=> $today_datetime,
					'ACT_ALG.kampkort'			=> $welkkampkort,
					'ACT_ALG.kampfunctie' 		=> $part_functie,
					'ACT_ALG.kampstart' 		=> $event_start_date,
					'ACT_ALG.kampeinde' 		=> $event_einde_date,
					'ACT_ALG.kampjaar'			=> $event_kampjaar,
				],
			];
			if ($extdebug >= 1) { watchdog('php','<pre>params_activity_mee_create: '.print_r($params_activity_mee_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
	  		if ($extwrite == 1) {
				$result_activity_mee_create = civicrm_api4('Activity', 'create', $params_activity_mee_create);
			}
			if ($extdebug >= 1) { watchdog('php','<pre>result_activity_mee_create: '.print_r($result_activity_mee_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
		}

		if ($werving_mee_update AND $result_mee_get_count == 1) {

			if ($extdebug >= 1) { watchdog('php', '<pre>################################################################</pre>',NULL,WATCHDOG_DEBUG);		}
	   		if ($extdebug >= 2) { watchdog('php','<pre>UPDATE werving_mee_verwachting   : '.print_r($werving_mee_verwachting,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 2) { watchdog('php','<pre>UPDATE werving_mee_toelichting   : '.print_r($werving_mee_toelichting,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php', '<pre>################################################################</pre>',NULL,WATCHDOG_DEBUG);		}

			$params_activity_mee_update = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,
				'where' => [
					['id', 					'=', $mee_activity_id],
				],				
				'values' => [
	    			'source_contact_id' 		=> $contact_id,
					'target_contact_id' 		=> $contact_id,
					'activity_type_id:name' 	=> 'werving_mee_ditjaar',
					'activity_date_time' 		=> $werving_mee_update,
					'subject' 					=> 'Mee in '. $werving_mee_komendkamp.' : '.$werving_mee_verwachting,
					'status_id:name' 			=> 'Completed',
				    'ACT_MEE.kampjaar' 			=> $mee_event_next_year,
				    'ACT_MEE.komendkamp' 		=> $mee_event_next_date,
				    'ACT_MEE.rol' 				=> $werving_mee_rol,
				    'ACT_MEE.verwachting' 		=> $werving_mee_verwachting,
				    'ACT_MEE.toelichting' 		=> $werving_mee_toelichting,
				    'ACT_MEE.update' 			=> $werving_mee_update,
					'ACT_ALG.modified' 			=> $today_datetime,
					'ACT_ALG.kampkort'			=> $welkkampkort,
					'ACT_ALG.kampfunctie' 		=> $part_functie,
					'ACT_ALG.kampstart' 		=> $event_start_date,
					'ACT_ALG.kampeinde' 		=> $event_einde_date,
					'ACT_ALG.kampjaar'			=> $event_kampjaar,
				],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_activity_mee_update: '.print_r($params_activity_mee_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result_activity_mee_update = civicrm_api4('Activity', 'update', $params_activity_mee_update);
			if ($extdebug >= 4) { watchdog('php','<pre>result_activity_mee_update: '.print_r($result_activity_mee_update,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			$today_nextkamp_date = $tdy_event_next_date;
			$today_nextkamp_jaar = $tdy_event_next_year;

			if ($extdebug >= 1) {watchdog('php','<pre>event_start_date     	: '.print_r($event_start_date,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	 	}
			if ($extdebug >= 1) {watchdog('php','<pre>event_einde_date     	: '.print_r($event_einde_date,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	 	}
			if ($extdebug >= 2) {watchdog('php','<pre>today_nextkamp_date  	: '.print_r($today_nextkamp_date,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);	}
			if ($extdebug >= 2) {watchdog('php','<pre>today_nextkamp_jaar  	: '.print_r($today_nextkamp_jaar,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);	}

			if ($extdebug >= 2) {watchdog('php','<pre>mee_update_next_year 	: '.print_r($mee_update_next_year,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);	}
			if ($extdebug >= 2) {watchdog('php','<pre>mee_event_past_date  	: '.print_r($mee_event_past_date,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);	}
			if ($extdebug >= 2) {watchdog('php','<pre>mee_event_next_date  	: '.print_r($mee_event_next_date,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);	}
			if ($extdebug >= 2) {watchdog('php','<pre>mee_event_next_year  	: '.print_r($mee_event_next_year,TRUE).'</pre>',NULL,WATCHDOG_DEBUG);	}
		}