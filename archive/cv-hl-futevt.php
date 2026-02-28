<?php

		if (in_array($part_functie, array('hoofdleiding', 'bestuurslid', 'kampstaf'))) {
			#####################################################################################################
			# 6.2 REGISTER HOOFDLEIDING VOOR FUTURE KAMPSTAF EVENS (MEETINGS)
			if ($extdebug >= 0) { watchdog('php','<pre>### 6.2 REGISTER HOOFDLEIDING VOOR FUTURE KAMPSTAF EVENS [groupID: '.$groupID.'] ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################

			# REGISTREER ALLEEN DE EVENT IDS DIE NOG NIET GEREGISTREERD ZIJN
			$evtcv_meet_notregistered_array = array_diff($kampidsmeet, $evtcv_meet_array);
			arsort($evtcv_meet_notregistered_array); // sort by value (reverse)

			if ($extdebug >= 2) { watchdog('php','<pre>evtcv_meet_array: '.print_r($evtcv_meet_array,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>kampidsmeet: '.print_r($kampidsmeet,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>evtcv_meet_notregistered_array: '.print_r($evtcv_meet_notregistered_array,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			if ($extdebug >= 2) { watchdog('php','<pre>part_functie: '.print_r($part_functie,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>group_staf: '.print_r($group_staf,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			if (in_array($part_functie, array('hoofdleiding','bestuurslid','kampstaf')) AND $group_staf == 1) {
			// ALLEEN INDIEN HL & IN MANUAL ACL GROUP STAF

				foreach ($evtcv_meet_notregistered_array as $kampstafeid) {
					$params_participant_create = [
						'checkPermissions' => FALSE,
						'debug' => $apidebug,
						'values' => [
							'contact_id' 		=> $contact_id,
							'event_id' 			=> $kampstafeid,
							'register_date' 	=> $today_datetime,
							'status_id'			=> 24,	// initiele status: Nog niet bekend
							'role_id' 			=> [6],	// rol = leiding
						],
					];
					if ($extdebug >= 3) { watchdog('php','<pre>params_participant_create: '.print_r($params_participant_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($regpast == 1 AND $kampstafeid > 0) {
						$participant_create = civicrm_api4('Participant', 'create', $params_participant_create);
					}
					if ($extdebug >= 2) { watchdog('php','<pre>Deze persoon geregistreerd kampstaf eventid: '.$kampstafeid.'</pre>',NULL,WATCHDOG_DEBUG); }
					if ($extdebug >= 4) { watchdog('php','<pre>result_participant_create: '.print_r($participant_create,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				}
			}

			if ($extdebug >= 1) { watchdog('php','<pre>oldcv_deel_nr  : '.print_r($oldcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php','<pre>curcv_deel_nr  : '.print_r($curcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php','<pre>evtcv_deel_nr  : '.print_r($evtcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php','<pre>tagcv_deel_nr  : '.print_r($tagcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php','<pre>maxcv_deel_nr  : '.print_r($maxcv_deel_nr,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
			if ($extdebug >= 1) { watchdog('php','<pre>deel_nr_diff   : '.print_r($deel_nr_diff,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); 	}
		}
