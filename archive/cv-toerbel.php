<?php

			#####################################################################################################
			# 4.2a BEPAAL OF ER VOOR DEZE AANMELDING TOERISTENBELASTING MOET WORDEN BETAALD (EDE)
			if ($extdebug >= 0) { watchdog('php','<pre>### 4.2a BEPAAL OF ER VOOR DEZE AANMELDING TOERISTENBELASTING MOET WORDEN BETAALD EDE [groupID: '.$groupID.'] ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################

			$params_addresses = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
					'select' => [
						'row_count',
					],
					'where' => [
						['contact_id', 				'=', $contact_id],
						['Adresgegevens.Gemeente', 	'=', 'Ede'], 
					],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_addresses: '.print_r($params_addresses,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result_ede 	= civicrm_api4('Address', 'get', $params_addresses);
			$group_ede  	= $result_ede->count();
			if ($extdebug >= 4) { watchdog('php','<pre>result_addresses: '.print_r($result_ede,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			if ($extdebug >= 2) { watchdog('php','<pre>group_ede: '.print_r($group_ede,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }


			#####################################################################################################
			# 4.2b BEPAAL OF ER VOOR DEZE AANMELDING TOERISTENBELASTING MOET WORDEN BETAALD
			if ($extdebug >= 0) { watchdog('php','<pre>### 4.2b BEPAAL OF ER VOOR DEZE AANMELDING TOERISTENBELASTING MOET WORDEN BETAALD GAVE [groupID: '.$groupID.'] ###</pre>',NULL,WATCHDOG_DEBUG); }
			#####################################################################################################

			$params_lineitem = [
				'checkPermissions' => FALSE,
				'debug' => $apidebug,				
				'select' => [
					'row_count',
				],
				'where' => [
					['contribution_id.contact_id', '=', $contact_id],
		    		['price_field_value_id', 'IN', [302, 301, 498, 472, 473, 497]],
					['qty', '=', 1],
					['contribution_id.receive_date', '>=', $event_fiscalyear_start],
					['contribution_id.receive_date', '<=', $event_fiscalyear_einde],
				],
			];
			if ($extdebug >= 3) { watchdog('php','<pre>params_gave: '.print_r($params_lineitem,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			$result_gave 	= civicrm_api4('LineItem', 'get', $params_lineitem);
			$group_gave  	= $result_gave->count();
			if ($extdebug >= 4) { watchdog('php','<pre>result_gave: '.print_r($result_gave,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }			
			if ($extdebug >= 2) { watchdog('php','<pre>group_gave: '.print_r($group_gave,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }

			if ($group_gave >= 1) {
	    	    if ($extdebug >= 2) { watchdog('php','<pre>TOERISTENBELASTING: [3] nee, via St.Gave</pre>',NULL,WATCHDOG_DEBUG); }
	    		$toeristenbelasting	= 3;
	    		$kampgeldregeling 	= 'stgave';

			} elseif ($diteventdeelyes == 1 AND $group_ede == 0) {
				if ($extdebug >= 2) { watchdog('php','<pre>TOERISTENBELASTING: [1] ja, als deelnemer</pre>',NULL,WATCHDOG_DEBUG); }
	    		$toeristenbelasting	= 1;
			} elseif ($diteventleidyes == 1 AND $group_ede == 0) {
			    if ($extdebug >= 2) { watchdog('php','<pre>TOERISTENBELASTING: [2] ja, als leiding</pre>',NULL,WATCHDOG_DEBUG); }
				$toeristenbelasting	= 2;
	    	} elseif ($group_ede == 1) {
				if ($extdebug >= 2) { watchdog('php','<pre>TOERISTENBELASTING: [4] nee, woont in gemeente Ede</pre>',NULL,WATCHDOG_DEBUG); }
				$toeristenbelasting	= 4;
			} elseif ($diteventdeelmss) {
				$toeristenbelasting	= "";		
			}
			if ($toeristenbelasting) {
				$params_toer = [
					'checkPermissions' => FALSE,
					'debug' => $apidebug,
	 				'where' => [
						['id', '=', $part_id],
					],
					'values' => [
						'PART.PART_Toeristenbelasting' 	=> $toeristenbelasting,
					],
				];
			}
			if ($part_id AND $toeristenbelasting) {
				#if ($extdebug >= 3) { watchdog('php','<pre>params_toer: '.print_r($params_toer,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
				#$result_toer = civicrm_api4('Participant', 'update', $params_toer);
				$params_participant['values']['PART.PART_Toeristenbelasting']	= $toeristenbelasting;
				#if ($extdebug >= 4) { watchdog('php','<pre>results_toer: '.print_r($result_toer,TRUE).'</pre>',NULL,WATCHDOG_DEBUG); }
			}
			if ($part_id AND $kampgeldregeling) {
				$params_participant['values']['PART_KAMPGELD.regeling'] 	= $kampgeldregeling;
			}